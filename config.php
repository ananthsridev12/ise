<?php
ob_start();
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
ob_end_clean();

defined('DB_HOST')    || define('DB_HOST',    'localhost');
defined('DB_NAME')    || define('DB_NAME',    'de2shrnx_intel');
defined('DB_USER')    || define('DB_USER',    'de2shrnx');
defined('DB_PASS')    || define('DB_PASS',    '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

defined('HUNTER_API_KEY')  || define('HUNTER_API_KEY',  '');
defined('ADZUNA_APP_ID')   || define('ADZUNA_APP_ID',   '');
defined('ADZUNA_APP_KEY')  || define('ADZUNA_APP_KEY',  '');

defined('APP_NAME')       || define('APP_NAME',       'ISE - Intent Signal Engine');
defined('ITEMS_PER_PAGE') || define('ITEMS_PER_PAGE', 25);
