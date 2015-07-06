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

?>

<h1>Bankbilanz</h1>

<p>Aus Gr&uuml;nden der Transparenz gibt es hier eine &Uuml;bersicht des aktuellen Bankstatus.</p>

<p>Stand: <?php echo date('r'); ?></p>

<h2>Verbindlichkeiten</h2>

<table class="aui" id="system-accounts">
  <tr>
    <th>Name</th>
    <th>Kontostand</th>
 </tr>
<?php
	foreach ($params['system_accounts'] as $user) {
		$html = <<<HTML
  <tr>
    <td title="%s">%s</td>
    <td>%s</td>
  </tr>

HTML;

		printf($html,
		    htmlentities($user['email']), htmlentities($user['name']), format_number($user['balance']));
	}

	if (get_user_attr(get_user_email(), 'admin')) {
		foreach ($params['user_accounts'] as $user) {
//			if (bccomp($user['balance'], '0') == 0)
//				continue;

			$html = <<<HTML
  <tr>
    <td title="%s">%s</td>
    <td>%s</td>
  </tr>

HTML;

			printf($html,
			    htmlentities($user['email']), htmlentities($user['name']), format_number($user['balance']));
		}
	} else {
?>
  <tr>
    <td>Alle Kundenaccounts</td>
    <td><?php echo format_number($params['user_balance']); ?></td>
 </tr>
<?php
	}
?>
</table>

<h2>Transaktionen</h2>

<p>Heutiges Transaktionsvolumen: <?php echo format_number($params['transaction_volume']); ?> &euro;

<?php
	if (get_user_attr(get_user_email(), 'admin') && count($params['transactions']) > 0) {
?>
<table class="aui" id="transactions">
  <tr>
    <th>ID</th>
    <th>Zeitstempel</th>
    <th>Typ</th>
    <th>Zahlungspflichtiger</th>
    <th>Zahlungsempf&auml;nger</th>
    <th>Betrag (&euro;)</th>
    <th>Verwendungszweck</th>
  </tr>
<?php
		foreach ($params['transactions'] as $transaction) {
			$html = <<<HTML
  <tr>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
  </tr>

HTML;

			printf($html,
			    htmlentities($transaction['id']), date('H:i:s', $transaction['timestamp']), htmlentities($transaction['type']),
			    htmlentities($transaction['from_email']), htmlentities($transaction['to_email']),
			    format_number($transaction['amount']), htmlentities($transaction['reference']));
		}
?>
</table>
<?php
	}
?>
