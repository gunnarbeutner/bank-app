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

		$tx = get_transactions(get_user_id());
		$vp = [
			'balance' => get_user_balance(get_user_id()),
			'tx' => $tx,
			'held_amount' => get_held_amount(get_user_id())
		];
		return [ 'transactions', $vp ];
	}
}

