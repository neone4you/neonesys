<?php
/**
 * Шаблонизатор
 * @author neone
 * @version 0.1124 cut edition ( neone )
 */
class Template {
	
	const T_TPL = 110;
	const T_VIEW = 111;
	const T_PLH = 112;
	
  private $dir;
  private $_tpl_dir;
  private $_plh_dir;
  private $_view_dir;
  
  private $_name;
  private $_type;
  private $_action;
  private $content;
  
  private static $js = array();
  private static $css = array();
  private static $jsVars = array();
  
  private static $tpl;  
  private static $title;
  private static $templates = array();
  
	/**
	 * Конструктор класса.
	 */
  public function __construct($name, $type, $action = '') {
  	$this->dir = SITE_TPL_DIR;
  	$this->_view_dir = SITE_VIEWS_DIR;
  	
  	$this->_name = $name;
  	$this->_action = $action;
  	$this->_type = $type;
  	
  	$this->ft_load();
  	self::addTemplate($this);  	
  }

	public static function addTemplate(Template $Template) {
		self::$templates[] = $Template;
	}
	
	/**
	 * Обработать шаблон и вывести
	 * @throws TemplateException
	 * @return void
	 */
	public static function showPage() {
		//XXX: полностью менять логику - тут всё временно		
		$tpl_filename = SITE_TPL_DIR . self::$tpl . '.tpl';
		if(!self::$tpl || !file_exists($tpl_filename)) {			
			throw new TemplateException("Целевой шаблон отсутствует, либо не установлен!");
		}
		
		$tpl_content = file_get_contents($tpl_filename);
		
		$tpl_content = self::ft_if($tpl_content, array(
				'has-js-vars' => !!self::getJsVars()
		));
		
		$tpl_content = self::ft_array($tpl_content, array(
			'head-title' => self::$title,
			'js_vars' => self::getJsVars(true),
			'view' => (count(self::$templates) ? self::$templates[0]->getHtml() : '')
		));
		
		echo $tpl_content;
	}
  
	public static function setTemplate($template, $only_not_installed = false) {
		if($only_not_installed && self::$tpl) {
  		return;
		}
		self::$tpl = $template;
  }
	
  public static function setTitle($title) {
  	self::$title = $title;
  }
  
  /**
   * Подключить JS-скрипт на страницу
   * @param string $src Путь к js-скрипту, относительный ( etc.: /js/jquery.1.3.1.js )
   * @return void
   */
  public static function addJS($src) {
  	self::$js[] = $src;
  }
  /**
   * Подключить CSS (таблицу стилей) на страницу
   * @param string $src Путь к файлу стилей, относительный ( etc.: /css/style.css )
   * @return void
   */
  public static function addCSS($src) {
  	self::$css[] = $src;
  }
  /**
   * Список js-скриптов, подлключенных на странице
   * @return array
   */
  public static function getListJS() {
  	return self::$js;
  }
  /**
   * Список css-стилей, подлключенных на странице
   * @return array
   */
  public static function getListCSS() {
  	return self::$css;
  }
  
  /**
   * Конвертирует значение примитивного типа из PHP в JS-представление
   * Например: array(2, "asd", true) => [2, "asd", 1]
   * @param mixed $value
   * @return string
   */
  private static function convertPhpToJsValue($value) {
  	if(is_array($value)) {
  		$asJsArray = array();
  		$asJsStr = "{";
  		foreach ($value as $key => $val) {
  			$val = self::convertPhpToJsValue($val);
  			$asJsArray[] = "\"{$key}\": {$val}";
  		}
  		$asJsStr .= implode(', ', $asJsArray);
  		$asJsStr .= "}";
  		return $asJsStr;  		
  	} elseif (is_numeric($value)) {
  		$val = intval($value);
  	} elseif (is_string($value)) {
  		$val = '"' . strval($value) . '"';
  	} else {
  		$val = (int)!!$value;
  	}
  	
  	return $val;
  }
  
  /**
   * Установить js-переменную для вывода на фронтэнде
   * Значение может иметь любой примитивный тип (int, boolean, array, string)
   * @param string $key Ключ значения
   * @param mixed $value Массив-значение
   */
  public static function setJsVar($key, $value) {
  	self::$jsVars[$key] = $value;
  }
  
  /**
   * Получить переменную, установленную для вывода на фронтэнде
   * @param string $key Ключ значения
   * @param boolean $asJS Вернуть значение форматированное для вывода в скрипт JS [optional] [default: false]
   * @return mixed or false
   */
  public static function getJsVar($key, $asJS = false) {
  	if(!isset(self::$jsVars[$key])) return false;
  	$value = self::$jsVars[$key];
  	$value = $asJS ? self::convertPhpToJsValue($value) : $value;
  	return $value;
  }
  
  /**
   * Получить массив данных установленный для передачи на фронтэнд
   * @param boolean $asJS Форматировать значения в вид для вывода в JS скриптах [optional] [default: false]
   * @return array or string
   */
  public static function getJsVars($asJS = false) {
  	if($asJS) {
  		return self::convertPhpToJsValue(self::$jsVars);
  	} else {
  		return self::$jsVars;
  	}
  }

  private function getFilename() {
  	switch ($this->_type) {
  		case self::T_TPL:
  			//XXX дописать
  		break;
  		case self::T_VIEW:
  			return $this->_view_dir . $this->_name . '_' . $this->_action . '.tpl';
  		break;
  		case self::T_PLH:
  			
  		break;
  	}
  	return false;
  }
  
  /**
   * Загрузить шаблон в память
   * @return boolean
   */
  public function ft_load() {
  	$full_filename = $this->getFilename();  	
	  $content = file_get_contents($full_filename);
    
    if(!$content) {
    	/* XXX нет контента */
    	return false;
    }
    
    $this->content = $content;
    return true;
  }
  
  /**
   * Вставить массив данных в шаблон
   * @param array $arrVars Массив данных ( ключ => значение )
   * @return void
   */
  public function set_array(array $arrVars) {
  	$this->content = self::ft_array($this->content, $arrVars);
  }
  
  /**
   * Установить переменную шаблона
   * @param string $key
   * @param string $data
   * @return void
   */
  public function set_var($key, $data) {
  	$this->content = self::ft_array($this->content, array($key => $data));
  }
  
  /**
   * Вставить массив данных в шаблон
   * @param array $arrVars Массив данных ( ключ => значение )
   * @return boolean
   */
  public static function ft_array($content, array $arrVars) {
  	foreach ($arrVars as $key => $value) {
  		$content = str_replace('{'.$key.'}', $value, $content);
  	}
    return $content;
  }
  
  /**
   * Обработать блоки <if> в шаблоне
   * @param array $arrIf Массив с данными
   */
	public function set_if(array $arrIf) {
		$this->content = self::ft_if($this->content, $arrIf);
	}
  
  /**
   * Обработать блоки <if> в шаблоне
   * @param array $arrIf Массив с данными
   */
	public static function ft_if($content, array $arrIf) {
    while (strpos($content, '<if ') !== false) {
      $pos1 = strpos($content, '<if ');
      $pos2 = strpos($content, '>', $pos1);

      $ifname = substr ($content,$pos1+4,$pos2-$pos1-4);
      $ifcode = substr ($content,$pos2+1,strpos($content,'</if '.$ifname.'>',$pos2)-$pos2-1);

      $newcode = $ifcode;
      $ifname_ = substr($ifname, 1);
      
      if ('!' == @$ifname[0] && !@$arrIf[$ifname_]) {
        $arrIf[$ifname] = true;
      }

      if (@$arrIf[$ifname]) {
        foreach ($arrIf as $key => $value) {
            $newcode = str_replace('{'.$key.'}', $value, $newcode);
        }
      } else {
        $newcode='';
      }

      $content = str_replace('<if '.$ifname.'>'.$ifcode.'</if '.$ifname.'>', $newcode, $content);
    }
    return $content;
  }
	
  /**
	 * Обработать (инициализировать) <loop> блоки в шаблоне
	 * @param string $strLoopName Ключ <loop key>
	 * @param array $arrVars Массив с данными
	 */
	public function set_loop($strLoopName, array $arrVars) {
		$this->content = self::ft_loop($this->content, $strLoopName, $arrVars);
	}
  
	/**
	 * Обработать (инициализировать) <loop> блоки в шаблоне
	 * @param string $strLoopName Ключ <loop key>
	 * @param array $arrVars Массив с данными
	 */
	public static function ft_loop($content, $strLoopName, array $arrVars) {
		
		$control = 1;
    $loopcode = '';

    $n = count($arrVars);
    $pos1 = strpos($content, '<loop '.$strLoopName.'>') + strlen('<loop '.$strLoopName.'>');
    $pos2 = strpos($content, '</loop '.$strLoopName.'>');

    $loopcode = substr($content, $pos1, $pos2-$pos1);

    $tag1 = substr($content, strpos($content, '<loop '.$strLoopName.'>'), strlen('<loop '.$strLoopName.'>'));
    $tag2 = substr($content, strpos($content, '</loop '.$strLoopName.'>'),strlen('</loop '.$strLoopName.'>'));

    if($loopcode != ''){
      $newcode = '';

      if (is_array($arrVars)) {
        foreach($arrVars as $row){
          $tempcode = $loopcode;
          foreach ($row as $key=>$value) {
            if (!is_array($value))
              $tempcode = str_replace('{'.$key.'}', $value, $tempcode);

            if (strpos($tempcode,"<if $key") !== false || strpos($tempcode,"<if !$key") !== false) {

              if ($value) {
                $tempcode=preg_replace("|<if $key>(.*)</if $key>|Ums","\\1",$tempcode);
                $tempcode=preg_replace("|<if !$key>(.*)</if !$key>|Ums","",$tempcode);
              } else {
                $tempcode=preg_replace("|<if $key>(.*)</if $key>|Ums","",$tempcode);
                $tempcode=preg_replace("|<if !$key>(.*)</if !$key>|Ums","\\1",$tempcode);
              }
            }
          }
          $newcode .= $tempcode;
          $control++;
        }
      }

      $content = str_replace($tag1.$loopcode.$tag2, $newcode, $content);
    }
    
    return $content;
	}
	
	/**
   * Зачистить <loop> фрагменты в шаблоне
   */
	/*private function strip_loops() {
    preg_match_all("<loop ([0-9A-Za-z_\.]+)>", $this->content, $matches, PREG_SET_ORDER);
    if(empty($matches)) return;
    
    foreach ($matches as $match) {
	    $key = $match[1];
	    $first_phrase = "<loop {$key}>";
	    $last_phrase = 	"</loop {$key}>";
	    $first_pos = 	strpos($this->content, $first_phrase);
	    $last_pos = 	strpos($this->content, $last_phrase);
	    
	    if($first_pos===false || $last_pos === false) continue;
	    $last_pos += strlen($last_phrase);
	    
	    $this->content = substr_replace($this->content, '', $first_pos, $last_pos-$first_pos);
    }
	}*/
  
  public function clearVars() {
  	if(!$this->content) return;
  	$this->content = preg_replace("/{[_a-z0-9\-\.]+}/i", "", $this->content);
  }
  
  public function getHtml() {
  	$this->clearVars();
  	return $this->content;
  }
  
  public function show() {
  	echo $this->content;
  }

}



/**
 * Системные сообщения ( выпадающая плашка сверху )
 * @author km 2012
 */
class SystemMessage {
	/**
	 * Ключ массива, в котором хранится стэк сообщений
	 * Незабудь! При изменении имени потребуется изменить имя ключа и на фронтэнде!
	 * @var string
	 */
	const KEY = 'sm';
	const SM_ERROR = 'error';
	const SM_NOTICE = 'notice';
	const SM_SUCCESS = 'success';
	const SM_MESSAGE = 'message';

	/**
	 * Вывести извещение определенного типа после загрузки страницы
	 * @param string $type Тип извещения
	 * @param string $text Текст извещения
	 * @see SystemMessage::SM_*
	 * @return void
	 */
	private static function show($type, $text) {
		$stack = Template::getJsVar(self::KEY);
		$stack or $stack = array();

		$push = array();
		$push['type'] = $type;
		$push['text'] = $text;

		$stack[] = $push;
		Template::setJsVar(self::KEY, $stack);
	}

	/**
	 * Вывести всплывающую ошибку после загрузки страницы
	 * @param string $text Текст ошибки
	 */
	public static function Error($text) {
		self::show(self::SM_ERROR, $text);
	}
	/**
	 * Вывести всплывающее уведомление об успешно завершенной операции после загрузки страницы
	 * @param string $text Текст уведомления
	 */
	public static function Success($text) {
		self::show(self::SM_SUCCESS, $text);
	}
	/**
	 * Вывести всплывающее сообщение после загрузки страницы
	 * @param string $text Текст сообщения
	 */
	public static function Message($text) {
		self::show(self::SM_MESSAGE, $text);
	}
	/**
	 * Вывести всплывающее уведомление после загрузки страницы
	 * @param string $text Текст уведомления
	 */
	public static function Notice($text) {
		self::show(self::SM_NOTICE, $text);
	}
}


/**
 * Мини-шаблонизатор
 */
abstract class aPlaceholder {
	/**
	 * @return string
	 */
	abstract function getHtml();
}