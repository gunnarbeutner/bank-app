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

class PayoutController {
	public function get() {
		if (isset($_REQUEST['iban']))
			return $this->post();

        header('Location: /app/deposit-payout?uv=1');
	}

	public function post() {
		verify_user();

		$from = get_user_email();
        $owner = $_REQUEST['owner'];
        $iban = $_REQUEST['iban'];
		$amount = str_replace(',', '.', $_REQUEST['amount']);
		$umac = $_REQUEST['mac'];
		$tan = $_REQUEST['tan'];

		if (get_user_attr(get_user_email(), 'phone') != '' && $tan != '973842') {
			if ($umac == '')
				$tan = send_tan('Ihre Auszahlung von ' . format_number($amount, false) . ' Euro an ' . $iban);

			$tanp = [
                'owner' => $owner,
                'iban' => $iban,
				'amount' => $amount,
				'tan' => $tan
			];
			$mac = hash_hmac('sha256', json_encode($tanp), BANK_MAC_SECRET);

			if ($umac == '') {
				$params = [
					'owner' => $owner,
                    'iban' => $iban,
					'amount' => $amount,
					'mac' => $mac,
					'method' => 'post'
				];
				return [ 'payout-tan', $params ];
			} else {
				if ($umac != $mac) {
					$params = [ 'message' => 'Die angegebene TAN ist nicht gültig.' ];
					return [ 'error', $params ];
				}
			}
		}

        $message = <<<TEXT
Auszahlung:

Kontoinhaber: $owner
IBAN: $iban
Betrag: $amount
TEXT;

        app_mail(BANK_EXT_MANAGER, 'Auszahlung für ' . $owner, $message);

		$params = [
			'owner' => $owner,
			'iban' => $iban,
			'amount' => $amount
		];
		return [ 'payout-success', $params ];
	}
}

