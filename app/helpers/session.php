<?php

/*
 * Bank
 * Copyright (C) 2015 Gunnar Beutner
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */

require_once('helpers/db.php');
require_once('helpers/mail.php');

session_start();

function is_logged_in() {
	return isset($_SESSION['email']);
}

function get_user_email() {
	return $_SESSION['email'];
}

function get_user_name() {
	return $_SESSION['name'];
}

function get_user_id() {
	return $_SESSION['uid'];
}

function verify_user() {
	if (!is_logged_in()) {
		header('Location: /app/login');
		die();
	}
}

function generate_reset_token($email) {
	global $bank_db;

	$token = bin2hex(openssl_random_pseudo_bytes(16));

	$token_quoted = $bank_db->quote($token);
	$email_quoted = $bank_db->quote($email);

	$query = <<<QUERY
UPDATE `users`
SET `reset_token`=${token_quoted}, `reset_timestamp`=NOW()
WHERE `email`=${email_quoted}
QUERY;

	$bank_db->query($query);

	return $token;
}

function get_user_last_reset($email) {
	global $bank_db;

	$email_quoted = $bank_db->quote($email);

	$query = <<<QUERY
SELECT UNIX_TIMESTAMP(`reset_timestamp`) AS reset_timestamp
FROM `users`
WHERE `email`=${email_quoted}
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return false;
	else
		return $row['reset_timestamp'];
}

function email_from_uid($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);

	$query = <<<QUERY
SELECT `email`
FROM `users`
WHERE `id`=${uid_quoted}
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return false;
	else
		return $row['email'];
}

function get_user_attr($email, $attr) {
	global $bank_db;

	$email_quoted = $bank_db->quote($email);

	$query = <<<QUERY
SELECT `$attr`
FROM `users`
WHERE `email`=${email_quoted}
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return false;
	else
		return $row[$attr];
}

function find_addresses($q) {
	global $bank_db;

	$q_quoted = $bank_db->quote($q);

	$query = <<<QUERY
SELECT `email`, `name`
FROM `users`
WHERE `email` LIKE '%@%' AND (`email` LIKE CONCAT('%', ${q_quoted}, '%') OR `name` LIKE CONCAT('%', ${q_quoted}, '%'))
QUERY;

	$addresses = [];
	foreach ($bank_db->query($query) as $row) {
		$addresses[] = [
			'email' => $row['email'],
			'name' => $row['name']
		];
	}

	return $addresses;
}

function set_user_attr($email, $attr, $value) {
	global $bank_db;

	$email_quoted = $bank_db->quote($email);
	$value_quoted = $bank_db->quote($value);

	$query = <<<QUERY
UPDATE `users`
SET `$attr`=${value_quoted}
WHERE `email`=${email_quoted}
QUERY;

	$bank_db->query($query);
}

function set_user_session($email) {
	global $bank_db;

	$email_quoted = $bank_db->quote($email);

	$query = <<<QUERY
SELECT `id`, `name`
FROM `users`
WHERE `email`=${email_quoted}
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return;

	set_user_attr($email, 'verified', 1);

	$_SESSION['email'] = $email;
	$_SESSION['uid'] = $row['id'];
	$_SESSION['name'] = $row['name'];
}

function create_new_account($email, $name, $phone) {
	global $bank_db;

	$email_quoted = $bank_db->quote($email);
	$name_quoted = $bank_db->quote($name);

	$query = <<<QUERY
INSERT INTO `users`
(`email`, `name`)
VALUES
(${email_quoted}, ${name_quoted})
QUERY;
	$bank_db->query($query);

	if (!get_user_attr($email, 'verified')) {
		set_user_attr($email, 'phone', $phone);
	}
}

function send_password_reminder($email) {
	$last_reset = get_user_last_reset($email);

	if ($last_reset !== false && ($last_reset === null || $last_reset < time() - 3600)) {
		$token = generate_reset_token($email);
		$url = "https://" . BANK_DOMAIN . "/app/reset-password?email=" . urlencode($email) . "&token=" . urlencode($token);

		if (get_user_attr($email, 'password') == '') {
			$message = <<<MESSAGE
Für Ihre E-Mailadresse wurde ein neuer Account angelegt. Mit folgendem Link können Sie den Account aktivieren und ein Passwort vergeben:

$url
MESSAGE;
			$subject = "Account aktivieren";
		} else {
			$message = <<<MESSAGE
Für Ihren Account wurde über die 'Passwort vergessen'-Funktion ein Token angefordert, mit dem das Passwort zurückgesetzt werden kann:

$url
MESSAGE;
			$subject = "Passwort zurücksetzen";
		}

		app_mail($email, $subject, $message);
	}
}

function get_user_transfer_code($email) {
	$id = get_user_attr($email, 'id');
	return 'U' . $id . 'H' . strtoupper(hash('crc32', $id));
}

function get_email_from_transfer_code($text) {
	$matches = null;
	if (!preg_match_all('/U(\\d+)H([A-Z0-9]{8})/', $text, $matches, PREG_SET_ORDER))
		return false;

	foreach ($matches as $match) {
		$uid = $match[1];
		$hash = $match[2];

		if (strtoupper(hash('crc32', $uid)) !== $hash)
			continue;

		return email_from_uid($uid);
	}

	return false;
}

if (isset($headers['HTTP_AUTHORIZATION'])) {
	$credentials = base64_decode( substr($_SERVER['HTTP_AUTHORIZATION'],6) );
	list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $credentials);
}

if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
	$email = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];
	$upassword = get_user_attr($email, 'password');

	if (!password_verify($password, $upassword)) {
		header('WWW-Authenticate: Basic realm="' . BANK_BRAND . '"');
		header('HTTP/1.0 401 Unauthorized');
		die();
	}

	set_user_session($email);
}
