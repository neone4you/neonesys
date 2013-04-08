<?php
/**
 * Контроллер
 * @author neone
 */
abstract class aController {
	protected $name;
	protected $action;
	protected $arg;
	/**
	 * Template
	 * @var Template
	 */
	public $Template;
	
	abstract function indexAction();
	
	public function __construct($controller, $action, $arg = null) {
		$this->name = $controller;
		$this->action = $action;
		$this->arg = $arg;
		
		$this->Template = new Template($this->name, Template::T_VIEW, $action);
		$this->onLoad();
	}
	
	abstract function onLoad();
	
	/**
	 * Template
	 * @return Template
	 */
	public function T() {
		return $this->Template;
	}
}
