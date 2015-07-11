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

function get_held_amount($user_id) {
	global $bank_db;

	$user_quoted = $bank_db->quote($user_id);

	$query = <<<QUERY
SELECT SUM(`amount`) AS amount
FROM `holds`
WHERE `user_id` = ${user_quoted}
QUERY;
	$row = $bank_db->query($query)->fetch();
	if ($row === false) {
		return 0;
	} else {
		return $row['amount'];
	}
}

function set_held_amount($user_id, $tagname, $amount) {
	global $bank_db;

	$user_quoted = $bank_db->quote($user_id);
	$tagname_quoted = $bank_db->quote($tagname);
	$amount_quoted = $bank_db->quote($amount);

	$query = <<<QUERY
INSERT INTO `holds`
(`user_id`, `name`, `amount`)
VALUES
(${user_quoted}, ${tagname_quoted}, ${amount_quoted})
ON DUPLICATE KEY UPDATE `amount`=VALUES(`amount`)
QUERY;
	$bank_db->query($query);
}
