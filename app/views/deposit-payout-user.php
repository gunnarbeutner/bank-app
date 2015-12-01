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
<!--  <tr>
    <th>BIC:</th>
    <td><?php echo BANK_EXT_BIC; ?></td>
  </tr>-->
  <tr>
    <th>Kreditinstitut:</th>
    <td><?php echo BANK_EXT_ORG; ?></td>
  </tr>
  <tr>
    <th>Verwendungszweck:</th>
    <td><?php echo $params['tx-code']; ?></td>
  </tr>
</table>

<p>Die Gutschrift erfolgt automatisch sobald die &Uuml;berweisung seitens der Bank best&auml;tigt wurde. Alternativ k&ouml;nnen Einzahlungen auch in bar bei Ufuk oder Gunnar get&auml;tigt werden.</p>

<h1>Auszahlung</h1>

<p>Auszahlungen werden momentan manuell ausgef&uuml;hrt. Hierf&uuml;r kann eine E-Mail an Ufuk oder Gunnar geschickt werden.</p>
