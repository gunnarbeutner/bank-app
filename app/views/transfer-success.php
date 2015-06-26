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

?>

<h1>&Uuml;berweisung</h1>

<p>Die &Uuml;berweisung wurde erfolgreich ausgef&uuml;hrt:</p>

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
      Auftraggeber:
    </th>
    <td>
      <?php echo htmlentities(get_user_email()); ?>
    </td>
  </tr>

  <tr>
    <th>
      Zahlungsempf&auml;nger:
    </th>
    <td>
      <?php echo htmlentities($params['to']); ?>
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
</table>

<p><a href="/app/transactions">Zur Konto&uuml;bersicht</a></p>
