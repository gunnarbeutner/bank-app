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

require_once('helpers/csrf.php');
require_once('helpers/session.php');

class RegisterController {
	public function get() {
		if (is_logged_in()) {
			header('Location: /app/transactions');
			die();
		}

		return [ 'register', null ];
	}

	public function post() {
		if (is_logged_in()) {
			header('Location: /app/transactions');
			die();
		}

		verify_csrf_token();

		$email = $_POST['email'];
		$phone = $_POST['phone'];
		$name = $_POST['name'];

		if (strlen($phone) > 0 && $phone[0] == '0')
			$phone = '+49' . substr($phone, 1);

		if (strlen($phone) < 6 || !preg_match('/^\\+[0-9 ]+$/', $phone)) {
			$params = [ 'message' => 'Die angegebene Mobilfunknummer ist ungültig (Beispiel: +49 171 55 577 77).' ];
			return [ 'error', $params ];
		}

		$found = false;
		foreach (BANK_MAIL_FILTERS as $mail_filter) {
			if (fnmatch($mail_filter, $email)) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			$params = [ 'message' => 'Accountregistrierung für Ihre E-Mailadresse wird aktuell leider nicht unterstützt.' ];
			return [ 'error', $params ];
		}

		create_new_account($email, $name, $phone);
		send_password_reminder($email);

		return [ 'register-success', null ];
	}
}

