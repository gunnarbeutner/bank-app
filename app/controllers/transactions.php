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

require_once('helpers/transaction.php');
require_once('helpers/session.php');
require_once('helpers/preauth.php');

class TransactionsController {
	public function get() {
		verify_user();

        if (isset($_REQUEST['account'])) {
            $account = $_REQUEST['account'];
        } else {
            $account = get_user_email();
        }

        if (!get_user_attr(get_user_email(), 'admin') && $account != get_user_email() && get_user_attr(get_user_email(), 'proxy_user_id') != get_user_id()) {
            $params = [ 'message' => 'Zugriff verweigert.' ];
            return [ 'error', $params ];
        }

        $uid = get_user_attr($account, 'id');

        if ($uid === false) {
            $params = [ 'message' => 'Zugriff verweigert.' ];
            return [ 'error', $params ];
        }

		$vp = [
            'uid' => $uid,
            'account' => $account,
            'accounts' => get_user_accounts(get_user_id()),
			'balance' => get_user_balance($uid),
			'tx' => get_transactions($uid),
			'held_amount' => get_held_amount($uid),
            'credit_limit' => get_user_attr($account, 'credit_limit')
		];
		return [ 'transactions', $vp ];
	}
}

