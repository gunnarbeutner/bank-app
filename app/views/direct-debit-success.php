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

function format_type($type) {
	if ($type == 'deposit')
		return 'Einzahlung';
	else if ($type == 'payout')
		return 'Auszahlung';
	else
		return '?';
}

?>

<h1>Lastschrift</h1>

<p>Die Lastschrift wurde erfolgreich ausgef&uuml;hrt:</p>

<table class="aui">
  <tr>
    <th>
      Transaktions-Nummer:
    </th>
    <td>
      TX<?php echo htmlentities($params['txid']); ?>
    </td>
  </tr>

  <tr>
    <th>
      Kontoadresse:
    </th>
    <td>
      <?php echo htmlentities($params['from']); ?>
    </td>
  </tr>

  <tr>
    <th>
      Betrag (&euro;):
    </th>
    <td>
      <?php echo format_number($params['amount']); ?>
    </td>
  </tr>

  <tr>
    <th>
      Verwendungszweck:
    </th>
    <td>
      <?php echo htmlentities($params['reference']); ?>
    </td>
  </tr>

<?php if (get_user_attr(get_user_email(), 'admin')) { ?>
  <tr>
    <th>
      Dispolimit ignorieren:
    </th>
    <td>
      <?php echo $params['ignore_limits'] ? 'Ja' : 'Nein'; ?>
    </td>
  </tr>
<?php } ?>
</table>

<p><a href="/app/transactions">Zur Konto&uuml;bersicht</a></p>
