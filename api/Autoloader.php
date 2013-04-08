<?php
class Autoloader {
	//private static $failed = array();
	public static function loadClass($class) {
		if(strpos($class, "Controller")>0) {
			$filename = SITE_CONTROLLERS_DIR . $class . '.php';
		} else if(strpos($class, "Model")>0) {
			$firstname = str_replace('Model', '', $class);
			$filename = SITE_MODELS_DIR . $class . '.php';			
			if(!file_exists($filename) && 's' != $firstname[strlen($firstname)-1]) {
				// XXX(neone): временно
				$filename = SITE_MODELS_DIR . $firstname . 'sModel' . '.php';
			}
		} else if(strpos($class, "View")>0) {
			$filename = SITE_VIEWS_DIR . $class . '.php';
		} else if(strpos($class, "plh")===0) {
			$filename = SITE_PLH_DIR . $class . '.php';
		} else {
			$filename = false;
		}
		
		if($filename && file_exists($filename)) {
			include $filename;
		} else {
			throw new Exception("Autoloder fail :: '{$class}'\n");
		}
	}
}


spl_autoload_register(array("Autoloader", "loadClass"));