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

<p>Bitte &uuml;berpr&uuml;fen Sie Ihre Angaben und best&auml;tigen Sie die Transaktion mit einer TAN:</p>

<table class="aui">
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

<form method="post" action="/app/transfer" class="aui">
  <div class="field-group">
    <label for="reference">
      smsTAN
    </label>
    <input type="text" class="text" maxlength="128" name="tan" id="tan" required="required"></input>
  </div>

  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="to" value="<?php echo htmlentities($params['to']); ?>"></input>
      <input type="hidden" name="amount" value="<?php echo htmlentities($params['amount']); ?>"></input>
      <input type="hidden" name="reference" value="<?php echo htmlentities($params['reference']); ?>"></input>
      <input type="hidden" name="mac" value="<?php echo htmlentities($params['mac']); ?>"></input>
      <input type="hidden" name="last_txid" value="<?php echo htmlentities($params['last_txid']); ?>"></input>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"></input>
      <input class="button submit" type="submit" value="&Uuml;berweisen"></input>
    </div>
  </div>
</form>

