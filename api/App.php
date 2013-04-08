<?php
/**
 * Приложение (MVC-модель)
 * @author neone 2013
 */
class App {
	private function __construct() {}
	private static $exec_data = array();
	
	/**
	 * Инициализация приложения
	 * Установка роутинга
	 */
	public static function init() {
		Router::init();
		self::logic(Router::Page(0, 'index'), Router::Page(1, 'index'), Router::Page(2, null));
	}
	
	private static function logic($PAGE_0, $PAGE_1 = null, $PAGE_2 = null) {
		$predefined_rules = self::checkPredefinedRules($PAGE_0, $PAGE_1, $PAGE_2);
		if(is_array($predefined_rules)) {
			switch ($predefined_rules[0]) {
				case 'include':
					include SITE_DIR . $predefined_rules[1];
					die();
					break;
				case 'controller':
					self::execController($predefined_rules[1], $predefined_rules[2], $predefined_rules[3]);
					break;
			}
		}
		self::setController($PAGE_0, $PAGE_1, $PAGE_2);		
	}
	
	/**
	 * Предустановленные правила обработки зарезервированных страниц
	 * @return array or false
	 */
	private static function checkPredefinedRules($PAGE_0, $PAGE_1 = null, $PAGE_2 = null) {
		if(!$PAGE_0) return false;
		
		$_rules = array(
			'json' => array('include', "/site/json/{$PAGE_1}.php"),
			'cron' => array('include', "/site/cron/{$PAGE_1}.php"),
		);
		
		if(isset($_rules[$PAGE_0])) {
			return $_rules[$PAGE_0];
		}
		
		return false;
	}
	
	/**
	 * Выполнить загрузку приложения по предустановленном контроллеру
	 * Подгружается предустановленный контроллер
	 */
	public static function exec() {
		self::execController(self::$exec_data['controller'], self::$exec_data['action'], self::$exec_data['arg']);
	}
	
	/**
	 * Установить/переопределить контроллер
	 * @param string $controller Контроллер
	 * @param string $action Действие
	 * @param mixed $arg Аргументы
	 * @return void
	 */
	public static function setController($controller, $action, $arg = null) {
		self::$exec_data['controller'] = $controller;
		self::$exec_data['action'] = $action;
		self::$exec_data['arg'] = $arg;
	}
	
	/**
	 * Выполнить загрузку контроллера
	 * @param string $controller Контроллер
	 * @param string $action Действие
	 * @param mixed $arg Аргументы
	 * @throws AppException
	 * @return aController
	 */
	public static function execController($controller, $action, $arg = null) {
		$controller_name = mb_strtolower($controller, "utf-8") . "Controller";
		$method_name = 		 mb_strtolower($action, "utf-8") . "Action";
		
		$Controller = new $controller_name($controller, $action, $arg);
		
		if(!method_exists($Controller, $method_name)) {
			throw new AppException("Method {$method_name} fail load.");
		}
		
		$Controller->$method_name($arg);
		return $Controller;
	}
	
	/**
	 * Установить страницу авторизации
	 * @param boolean $conclusion Условие, не пройдя которое вы попадаете на установленный через этот же метод контроллер
	 * @param string $controller Контроллер
	 * @param string $action Действие
	 * @return boolean Результат условия
	 */
	public static function securityPage($conclusion, $controller, $action) {
		$conclusion = !!$conclusion;
		if(!$conclusion) {
			self::setController($controller, $action);
		}
		return $conclusion;
	}
}


class AppRules {
	private function __construct() {}
	
}