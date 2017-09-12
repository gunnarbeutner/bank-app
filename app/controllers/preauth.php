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
require_once('helpers/preauth.php');

class PreauthController {
	public function post() {
		if (!get_user_attr(get_user_email(), 'direct_debit')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$email = $_POST['email'];
		$tag = $_POST['tag'];
		$amount = $_POST['amount'];
        $override_credit_limit = $_POST['override_credit_limit'];

		$id = get_user_attr($email, 'id');

		if ($id === false) {
			$params = [ 'message' => 'Benutzername ungültig.' ];
			return [ 'error', $params ];
		}

		$balance = get_user_attr($email, 'balance');
		$balance = bcsub($balance, $amount);
		$credit_limit = get_user_attr($email, 'credit_limit');

        if ($override_credit_limit != '' && bccomp($override_credit_limit, $credit_limit) == -1) {
            $credit_limit = $override_credit_limit;
        }

		if (bccomp($balance, bcmul($credit_limit, -1)) == -1) {
			$params = [ 'message' => 'Authorization fehlgeschlagen.' ];
			return [ 'error', $params ];
		}

		set_held_amount($id, $tag, $amount);

		$params = [];
		return [ 'preauth-success', $params ];
	}
}
