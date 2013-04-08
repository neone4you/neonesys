<?php

/**
 * Устанавливаем хранилище сессий
 */
//ini_set('session.save_path', $sessPath);

/**
 * Устанавливаем глобальный массив $_SERVER
 */
$_SERVER = array(
	"HTTP_HOST" =>  "www.linkosos.ru" ,
	"HTTP_USER_AGENT" =>  "Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15 CronManager" ,
	"HTTP_ACCEPT" =>  "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8" ,
	"HTTP_ACCEPT_LANGUAGE" =>  "ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3" ,
	"HTTP_ACCEPT_ENCODING" =>  "gzip,deflate" ,
	"HTTP_ACCEPT_CHARSET" =>  "windows-1251,utf-8;q=0.7,*;q=0.7" ,
	"HTTP_KEEP_ALIVE" =>  "115" ,
	"HTTP_CONNECTION" => "keep-alive" ,
	"PATH" =>  "/sbin:/usr/sbin:/bin:/usr/bin" ,
	"SERVER_SIGNATURE" =>  "" ,
	"SERVER_SOFTWARE" =>  "Apache" ,
	"SERVER_NAME" => "www.linkosos.ru" ,
	"SERVER_ADDR" => "84.204.80.90" ,
	"SERVER_PORT" =>  "80" ,
	"REMOTE_ADDR" =>  "127.0.0.7" ,
	"DOCUMENT_ROOT" =>  SITE_DIR,
	"SERVER_ADMIN" => "info@linkosos.ru" ,
	"REMOTE_PORT" => "56142" ,
	"GATEWAY_INTERFACE" => "CGI/1.1" ,
	"SERVER_PROTOCOL" => "HTTP/1.1" ,
	"REQUEST_METHOD" => "GET" ,
	"QUERY_STRING" =>  ""
);


/**
 * Разбор параметров
 * @author km 2012
 * @deprecated Есть встроенная функция PHP: getopt()
 * Примеры: 
 * 	--debug 						> $_REQUEST['debug'] = true
 *  --location=spb			> $_REQUEST['location'] = 'spb'
 *  --exlcude="-3 DAYS" > $_REQUEST['exlcude'] = '-3 DAYS'
 */
if($argc>1 && empty($_REQUEST)) {
	$_REQUEST = array();
	$_REQUEST['is-cli'] = true;
	foreach ($argv as $param) {
		if(stripos($param, '--')!==0) continue;
		
		$eq_pos = stripos($param, '=');
		$key_len = $eq_pos ? $eq_pos-2 : false;
		$val_start_pos = $eq_pos ? $eq_pos + 1 : false;
		
		$key = $key_len ? substr($param, 2, $key_len) : substr($param, 2);
		$key = trim($key);
		$val = $key && $val_start_pos ? trim(substr($param, $val_start_pos)) : true;
		
		$_REQUEST[$key] = $_GET[$key] = $val;
	}
} else {
	$_REQUEST['is-cli'] = false;
}

	
/**
 * Получаем имя скрипта вызвавшего include/require и текущую директорию
 */
$trace = debug_backtrace();
preg_match('#^(.*)\/(.*?)$#', $trace[0]['file'], $caller_match);

$path = $caller_match[1];
set_include_path($path);

$script_name = $caller_match[2];
$_SERVER["REQUEST_URI"] = $script_name;