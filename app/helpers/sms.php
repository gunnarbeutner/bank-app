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

function send_text_message($to, $text) {
	$to = str_replace('+49', '0', $to);

	system(__DIR__ . "/../../scripts/sendsms.pl -H " . escapeshellarg(BANK_SMS_HOSTNAME) . ' -u ' . escapeshellarg(BANK_SMS_USERNAME) . ' -p ' . escapeshellarg(BANK_SMS_PASSWORD) . ' -n ' . escapeshellarg($to) . ' -m ' . escapeshellarg($text) . ' >/dev/null 2>&1');
}

function send_tan($description) {
	$to = get_user_attr(get_user_email(), 'phone');

	if ($to == '')
		return '';

	$tan = 100000 + unpack('N', openssl_random_pseudo_bytes(4))[1] % 900000;

	send_text_message($to, "Die TAN fuer " . $description . ' lautet: ' . $tan);

	return (string)$tan;
}

