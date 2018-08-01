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

require_once('helpers/session.php');

$username = get_user_name();
$email = get_user_email();
$gravatar_url = "https://www.gravatar.com/avatar/" . md5(strtolower($email)) . "?s=35&amp;d=mm&amp;r=g";

?>

<div id="header" role="banner">
  <div class="aui-header aui-dropdown2-trigger-group" role="navigation">
    <div class="aui-header-inner">
      <div class="aui-header-primary">
        <h1 id="logo" class="aui-header-logo aui-header-logo-aui">
          <img src="<?php echo $app_url; ?>/images/<?php echo BANK_LOGO; ?>" alt="<?php echo BANK_BRAND; ?>" />
        </h1>
        <?php if ($format == 'html') { ?>
        <ul class="aui-nav">
          <li>
            <a href="/app/transactions">
              <i class="fa fa-list-alt menu-icon"></i>
              <span class="menu-text">Umsatz&uuml;bersicht</span>
            </a>
          </li>
          <li>
            <a href="/app/transfer">
              <i class="fa fa-pencil-square-o menu-icon"></i>
              <span class="menu-text">&Uuml;berweisung</span>
            </a>
          </li>
        <?php if (get_user_attr($email, 'direct_debit')) { ?>
          <li>
            <a href="/app/direct-debit">
              <i class="fa fa-cart-arrow-down menu-icon"></i>
              <span class="menu-text">Lastschrift</span>
            </a>
          </li>
        <?php } ?>
        <?php if (get_user_attr($email, 'admin') || BANK_EXT_IBAN !== null) { ?>
          <li>
            <a href="/app/deposit-payout">
              <i class="fa fa-money menu-icon"></i>
              <span class="menu-text">Ein- und Auszahlung</span>
            </a>
          </li>
        <?php } ?>
          <li>
            <a href="/app/finance-statement">
              <i class="fa fa-check-square-o menu-icon"></i>
              <span class="menu-text">Bankbilanz</span>
            </a>
          </li>
        </ul>
      </div>
      <div class="aui-navgroup-secondary">
        <ul class="aui-nav __skate" resolved="">
          <li>
            <a href="#" aria-haspopup="true" class="aui-dropdown2-trigger aui-alignment-target aui-alignment-element-attached-top aui-alignment-element-attached-right aui-alignment-target-attached-bottom aui-alignment-target-attached-right user-menu" data-container="#aui-hnav-example" aria-controls="dropdown2-nav2" aria-expanded="false" resolved="">
              <img src="<?php echo($gravatar_url); ?>" class="menu-icon" alt="<?php echo htmlentities($username); ?>"/>
              <span class="menu-text">
                <?php echo htmlentities($username); ?>
              </span>
            </a>

            <!-- .aui-dropdown2 -->
            <div id="dropdown2-nav2" class="aui-dropdown2 aui-style-default aui-layer aui-alignment-element aui-alignment-side-bottom aui-alignment-snap-right aui-alignment-element-attached-top aui-alignment-element-attached-right aui-alignment-target-attached-bottom aui-alignment-target-attached-right" aria-hidden="true" resolved="" data-aui-alignment="bottom auto" data-aui-alignment-static="true" style="z-index: 3000; top: 0px; left: 0px; position: absolute; transform: translateX(1229px) translateY(783px) translateZ(0px);">
              <ul class="aui-list-truncate">
                <li>
                  <a href="/app/profile" tabindex="-1">
                    <i class="fa fa-user"></i> &nbsp;
                    Profil
                  </a>
               </li>
               <li>
                  <a href="/app/logout" tabindex="-1">
                    <i class="fa fa-sign-out"></i> &nbsp;
                    Abmelden
                  </a>
                </li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
      <?php } ?>
    </div>
  </div>
</div>
