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

class LoginController {
	public function get() {
		if (is_logged_in()) {
			header('Location: /app/transactions');
			die();
		}

		return [ 'login', null ];
	}

	public function post() {
		verify_csrf_token();

		$email = $_POST['email'];
		$password = $_POST['password'];
		$upassword = get_user_attr($email, 'password');

		if (!password_verify($password, $upassword)) {
			$params = [
				'message' => 'Benutzername oder Passwort falsch.'
			];
			return [ 'error', $params ];
		}

		set_user_session($email);
		header('Location: /app/transactions');
		die();
	}
}
