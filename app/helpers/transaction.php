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

require_once('helpers/db.php');
require_once('helpers/mail.php');
require_once('helpers/preauth.php');

function get_user_balance($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);
	$query = <<<QUERY
SELECT `balance`
FROM `users`
WHERE `id` = ${uid_quoted}
QUERY;

	return $bank_db->query($query)->fetch()['balance'];
}

function new_transaction($from_mail, $to_mail, $type, $amount, $reference, $use_tx = true, $ignore_limits = false) {
	global $bank_db;

	if (bccomp($amount, 0) != 1)
		return [ false, 'Der Betrag muss positiv sein.' ];

	if ($type != 'Transfer' && $type != 'Direct Debit')
		return [ false, 'Ungültiger Transaktionstyp.' ];

	$from = get_user_attr($from_mail, 'id');
	$to = get_user_attr($to_mail, 'id');

	if ($from == false || $to == false || $from == $to)
		return [ false, 'Auftraggeber oder Empfänger ungültig.' ];

	$from_quoted = $bank_db->quote($from);
	$to_quoted = $bank_db->quote($to);
	$type_quoted = $bank_db->quote($type);
	$amount_quoted = $bank_db->quote($amount);
	$reference_quoted = $bank_db->quote($reference);

	if ($use_tx)
		$bank_db->query("BEGIN");

	$query = <<<QUERY
SELECT `balance`
FROM `users`
WHERE `id`=${from_quoted}
FOR UPDATE
QUERY;
	$row_from = $bank_db->query($query)->fetch();

	$balance_from = bcsub($row_from['balance'], $amount);

    if (!$ignore_limits) {
        $held_amount = get_held_amount(get_user_attr($from_mail, 'id'));
        $balance_from_held = bcsub($balance_from, $held_amount);

        $credit_limit_from = get_user_attr($from_mail, 'credit_limit');

        if (bccomp($balance_from_held, bcmul($credit_limit_from, -1)) == -1) {
            if ($use_tx)
                $bank_db->query("ROLLBACK");
            return [ false, 'Unzureichende Kontodeckung.' ];
        }
    }

	$query = <<<QUERY
SELECT `balance`
FROM `users`
WHERE `id`=${to_quoted}
FOR UPDATE
QUERY;
	$row_to = $bank_db->query($query)->fetch();

	$balance_to = bcadd($row_to['balance'], $amount);

	$query = <<<QUERY
INSERT INTO `transactions`
(`from`, `to`, `type`, `amount`, `reference`)
VALUES
(${from_quoted}, ${to_quoted}, ${type_quoted}, ${amount_quoted}, ${reference_quoted})
QUERY;
	$bank_db->query($query);

	$txid = $bank_db->lastInsertId();

	$balance_from_quoted = $bank_db->quote($balance_from);

	$query = <<<QUERY
UPDATE `users`
SET `balance` = ${balance_from_quoted}
WHERE `id` = ${from_quoted}
QUERY;
	$bank_db->query($query);

	$balance_to_quoted = $bank_db->quote($balance_to);

	$query = <<<QUERY
UPDATE `users`
SET `balance` = ${balance_to_quoted}
WHERE `id` = ${to_quoted}
QUERY;
	$bank_db->query($query);

	if ($use_tx)
		$bank_db->query("COMMIT");

	$amount_formatted = format_number($amount, false);

	if (get_user_attr($from_mail, 'verified')) {
		$subject = "Abbuchung von Ihrem Konto an ${to_mail}: ${reference}";

		$message = <<<MESSAGE
Von Ihrem Konto fand eine Abbuchung statt:

Empfänger: $to_mail
Betrag (€): ${amount_formatted}
Verwendungszweck: $reference
MESSAGE;

		app_mail($from_mail, $subject, $message);
	}

	if (get_user_attr($to_mail, 'verified')) {
		$subject = "Gutschrift auf Ihr Konto von ${from_mail}: ${reference}";

		$message = <<<MESSAGE
Für Ihr Konto fand eine Gutschrift statt:

Auftraggeber: $from_mail
Betrag (€): ${amount_formatted}
Verwendungszweck: $reference
MESSAGE;

		app_mail($to_mail, $subject, $message);
	}

	return [ true, $txid ];
}

function get_transactions($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);
	$query = <<<QUERY
SELECT t.`id`, UNIX_TIMESTAMP(t.`timestamp`) AS timestamp, t.`type`, t.`from`, u1.`name` AS from_name, u1.`email` AS from_email, t.`to`, u2.`name` AS to_name, u2.`email` AS to_email, t.`amount`, t.`reference`
FROM `transactions` t
LEFT JOIN `users` u1 ON u1.`id`=t.`from`
LEFT JOIN `users` u2 ON u2.`id`=t.`to`
WHERE `from` = ${uid_quoted} OR `to` = ${uid_quoted}
ORDER BY t.`id` DESC
QUERY;
	return $bank_db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

function get_last_inbound_txid($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);
	$query = <<<QUERY
SELECT `id`
FROM `transactions`
WHERE `to` = ${uid_quoted}
ORDER BY `id` DESC
LIMIT 1
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return -1;
	else
		return $row['id'];
}

function get_last_outbound_txid($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);
	$query = <<<QUERY
SELECT `id`
FROM `transactions`
WHERE `from` = ${uid_quoted}
ORDER BY `id` DESC
LIMIT 1
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return -1;
	else
		return $row['id'];
}

function get_last_txid($uid) {
	global $bank_db;

	$uid_quoted = $bank_db->quote($uid);
	$query = <<<QUERY
SELECT `id`
FROM `transactions`
WHERE `from` = ${uid_quoted} OR `to` = ${uid_quoted}
ORDER BY `id` DESC
LIMIT 1
QUERY;

	$row = $bank_db->query($query)->fetch();

	if ($row === false)
		return -1;
	else
		return $row['id'];
}

bcscale(2);

function format_number($num, $html = true) {
	if ((float)$num >= 0)
		$color = "green";
	else
		$color = "red";

	$num = str_replace('.', ',', bcadd($num, 0));

	if ($html)
		return sprintf('<span style="color: %s">%s</span>', $color, htmlentities($num));
	else
		return $num;
}

function get_account_balances() {
	global $bank_db;

	$query = <<<QUERY
SELECT `email`, `name`, `balance`
FROM `users`
QUERY;

	return $bank_db->query($query)->fetchAll();
}

function get_transactions_between($begin, $end) {
	global $bank_db;

	$begin_quoted = $bank_db->quote($begin);
	$end_quoted = $bank_db->quote($end);

	$query = <<<QUERY
SELECT t.`id`, UNIX_TIMESTAMP(t.`timestamp`) AS timestamp, t.`type`, t.`from`, u1.`email` AS from_email, t.`to`, u2.`email` AS to_email, t.`amount`, t.`reference`
FROM `transactions` t
LEFT JOIN `users` u1 ON u1.`id`=t.`from`
LEFT JOIN `users` u2 ON u2.`id`=t.`to`
WHERE UNIX_TIMESTAMP(t.`timestamp`) > ${begin_quoted} AND UNIX_TIMESTAMP(t.`timestamp`) < ${end_quoted}
QUERY;
	return $bank_db->query($query)->fetchAll();
}

function _cmp_transaction($a, $b) {
    if ($a['timestamp'] > $b['timestamp'])
        return -1;
    else if ($a['timestamp'] < $b['timestamp'])
        return 1;
    else
        return 0;
}

function get_user_last_positive($email) {
    $balance = get_user_attr($email, 'balance');

    if (bccomp($balance, '0') != -1) {
        return time();
    }

    $uid = get_user_attr($email, 'id');

    $transactions = get_transactions($uid);
    usort($transactions, '_cmp_transaction');

    foreach ($transactions as $transaction) {
        $amount = $transaction['amount'];

        if ($transaction['to'] == $uid) {
            $amount = bcmul('-1', $amount);
        }

        $balance = bcadd($balance, $amount);

        if (bccomp($balance, '0') != -1) {
            return $transaction['timestamp'];
        }
    }

    return 0;
}
