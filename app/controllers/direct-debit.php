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

class DirectdebitController {
	public function get() {
		if (!get_user_attr(get_user_email(), 'direct_debit')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$params = [
			'last_txid' => get_last_inbound_txid(get_user_id())
		];
		return [ 'direct-debit', $params ];
	}

	public function post() {
		if (!get_user_attr(get_user_email(), 'direct_debit')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$last_txid = $_POST['last_txid'];
		if ($last_txid != '' && $last_txid != get_last_inbound_txid(get_user_id())) {
			$params = [
				'message' => "In der Zwischenzeit wurde in einem anderen Fenster eine andere Transaktion ausgeführt.",
				'back' => false
			];
			return [ 'error', $params ];
		}

		$amount = str_replace(',', '.', $_POST['amount']);
		$from = $_POST['from'];
		$reference = $_POST['reference'];

		if (!get_user_attr($from, 'allow_direct_debit')) {
			$params = [ 'message' => 'Der Benutzer hat der Durchführung von Lastschriften widersprochen.' ];
			return [ 'error', $params ];
		}

		list($status, $result) = new_transaction($from, get_user_email(), 'Direct Debit', $amount, $reference);

		if (!$status) {
			$params = [ 'message' => $result ];
			return [ 'error', $params ];
		}

		$params = [
			'txid' => $result,
			'from' => $from,
			'amount' => $amount,
			'reference' => $reference
		];
		return [ 'direct-debit-success', $params ];
	}
}
