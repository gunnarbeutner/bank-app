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
require_once('helpers/otp.php');

class OtptokenController {
	public function get() {
		verify_user();

		if (get_user_attr(get_user_email(), 'yubikey_identity') != '') {
			$params = [ 'message' => 'Für den Account ist bereits ein Yubikey registriert. Bitte lassen Sie diesen zunächst durch einen Administrator entfernen.' ];
			return [ 'error', $params ];
		}

		return [ 'otp-token', null ];
	}

	public function post() {
		verify_csrf_token();

		if (get_user_attr(get_user_email(), 'yubikey_identity') != '') {
			$params = [ 'message' => 'Für den Account ist bereits ein Yubikey registriert. Bitte lassen Sie diesen zunächst durch einen Administrator entfernen.' ];
			return [ 'error', $params ];
		}

		$otp_token = $_POST['otp-token'];

		$yubikey_identity = get_yubikey_identity($otp_token);

		if ($yubikey_identity === false) {
			$params = [ 'message' => 'Das angegebene OTP-Token ist nicht gültig.' ];
			return [ 'error', $params ];
		}

		set_user_attr(get_user_email(), 'yubikey_identity', $yubikey_identity);

		return [ 'otp-token-success', null ];
	}
}

