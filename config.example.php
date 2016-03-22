<?php

ini_set('include_path', ini_get('include_path') . ':' . __DIR__ . ':' . __DIR__  . '/app');

const BANK_DB_DSN = 'mysql:host=192.0.2.17;dbname=lunch_bank;charset=utf8';
const BANK_DB_USERNAME = 'lunch_bank';
const BANK_DB_PASSWORD = 'xxxx';

const BANK_BRAND = 'My Bank';
const BANK_LOGO = 'my-logo.png';
const BANK_DOMAIN = 'banking.example.org';

const BANK_MGMT_ACCOUNT = 'net-bank';
const BANK_POPS = [
  'net-kasse' => 'NET Kasse (Bargeld)',
  'net-kasse-postbank' => 'NET Kasse (Postbank)',
  'marius.gebert@netways.de' => 'Marius Gebert',
  'gunnar.beutner@netways.de' => 'Gunnar Beutner'
];

const BANK_EXT_ACCOUNT = 'net-kasse-postbank';
const BANK_EXT_OWNER = 'xxxx';
const BANK_EXT_IBAN = 'DExxxx';
const BANK_EXT_BIC = 'PBNKDEFF';
const BANK_EXT_ORG = 'Postbank Nürnberg';

const BANK_MAIL_FILTERS = [
	'*.*@netways.de'
];

const BANK_SMS_HOSTNAME = '192.0.2.5,192.0.2.23';
const BANK_SMS_USERNAME = 'xxxx';
const BANK_SMS_PASSWORD = 'xxxx';

const BANK_MAC_SECRET = 'xxxx';

const BANK_YUBIKEY_CLIENTID = 'xxxx';
const BANK_YUBIKEY_APIKEY = 'xxxx';
