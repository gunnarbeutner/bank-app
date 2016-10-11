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

require_once('helpers/session.php');
require_once('helpers/transaction.php');

const MINIMUM_DEBT = '-10';
const MINIMUM_DAYS = 14;
const LIMIT_ADJUSTMENT = '5';

class AdjustlimitsController {
	public function post() {
		if (!get_user_attr(get_user_email(), 'admin')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$email = $_GET['email'];

        $last_adjustment = get_user_attr($email, 'last_credit_limit_adjustment');

        if ($last_adjustment !== null) {
            $last_adjustment = strtotime($last_adjustment);
        }

        $today = strtotime(date("Y-m-d", time()));

        $next_adjustment = null;
        $adjusted = false;

        if (bccomp(get_user_attr($email, 'balance'), MINIMUM_DEBT) <= 0 && bccomp(get_user_attr($email, 'credit_limit'), '0') != 0) {
            $next_adjustment = get_user_last_balance_above($email, MINIMUM_DEBT) + 24 * 60 * 60 * MINIMUM_DAYS;

            if ($last_adjustment !== null) {
                $adjtmp = $last_adjustment + 24 * 60 * 60 * MINIMUM_DAYS;
                if ($adjtmp > $next_adjustment) {
                    $next_adjustment = $adjtmp;
                }
            }

            if ($next_adjustment !== null && time() >= $next_adjustment) {
                $credit_limit = get_user_attr($email, 'credit_limit');
                if (bccomp($credit_limit, LIMIT_ADJUSTMENT) <= 0) {
                    $credit_limit = 0;
                } else {
                    $credit_limit = bcsub($credit_limit, LIMIT_ADJUSTMENT);
                }

                set_user_attr($email, 'credit_limit', $credit_limit);
                set_user_attr($email, 'last_credit_limit_adjustment', date('Y-m-d'));
                $adjusted = true;
            }
        }

		$params = [
            'adjusted' => $adjusted,
            'credit_limit' => get_user_attr($email, 'credit_limit'),
            'next_credit_limit_adjustment' => $next_adjustment,
		];
		return [ 'adjust-limits', $params ];
	}
}
