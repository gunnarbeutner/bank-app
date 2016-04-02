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

?>

<h1>Einzahlung</h1>

<p>Einzahlungen auf das eigene Konto k&ouml;nnen vorzugsweise per SEPA-&Uuml;berweisung durchgef&uuml;hrt werden:</p>

<table class="aui">
  <tr>
    <th>Kontoinhaber:</th>
    <td><?php echo BANK_EXT_OWNER; ?></td>
  </tr>
  <tr>
    <th>IBAN:</th>
    <td><?php echo BANK_EXT_IBAN; ?></td>
  </tr>
  <tr>
    <th>Kreditinstitut:</th>
    <td><?php echo BANK_EXT_ORG; ?></td>
  </tr>
  <tr>
    <th>Verwendungszweck:</th>
    <td><?php echo $params['tx-code']; ?></td>
  </tr>
</table>

<p>Die Gutschrift erfolgt automatisch sobald die &Uuml;berweisung seitens der Bank best&auml;tigt wurde.</p>

<h1>Auszahlung</h1>

<form class="aui" method="post" action="/app/payout">
  <div class="field-group">
    <label for="owner">Kontoinhaber</label>
    <input class="text article" type="text" name="owner" id="owner" value="<?php echo htmlentities(get_user_name()); ?>">
  </div>
  <div class="field-group">
    <label for="owner">IBAN</label>
    <input class="text article" type="text" name="iban" id="iban">
  </div>
  <div class="field-group">
    <label for="amount">Betrag (&euro;)</label>
    <input class="text small-field" type="text" name="amount" id="amount" value="<?php echo format_number(get_user_attr(get_user_email(), 'balance'), false); ?>">
  </div>
  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
      <button type="submit" class="aui-button">
      <i class="fa fa-check"></i> Auszahlen
      </button>
    </div>
  </div>
</form>
