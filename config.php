<?php
/** Установка локалей ***/
setlocale(LC_ALL, "ru_RU.utf8");
date_default_timezone_set('Europe/Moscow');

/** ОКРУЖЕНИЕ */
define('IS_LOC', strpos($_SERVER["SERVER_NAME"], '.loc')>0);
define('IS_CLI', "cli" == php_sapi_name());
define('EOL', IS_CLI ? PHP_EOL : '<br>');

/** Ограничение на использование оперативной памяти */
ini_set('memory_limit', '64M');
set_time_limit(30);

if(IS_CLI) {
	set_time_limit(300);
	ini_set('memory_limit', '128M');
}

/** ДИРЕКТОРИИ */

define('SITE_DIR', dirname(__FILE__) . '/');
define('CACHE_DIR', ( SITE_DIR . 'api/cache/') . ( IS_CLI ? "cli/" : "" ));

define('SITE_API_DIR', SITE_DIR."api/");
define('SITE_LOG_DIR', SITE_DIR."logs/");

define('SITE_STATIC_DIR', SITE_DIR."static/");
define('SITE_UPLOADS_DIR', SITE_DIR.'uploads/');

define('SITE_SITE_DIR', SITE_DIR."site/");
define('SITE_CONTROLLERS_DIR', SITE_SITE_DIR."controllers/");
define('SITE_MODELS_DIR', SITE_SITE_DIR."models/");
define('SITE_VIEWS_DIR', SITE_SITE_DIR."views/");
define('SITE_PLH_DIR', SITE_SITE_DIR."placeholders/");
define('SITE_TPL_DIR', SITE_SITE_DIR."tpl/");

define('SITE_USER_LIB_DIR', SITE_SITE_DIR."lib/");

/** РЕЖИМ РАЗРАБОТЧИКА */
define('DEVELOPMENT', IS_LOC);

if (DEVELOPMENT) {
	function fb() { } //include_once(SITE_API_DIR."FirePHP/fb.php");
  error_reporting(E_ALL^E_NOTICE);
  ini_set('display_errors', 'On');
} else {
	error_reporting(E_ALL & ~E_NOTICE);
	function fb() { }
}

/** Получение параметров CLI */
if(IS_CLI) {
	include SITE_DIR . 'config_cli.php';
}

/** Инициализация сессии */
isset($_SESSION) or session_name("site");
isset($_SESSION) or session_start();