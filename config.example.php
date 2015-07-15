<?php

ini_set('include_path', ini_get('include_path') . ':' . __DIR__ . ':' . __DIR__  . '/app');

const BANK_DB_DSN = 'mysql:dbname=bank;charset=utf8';
const BANK_DB_USERNAME = 'bank';
const BANK_DB_PASSWORD = 'xxxx';

const BANK_BRAND = 'My Bank';
const BANK_LOGO = 'my-logo.png';
const BANK_DOMAIN = 'banking.example.org';

const BANK_MGMT_ACCOUNT = 'net-bank';
const BANK_POPS = [
	'net-kasse' => 'NET Kasse'
];

const BANK_EXT_ACCOUNT = 'xxxx';
const BANK_EXT_OWNER = 'xxxx';
const BANK_EXT_IBAN = 'xxxx';
const BANK_EXT_BIC = 'xxxx';
const BANK_EXT_ORG = 'xxxx';

const BANK_MAIL_FILTERS = [
	'*'
];

const BANK_SMS_URL = 'https://sms-gateway/?password=xxxx';

const BANK_MAC_SECRET = 'xxxx';

const BANK_YUBIKEY_CLIENTID = '12345';
const BANK_YUBIKEY_APIKEY = 'xxxx';

