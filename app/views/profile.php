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

<h1>Kundendaten</h1>

<?php if ($params['force']) { ?>
<p>Bitte setzen Sie ein neues Passwort f&uuml;r Ihr Konto.</p>
<?php } ?>

<form method="post" action="/app/profile" class="aui">
  <div class="field-group">
    <label for="name">
      Name
    </label>
    <span id="name"><?php echo htmlentities(get_user_name()); ?></span>
  </div>

  <div class="field-group">
    <label for="email">
      E-Mailadresse
    </label>
    <span id="email"><?php echo htmlentities(get_user_email()); ?></span>
  </div>

  <div class="field-group">
    <label for="email">
      Mobilfunknummer
    </label>
    <span id="email"><?php echo htmlentities(get_user_attr(get_user_email(), 'phone')); ?></span>
  </div>

<?php if (!$params['force']) { ?>
  <div class="field-group">
    <label for="old-password">
      Altes Passwort
    </label>
    <input type="password" class="password" maxlength="128" name="old-password" id="old-password"></input>
  </div>
<?php } ?>

  <div class="field-group">
    <label for="password1">
      Passwort
    </label>
    <input type="password" class="password" maxlength="128" name="password1" id="password1"></input>
  </div>

  <div class="field-group">
    <label for="password2">
      Passwort (wdh.)
    </label>
    <input type="password" class="password" maxlength="128" name="password2" id="password2"></input>
  </div>

<!--  <div class="field-group">
    <label for="direct-debit">
    </label>
    <input type="checkbox" class="checkbox" name="direct-debit" value="yes" id="direct-debit"<?php if ($params['direct-debit']) { ?> checked="checked"<?php } ?>>Abbuchung per Lastschrift erlauben</input>
  </div> -->

  <div class="buttons-container">
    <div class="buttons">
      <input type="hidden" name="direct-debit" value="yes"></input>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>"></input>
      <input class="button submit" type="submit" value="Speichern"></input>
    </div>
  </div>
</form>
