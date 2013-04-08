<?php
/**
 * Ошибка приложения
 */
class AppException extends Exception {}
/**
 * Ошибка при работе с шаблонизатором
 */
class TemplateException extends Exception {}
/**
 * Не полностью осуществлена реализация одного из интерфейсов
 */
class InterfaceRealisationException extends Exception {
	/**
	 * 
	 * @param string $message Имя интерфейса [optional]
	 * @param unknown_type $code Объект, из метода которого совершается вызов [optional]
	 * @param unknown_type $previous [optional]
	 */
	/*public function __construct($message = null, $code = null, $previous = null) {
		if($message) {
			if(is_object($message)) {
				$this->message = "Для работы с методом Класса " . get_class($message) . "";
			} else {
				
			}
		}
	}*/
}
/**
 * Ошибка при работе с базой данных
 */
class DB_Exception extends Exception {}
/**
 * Ошибка при обработке данных форм
 */
class FormException extends Exception{}