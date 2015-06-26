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

class ProfileController {
	public function get() {
		verify_user();

		$force = (get_user_attr(get_user_email(), 'password') == '');
		$direct_debit = get_user_attr(get_user_email(), 'allow_direct_debit');

		$params = [
			'force' => $force,
			'direct-debit' => $direct_debit
		];
		return [ 'profile', $params ];
	}

	public function post() {
		verify_csrf_token();

		$upassword = get_user_attr(get_user_email(), 'password');
		$force = ($upassword == '');

		$old_password = $_POST['old-password'];
		$password1 = $_POST['password1'];
		$password2 = $_POST['password2'];
		$direct_debit = $_POST['direct-debit'];	

		set_user_attr(get_user_email(), 'allow_direct_debit', $direct_debit == 'yes');

		if ($password1 != '') {
			if ($upassword != '' && !password_verify($old_password, $upassword)) {
				$params = [ 'message' => 'Bitte geben Sie Ihr bestehendes Passwort an.' ];
				return [ 'error', $params ];
			}

			if (strlen($password1) < 8) {
				$params = [ 'message' => 'Das angegebene Passwort ist zu kurz. Verwenden Sie ein Passwort mit mindestens 8 Stellen.' ];
				return [ 'error', $params ];
			}

			if ($password1 != $password2) {
				$params = [ 'message' => 'Die beiden Passwörter müssen übereinstimmen.' ];
				return [ 'error', $params ];
			}

			$hash = password_hash($password1, PASSWORD_DEFAULT);
			set_user_attr(get_user_email(), 'password', $hash);
		} else if ($force) {
			$params = [ 'message' => 'Bitte setzen Sie ein Passwort für Ihren Account.' ];
			return [ 'error', $params ];
		}

		return [ 'profile-success', null ];
	}
}

