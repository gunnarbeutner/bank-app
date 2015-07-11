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

$app_url = 'https://' . BANK_DOMAIN;

?><?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo BANK_BRAND; ?></title>
<?php if ($format == 'html') { ?>
  <script src="/vendor/components/jquery/jquery.min.js" type="text/javascript"></script>
  <script src="/vendor/atlassian/aui/aui/js/aui.min.js" type="text/javascript"></script>
  <script src="/vendor/atlassian/aui/aui/js/aui-experimental.min.js" type="text/javascript"></script>
  <script src="/vendor/atlassian/aui/aui/js/aui-soy.min.js" type="text/javascript"></script>
  <link rel="stylesheet" href="<?php echo $app_url; ?>/vendor/components/font-awesome/css/font-awesome.min.css"></link>
  <link rel="stylesheet" href="<?php echo $app_url; ?>/vendor/atlassian/aui/aui/css/aui.min.css" media="all"></link>
  <link rel="stylesheet" href="<?php echo $app_url; ?>/vendor/atlassian/aui/aui/css/aui-experimental.min.css" media="all"></link>
  <link rel="stylesheet" href="<?php echo $app_url; ?>/css/aui-theme.css" media="all"></link>
<?php } ?>
  <link rel="stylesheet" href="<?php echo $app_url; ?>/css/application.css"></link>
  <link rel="shortcut icon" href="<?php echo $app_url; ?>/favicon.ico"></link>
  <link rel="apple-touch-icon" href="<?php echo $app_url; ?>/favicon.ico"></link>
  <link rel="apple-touch-startup-image" href="<?php echo $app_url; ?>/favicon.ico"></link>
  <meta name="apple-mobile-web-app-capable" content="yes"></meta>
</head>
<body class="aui-page-focused aui-page-focused-xlarge">
  <div id="page">
  <?php
	if (is_logged_in()) {
		require_once('views/menu.php');
  	}
  ?>
  </div>
  <div id="content">
    <div class="aui-page-panel" role="main">
      <div class="aui-page-panel-inner">
        <div class="aui-page-panel-content">
<?php require_once('../app/views/' . $view . '.php'); ?>
        </div>
      </div>
    </div>
  </div>

  <script src="/vendor/twitter/typeahead.js/dist/typeahead.bundle.min.js" type="text/javascript"></script>
  <script src="/vendor/components/handlebars.js/handlebars.min.js" type="text/javascript"></script>
  <script src="/js/md5.min.js" type="text/javascript"></script>
  <script src="/js/application.js" type="text/javascript"></script>
</body>
</html>
