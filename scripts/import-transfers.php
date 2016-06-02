#!/usr/bin/env php
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

require_once(__DIR__ . '/../config.php');
require_once('helpers/session.php');
require_once('helpers/db.php');
require_once('helpers/transaction.php');

if (count($argv) < 2) {
	echo 'Syntax: ' . $argv[0] . " <csv-file>\n";
	die();
}

$fp = fopen($argv[1], 'r');

$headers = fgetcsv($fp, 0, ';');

$transfers = array();

while (($line = fgetcsv($fp, 0, ';')) !== false) {
	$transaction = array();
	for ($i = 0; $i < count($line); $i++)
		$transaction[$headers[$i]] = $line[$i];

	$purpose = '';
	for ($i = 1; $i < count($line); $i++) {
		if (preg_match('/^purpose\\d+$/', $headers[$i])) {
			$purpose .= $line[$i];
		}
	}

	$date = $transaction['date'];
	$user = get_email_from_transfer_code($purpose);

	if ($user === false)
		continue;

	if (!isset($transfers[$user]))
		$transfers[$user] = array();

	if (!isset($transfers[$user][$date])) {
		$transfers[$user][$date] = array(
			'amount' => 0,
			'transactions' => array()
		);
	}

	$transfers[$user][$date]['amount'] = bcadd($transfers[$user][$date]['amount'], $transaction['value_value']);
	$transfers[$user][$date]['transactions'][] = $transaction;
}

foreach ($transfers as $email => $dates) {
	foreach ($dates as $date => $info) {
		$uid = get_user_attr($email, 'id');

		$date_quoted = $bank_db->quote($date);
		$uid_quoted = $bank_db->quote($uid);

		$bank_db->query("BEGIN");

		$query = <<<QUERY
SELECT `amount`
FROM `external_transfers`
WHERE `date`=${date_quoted}
AND `uid`=${uid_quoted}
QUERY;
		$row = $bank_db->query($query)->fetch();

		if ($row === false) {
			$prev_amount = 0;
			$new_row = true;
		} else {
			$prev_amount = $row['amount'];
			$new_row = false;
		}

		$amount = 0;
		$transactions = array();
		foreach ($info['transactions'] as $transaction) {
			$amount = bcadd($amount, $transaction['value_value']);

			/* Ignore transactions we've already processed. */
			if (bccomp($amount, $prev_amount) <= 0)
				continue;

			$prev_amount = bcadd($prev_amount, $transaction['value_value']);

			$transactions[] = array(
				'amount' => $transaction['value_value'],
				'reference' => 'SEPA-Überweisung von ' . $transaction['remoteName'] . ' (IBAN: ' . $transaction['remoteAccountNumber'] . ')'
			);
		}

		if (bccomp($amount, $prev_amount) != 0) {
			echo 'Mismatching transfers for date ' . $date . ' and user ' . $email . ': expected ' . $prev_amount . ' - got: ' . $amount . "\n";
			die();
		}

		$amount_quoted = $bank_db->quote($amount);

		if ($new_row) {
			$query = <<<QUERY
INSERT INTO `external_transfers`
(`date`, `uid`, `amount`)
VALUES
(${date_quoted}, ${uid_quoted}, ${amount_quoted})
QUERY;
		} else {
			$query = <<<QUERY
UPDATE `external_transfers`
SET `amount`=${amount_quoted}
WHERE `date`=${date_quoted}
AND `uid`=${uid_quoted}
QUERY;
		}

		$bank_db->query($query);

		foreach ($transactions as $transaction) {
			echo "Executing transfer for ${email}: Amount: ${transaction['amount']}, Reference: ${transaction['reference']}\n";
			$res = new_transaction(BANK_EXT_ACCOUNT, BANK_MGMT_ACCOUNT, 'Direct Debit', $transaction['amount'], 'Auszahlung auf externes Konto', false);
			if ($res[0] === false) {
				$bank_db->query("ROLLBACK");
				echo "Transaction failed for account ${email}: ${res[1]}\n";
				die();
			}
			$res = new_transaction(BANK_MGMT_ACCOUNT, $email, 'Transfer', $transaction['amount'], $transaction['reference'], false);
			if ($res[0] === false) {
				$bank_db->query("ROLLBACK");
				echo "Transaction failed for account ${email}: ${res[1]}\n";
				die();
			}
		}

		$bank_db->query("COMMIT");
	}
}

?>
