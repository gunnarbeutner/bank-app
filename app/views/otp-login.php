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

?>
<h1>Two-Factor-Authentication</h1>

<p>F&uuml;r diesen Account ist Two-Factor-Authentication aktiviert. Bitte geben Sie ein OTP-Token ein, um sich anzumelden.</p>

<form method="post" action="/app/login" class="aui">
  <div class="field-group">
    <label for="email">
      E-Mailadresse
    </label>
    <span><?php echo htmlentities($params['email']); ?></span>
  </div>

  <div class="field-group">
    <label for="email">
      OTP-Token
    </label>
    <input type="text" class="text" maxlength="128" name="otp-token" id="otp-token" required="required"></input>
  </div>

  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="email" value="<?php echo htmlentities($params['email']); ?>"></input>
      <input type="hidden" name="password" value="<?php echo htmlentities($params['password']); ?>"></input>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"></input>
      <input type="submit" class="button submit" value="Anmelden"></input>
    </div>
  </div>
</form>

<script type="text/javascript">
  $('#otp-token').focus();
</script>
