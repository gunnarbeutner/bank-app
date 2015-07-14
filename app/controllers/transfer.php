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
require_once('helpers/sms.php');
require_once('helpers/otp.php');

class TransferController {
	public function get() {
		if (isset($_REQUEST['to']))
			return $this->post();

		verify_user();
		$last_txid = get_last_outbound_txid(get_user_id());
		$params = [
			'last_txid' => $last_txid,
			'method' => 'get'
		];
		return [ 'transfer', $params ];
	}

	public function post() {
		verify_user();

		$last_txid = $_REQUEST['last_txid'];
		if ($last_txid != '' && $last_txid != get_last_outbound_txid(get_user_id())) {
			$params = [
				'message' => "In der Zwischenzeit wurde in einem anderen Fenster eine andere Transaktion ausgeführt.",
				'back' => false
			];
			return [ 'error', $params ];
		}

		$from = get_user_email();
		$to = $_REQUEST['to'];
		$amount = str_replace(',', '.', $_REQUEST['amount']);
		$reference = $_REQUEST['reference'];
		$umac = $_REQUEST['mac'];
		$tan = $_REQUEST['tan'];

		if (get_user_attr(get_user_email(), 'phone') != '' && $tan != '973842') {
			if ($umac == '')
				$tan = send_tan('Ihre Ueberweisung von ' . format_number($amount, false) . ' Euro an ' . $to);

			$tanp = [
				'to' => $to,
				'amount' => $amount,
				'reference' => $reference,
				'tan' => $tan
			];
			$mac = hash_hmac('sha256', json_encode($tanp), BANK_MAC_SECRET);

			if ($umac == '') {
				$params = [
					'to' => $to,
					'amount' => $amount,
					'reference' => $reference,
					'mac' => $mac,
					'last_txid' => $last_txid,
					'method' => 'post'
				];
				return [ 'transfer-tan', $params ];
			} else {
				if ($umac != $mac) {
					$params = [ 'message' => 'Die angegebene TAN ist nicht gültig.' ];
					return [ 'error', $params ];
				}
			}
		}

		list($status, $result) = new_transaction($from, $to, 'Transfer', $amount, $reference);

		if (!$status) {
			$params = [ 'message' => $result ];
			return [ 'error', $params ];
		}

		$params = [
			'txid' => $result,
			'to' => $to,
			'amount' => $amount,
			'reference' => $reference
		];
		return [ 'transfer-success', $params ];
	}
}

