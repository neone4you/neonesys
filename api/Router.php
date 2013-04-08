<?php
/**
 * Роутинг
 * @author neone 2013
 */
class Router {
	/**
	 * Сегменты URI текущей страницы
	 * @var array
	 */
	public static $PAGES;
	/**
	 * URI-ресурса
	 * @var string
	 */
	public static $URI;
	
	private static $init;
	private static $segments;
	
	private function __construct() {
		
	}
	
	/**
	 * Инициализировать роутер
	 * @param string $uri Принудительно использовать этот URI [opt] [default: false]
	 * @return void
	 */
	public static function init($uri = false) {
		if(self::$init) return ;
		
		self::$URI = $uri ? $uri : $_SERVER["REQUEST_URI"];
		
		self::$segments = self::parseUrl(self::$URI);
		self::$PAGES = 		self::$segments;
		
		self::$init = true;
	}
	
	/**
	 * Alias части URI ресурса
	 * @param int $segment сегмент, с 0
	 * @param mixed $default_value Значение по-умолчанию, вернётся в случае пустой ячейки URI
	 * @return string or mixed or null
	 */
	public static function Page($segment, $default_value = null) {
		if(isset(self::$segments[$segment])) return self::$segments[$segment];
		return $default_value;
	}
	
	private static function parseUrl($url) {
		$segments = array();
		$parsed_url = parse_url($url, PHP_URL_PATH);
		$parsed_url_arr = explode('/', $parsed_url);
		foreach ($parsed_url_arr as $part) {
			if(!$part) continue;
			$segments[] = $part;
		}		
		return $segments;
	}
}