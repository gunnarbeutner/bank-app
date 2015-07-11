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

<h1>Konto</h1>

<table class="aui">
  <tr>
    <th>Konto:</th>
    <td><?php echo htmlentities(get_user_email()); ?></td>
  </tr>
  <tr>
    <th>Kontostand:</th>
    <td><b><?php echo format_number($params['balance']); ?> &euro;</b></td>
  </tr>
  <tr>
    <th>Dispolimit:</th>
    <td><?php echo format_number(get_user_attr(get_user_email(), 'credit_limit'), false); ?> &euro;</td>
  </tr>
  <tr>
    <th>Angefragte Ums&auml;tze:
      <span class="tooltip" title="Angefragte Ums&auml;tze sind noch nicht gebuchte Ums&auml;tze, z.B. f&uuml;r Bestellungen, die noch nicht ausgef&uuml;hrt wurden.">
        <span class="aui-icon aui-icon-small aui-iconfont-help tooltip">
      </span>
    </th>
    <td><?php echo format_number($params['held_amount']); ?>
  </tr>
</table>

<script type="text/javascript">
  AJS.$(".tooltip").tooltip();
</script>

<h1>Umsatz&uuml;bersicht</h1>

<p>Es werden die Buchungen der letzten <b>90 Tage</b> angezeigt.</p>

<table class="aui zebra" id="transactions">
  <tr>
    <th>Wertstellung</th>
    <th>Auftraggeber / Empf&auml;nger</th>
    <th>Umsatzart</th>
    <th>Verwendungszweck</th>
    <th>Betrag (&euro;)</th>
    <th>Saldo (&euro;)</th>
 </tr>
<?php
	$balance = $params['balance'];

	foreach ($params['tx'] as $tx) {
		$ts = date('d.m.Y', $tx['timestamp']);
		$amount = $tx['amount'];

		if ($tx['from'] == get_user_id()) {
			$other_user_email = $tx['to_email'];
			$other_user_name = $tx['to_name'];
			$amount = bcmul($amount, '-1');
			if ($tx['type'] == 'Direct Debit')
				$type = 'Lastschrift';
			else
				$type = 'Überweisung';
		} else {
			$other_user_email = $tx['from_email'];
			$other_user_name = $tx['from_name'];
			$type = 'Gutschrift';
		}

		$html = <<<HTML
  <tr>
    <td>%s</td>
    <td title="%s">%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
  </tr>

HTML;

		printf($html,
		    htmlentities($ts), htmlentities($other_user_email), htmlentities($other_user_name), htmlentities($type),
		    nl2br(htmlentities(wordwrap($tx['reference'], 25, "\n", true))), format_number($amount), format_number($balance));

		$balance = bcsub($balance, $amount);
	}
?>
</table>
