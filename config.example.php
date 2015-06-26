<?php

ini_set('include_path', ini_get('include_path') . ':' . __DIR__ . ':' . __DIR__  . '/app');

define('BANK_DB_DSN', 'mysql:dbname=bank');
define('BANK_DB_USERNAME', 'bank');
define('BANK_DB_PASSWORD', 'xxxx');

define('BANK_BRAND', 'My Bank');
define('BANK_LOGO', 'my-logo.png');
define('BANK_DOMAIN', 'banking.example.org');

define('BANK_MGMT_ACCOUNT', 'net-bank');
define('BANK_POPS', array(
	'net-kasse' => 'NET Kasse'
));

define('BANK_EXT_ACCOUNT', 'xxxx');
define('BANK_EXT_OWNER', 'xxxx');
define('BANK_EXT_IBAN', 'xxxx');
define('BANK_EXT_BIC', 'xxxx');
define('BANK_EXT_ORG', 'xxxx');

define('BANK_MAIL_FILTERS', array('*'));

define('BANK_MAC_SECRET', 'xxxx');
define('BANK_SMS_URL', 'https://sms-gateway/?password=xxxx');

define('BANK_MAC_SECRET', 'xxxx');
