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
require_once('helpers/transaction.php');

class FinancestatementController {
	public function get() {
		verify_user();

		$system_accounts = [];
		$user_accounts = [];
		$user_balance_positive = '0';
		$user_balance_negative = '0';

		$users = get_account_balances();

		foreach ($users as $user) {
			if (strpos($user['email'], '@') === false)
				$system_accounts[] = $user;
			else {
                $user['last_positive'] = get_user_last_balance_above($user['email']);
                $user['last_above_threshold'] = get_user_last_balance_above($user['email'], -10);
				$user_accounts[] = $user;
                if (bccomp($user['balance'], '0') > 0) {
    				$user_balance_positive = bcadd($user_balance_positive, $user['balance']);
                } else {
                    $user_balance_negative = bcadd($user_balance_negative, $user['balance']);
                }
			}
		}

        function comp_user($a, $b) {
            if (bccomp($a['balance'], '0') < 0 && bccomp($b['balance'], '0') >= 0) {
                return 1;
            } else if (bccomp($a['balance'], '0') >= 0 && bccomp($b['balance'], '0') < 0) {
                return -1;
            } else {
                return strcmp($a['name'], $b['name']);
            }
        }

        usort($system_accounts, 'comp_user');
        usort($user_accounts, 'comp_user');

		$transactions = get_transactions_between(mktime('0') - 6 * 24 * 60 * 60, time());

		$transaction_volume = '0';

		foreach ($transactions as $transaction) {
			$amount = $transaction['amount'];
			if (bccomp($amount, '0') == -1)
				$amount = bcmul($amount, '-1');
			$transaction_volume = bcadd($transaction_volume, $amount);
		}

		$params = [
			'system_accounts' => $system_accounts,
			'user_accounts' => $user_accounts,
			'user_balance_positive' => $user_balance_positive,
			'user_balance_negative' => $user_balance_negative,
            'user_balance' => bcadd($user_balance_positive, $user_balance_negative),
			'transaction_volume' => $transaction_volume,
			'transactions' => $transactions
		];
		return [ 'finance-statement', $params ];
	}
}
