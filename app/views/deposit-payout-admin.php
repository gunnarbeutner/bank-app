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

?>

<h1>Ein- und Auszahlung</h1>

<form method="post" action="/app/deposit-payout" class="aui">
  <div class="field-group">
    <label for="from">
      Vorgang
    </label>
    <select class="select" name="type" id="type" required="required">
      <option selected="selected" value="deposit">Einzahlung</option>
      <option value="payout">Auszahlung</option>
    </select>
  </div>

  <div class="field-group">
    <label for="pop">
      POP
    </label>
    <select class="select" name="pop" id="pop" required="required">
<?php
	foreach (BANK_POPS as $account => $name) {
		echo '<option value="' . htmlentities($account) . '">' . htmlentities($name) . '</option>';
	}
?>
    </select>
  </div>

  <div class="field-group">
    <label for="customer">
      Kunde
    </label>
    <input type="text" class="text address" maxlength="128" name="customer" id="customer" required="required"></input>
  </div>

  <div class="field-group">
    <label for="amount">
      Betrag (&euro;)
    </label>
    <input type="text" class="text" maxlength="128" name="amount" id="amount" required="required"></input>
  </div>

  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="last_txid" value="<?php echo htmlentities($params['last_txid']); ?>"></input>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"></input>
      <input class="button submit" type="submit" value="Ausf&uuml;hren"></input>
    </div>
  </div>
</form>

