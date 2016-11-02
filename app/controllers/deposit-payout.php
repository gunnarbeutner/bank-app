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

class DepositpayoutController {
	public function get() {
		verify_user();

		if ($_REQUEST['uv'] != 1 && get_user_attr(get_user_email(), 'admin')) {
			$params = [
				'last_txid' => get_last_txid(get_user_attr(BANK_MGMT_ACCOUNT, 'id'))
			];
			return [ 'deposit-payout-admin', $params ];
		} else {
			$params = [
				'tx-code' => get_user_transfer_code(get_user_email())
			];
			return [ 'deposit-payout-user', $params ];
		}
	}

	public function post() {
		global $bank_db;

		verify_user();

		if (!get_user_attr(get_user_email(), 'admin')) {
			$params = [ 'message' => 'Zugriff verweigert.' ];
			return [ 'error', $params ];
		}

		$last_txid = $_POST['last_txid'];
		if ($last_txid != get_last_txid(get_user_attr(BANK_MGMT_ACCOUNT, 'id'))) {
			$params = [
				'message' => "In der Zwischenzeit wurde in einem anderen Fenster eine andere Transaktion ausgeführt.",
				'back' => false
			];
			return [ 'error', $params ];
		}

		$type = $_POST['type'];
		$amount = str_replace(',', '.', $_POST['amount']);
		$pop = $_POST['pop'];
		$customer = $_POST['customer'];

		if ($type == 'deposit') {
			$from = BANK_MGMT_ACCOUNT;
			$to = $customer;
			$reference = 'Einzahlung';

			$pop_from = $pop;
			$pop_to = BANK_MGMT_ACCOUNT;
			$pop_reference = 'Auszahlung';
		} else {
			$from = $customer;
			$to = BANK_MGMT_ACCOUNT;
			$reference = 'Auszahlung';

			$pop_from = $to;
			$pop_to = $pop;
			$pop_reference = 'Einzahlung';
		}

		$bank_db->query("BEGIN");

		list($status, $result) = new_transaction($pop_from, $pop_to, 'Direct Debit', $amount, $pop_reference, get_user_email(), false);

		if (!$status) {
			$params = [ 'message' => "Bank -> POP - " . $result ];
			return [ 'error', $params ];
		}

		list($status, $result) = new_transaction($from, $to, 'Direct Debit', $amount, $reference, get_user_email(), false);

		if (!$status) {
			$params = [ 'message' => "Bank -> Kunde - " . $result ];
			return [ 'error', $params ];
		}

		$bank_db->query("COMMIT");

		$params = [
			'txid' => $result,
			'type' => $type,
			'customer' => $customer,
			'amount' => $amount
		];
		return [ 'deposit-payout-admin-success', $params ];
	}
}
