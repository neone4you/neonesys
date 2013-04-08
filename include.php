<?php
$dir = dirname(__FILE__);

include $dir . '/access.php';
include $dir . '/config.php';
include $dir . '/api/include.php';

include SITE_SITE_DIR . 'lib/linkososApi.php';

/**
 * Инициализация базы данных
 */
Database::init($_db_config["host"], $_db_config["dbname"], $_db_config["login"], $_db_config["password"]);
Database::query("SET NAMES 'utf8'");