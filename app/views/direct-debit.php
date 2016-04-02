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

<h1>Lastschrift</h1>

<p>
  Lastschrift auf Konto:
  <strong><?php echo htmlentities(get_user_name()) . ' (' . htmlentities(get_user_email()) . ')'; ?></strong>
</p>

<form method="post" action="/app/direct-debit" class="aui">
  <div class="field-group">
    <label for="from">
      Zahlungspflichtiger
    </label>
    <input type="text" class="text address" maxlength="128" name="from" id="from" required="required"></input>
  </div>

  <div class="field-group">
    <label for="amount">
      Betrag (&euro;)
    </label>
    <input type="text" class="text" maxlength="128" name="amount" id="amount" required="required"></input>
  </div>

  <div class="field-group">
    <label for="reference">
      Verwendungszweck
    </label>
    <input type="text" class="text" maxlength="128" name="reference" id="reference" required="required"></input>
  </div>

  <div class="field-group">
    <label for="ignore_limits">
      Dispolimit ignorieren
    </label>
    <input type="checkbox" class="checkbox" name="ignore_limits" id="ignore_limits" value="1"></input>
  </div>

  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="last_txid" value="<?php echo htmlentities($params['last_txid']); ?>"></input>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"></input>
      <input class="button submit" type="submit" value="Ausf&uuml;hren"></input>
    </div>
  </div>
</form>

