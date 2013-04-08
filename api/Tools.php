<?php
/**
 * Различные утилиты
 * @author Mikhail.Pogrebnikov
 *
 */
class Tools {
	const GB = "Гб";
	const MB = "Мб";
	const KB = "Кб";
	const B = "б";
	
	/**
	 * Склонение по числам для русского языка
	 * http://www.gnu.org/software/gettext/manual/html_mono/gettext.html#Plural-forms
	 * @param integer $n
	 * @param string $form0 $n заканчивается на 1, но не на 11
	 * @param string $form1 $n заканчивается на 2, 3 или 4, но не 12, 13 или 14
	 * @param string $form2 $n заканчивается на 5-20
	 * @return string
	 */
	public static function rus_ngettext($n, $form0, $form1, $form2)
	{
		$w = array($form0, $form1, $form2);
		return $w[self::numeral($n)];
	}
	/*
	 * Существительное во множественном числе
	 */
	public static function rus_plural($noun) {
		$arrayVowel = array("у", "е", "ы", "а", "о", "э", "я", "и", "ю");
		$arrayConsonant = array("й", "ц", "к", "н", "г", "ш", "щ", "з", "х", "ъ", "ф", "в", "п", "р", "л", "д", "ж", "ч", "с", "м", "т", "б", "ь");
		$lastword = mb_substr($noun, strlen($noun)-2);
		$plural = $noun;
		switch ($lastword) {
			case "р":
				$lastplural = 'ы';
				$plural = mb_substr($noun, 0, strlen($noun)).$lastplural;
				break;
			case "к":
				$lastplural = 'и';
				$plural = mb_substr($noun, 0, strlen($noun)).$lastplural;
				break;
		}
		return $plural;
	}
	/**
	 * Склонение слова по числам
	 * Возвращает 0, 1 или 2
	 * @param integer $n
	 * @return integer
	 */
	public static function numeral($n)
	{
		$n = abs($n);
		/*
		 * 0 коров return (2)
		 * 1 корова return (0)
		 * 2, 3, 4 коровы return (1)
		 * 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20 коров -> (2)
		 * 21 корова -> 0
		 */
		return ($n%10==1 && $n%100!=11) ? 0 :
		(($n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20)) ? 1 : 2);
	}
	/**
	 * Генерит UID типа {2C28542E-5971-4561-A81E-C1657DED08C2}
	 * @param $parenthesis обрамить фигурными скобками [optional] [default: false]
	 * @return string
	 */
	public static function uid($parenthesis = false)
	{
		$ss = array();
		foreach (array(8, 4, 4, 4, 12) as $n) {
			$s = "";
			for ($i = 0; $i < $n; $i++) $s.= dechex(rand(0, 15));
			$ss[] = $s;
		}
		$ss = strtoupper(implode("-", $ss));
		return $parenthesis ? "{".$ss."}" : $ss;
	}
	/**
	 * Вырезает из строки пробелы и заменяет запятую на точку: 1 000,00 -> 1000.00
	 * @param $string
	 * @return double
	 */
	public static function string2number($string)
	{
		$string = str_replace(' ', '', $string);
		$string = str_replace(',', '.', $string);
		$string = floatval($string);
		/* Была какая-то хрень, из-за локали на сервере floatval может ставить разделитель не точку, а запятую */
		$string = str_replace(',', '.', $string);
		return $string;
	}
	/**
	 * Человеческое представление размера файла
	 * @param integer $val
	 * @return string
	 */
	public static function int2filesize($val)
	{
		$answ = array();
		$pref = array("Gb"=>1024*1024*1024, "Mb"=>1024*1024, "Kb"=>1024, "b"=>1);
		$value = $val;
		foreach ($pref as $measure=>$rate) {
			$answ[$measure] = (integer)($value / $rate);
			$value-= $answ[$measure] * $rate;
		}
		$val = false;
		foreach ($answ as $measure=>$value) {
			if ($value || $val) {
				if (!$val) {
					$val = $value;
					$mea = $measure;
				} else {
					$dec = $value / 1024;
					$val+= $dec;
					$val = (integer)($val * 100);
					$val = ($val / 100);
					break;
				}
			}
		}
		if ($val) {
			$val = number_format($val, $dec * 100 < 1 ? 0 : 2, ",", " ");
			$val.= " ".($mea == "Gb" ? self::GB :
			($mea == "Mb" ? self::MB :
			($mea == "Kb" ? self::KB : self::B)));
		}
		return $val;
	}
	/**
	 * Генератор случайного пароля
	 * @param integer $len длина пароля
	 * @return string
	 */
	public static function generatePassword($len = 8)
	{
		$vowel = array("a", "e", "i", "o", "u", "y");
		$conso = array("b","c","d","f","j","h","j","k","l","m","n","p","q","r","s","t","w","v","x","z");
		$v = count($vowel) - 1;
		$c = count($conso) - 1;
		for($i = 0, $pass = ""; $i < $len; $i++) {
			$pass.= ($i % 2) ? $vowel[rand(0, $v)] : $conso[rand(0, $c)];
		}
		return $pass;
	}
	/**
	 * Формирует JSON формат из массива
	 * @param $arr array
	 * @return string
	 */
	public static function JSON($arr)
	{
		if (function_exists('json_encode')) {
			return json_encode($arr); //Lastest versions of PHP already has this functionality.
		} else {
			throw new Exception('Ask Google for "jsonwrapper" to get json_encode() functionality');
		}
	}

	/**
	 * Подготавливает массив для вывода в качестве OPTION листа
	 *
	 * @param $array array исходные данные
	 * @param $key string имя поля с идентификатором
	 * @param $val string имя поля со значением
	 * @param $id mixed выбраный идентификатор или их массив
	 * @param $zero string нулевое значение
	 * @return array
	 */
	public static function makeOptions($array, $key, $val, $id, $zero = false)
	{
		$data = array();
		if (!is_array($id)) $id = array($id);
		if ($zero) $data[] = array("value"=>0, "caption"=>$zero, "selected"=>in_array(0, $id) ? 'selected="selected"' : "", "parity"=>self::parity(0));
		foreach ($array as $i => $rows) {
			$data[] = array(
        "value"=>$rows[$key],
        "caption"=>$rows[$val],
        "selected"=>in_array($rows[$key], $id) ? 'selected="selected"' : ""
        );
		}
		return self::paritate($data);
	}
	/**
	 * Подготавливает массив для вывода в качестве INPUT[TYPE=RADIO] списка
	 *
	 * @param $array array исходные данные
	 * @param $key string имя поля с идентификатором
	 * @param $val string имя поля со значением
	 * @param $id mixed выбраный идентификатор или их массив
	 * @param $zero string нулевое значение
	 * @return array
	 */
	public static function makeRadio($array, $key, $val, $id, $zero = false)
	{
		$arr = self::makeOptions($array, $key, $val, $id, $zero);
		foreach ($arr as $i=>$a) {
			$arr[$i]['checked'] = $a['selected'] ? 'checked="checked"' : '';
		}
		return $arr;
	}
	/**
	 * Подготавливает массив для вывода в качестве INPUT[TYPE=CHECKBOX] списка
	 * @see self::makeRadio()
	 * @return array
	 */
	public static function makeCheckbox($array, $key, $val, $id, $zero = false)
	{
		$arr = self::makeOptions($array, $key, $val, $id, $zero);
		foreach ($arr as $i=>$a) {
			$arr[$i]['checked'] = $a['selected'] ? 'checked="checked"' : '';
		}
		return $arr;
	}
	/**
	 * По умному обрезает большие блоки текста
	 * @param $text
	 * @param $lines максимальное количество строк
	 * @param $symbols максимальное количество символов
	 * @param $cut резать даже посередине предложения
	 * @return string
	 */
	public static function trim($text, $lines = false, $symbols = false, $cut = false)
	{
		$text = str_replace('&nbsp;', ' ', trim($text));
		if (!$lines && !$symbols) return $text;

		if (!$lines) {
			$strings = preg_split("#[\.\n!?…]\s*[^a-zа-я]#", $text);

			$n = 0;
			foreach ($strings as $string) {
				$n+= mb_strlen($string, 'utf8');
				if ($n > $symbols) break;
				$n+= 0;
			}
			if ($n <= $symbols) return $text;

			$p = array();

			$p[] = mb_strpos($text, ".", $n, 'utf8');
			$p[] = mb_strpos($text, "\n", $n, 'utf8');
			$p[] = mb_strpos($text, "!", $n, 'utf8');
			$p[] = mb_strpos($text, "?", $n, 'utf8');
			$p[] = mb_strpos($text, "…", $n, 'utf8');
			$p[] = mb_strpos($text, ",", $n, 'utf8');

			foreach ($p as $i=>$t) if ($t === false) unset ($p[$i]);
			$p = (integer)@min($p);

			// Смотрим последний символ (если запятая - делаем многоточие)
			$end_symbol = mb_substr($text, $p, 1, 'utf8');

			if($end_symbol == ',') {
				$string = $p ? mb_substr($text, 0, $p, 'utf8')."..." : $text;
			} else {
				$string = $p ? mb_substr($text, 0, $p + 1, 'utf8') : $text;
			}

			return $string;
		} else
		if (!$symbols) {
			$strings = explode("\n", $text);
			$out = array();
			foreach ($strings as $string) {
				if ($lines) $out[] = $string;
				$lines--;
			}
			return implode("\n", $out);
		} else {
			$text = self::trim($text, $lines, false);
			$n = 0; $m = 0; $out = array();
			$perline = $symbols / $lines;
			$strings = explode("\n", $text);
			foreach ($strings as $string) {
				$len = mb_strlen($string, 'utf8');
				$n = $len < $perline ? $perline : $len;
				if (($m + $n) > $symbols) {
					$limit = $symbols - $m;
					$out[] = self::trim($string, false, $limit);
					break;
				} else {
					$out[] = $string;
				}
				$m+= $n;
			}
			return implode("\n", $out);
		}
	}
	/**
	 * Форматирует строку по правилам заголовка
	 * @param $str
	 * @return string
	 */
	public static function titleize($str)
	{
		//Реализуем  mb_ucfirst();
		mb_internal_encoding('utf-8');
		$fl = mb_substr($str, 0, 1);
		$el = mb_substr($str, 1);
		$fl = str_replace(
		array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
      'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y',
      'z', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к',
      'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч',
      'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'),
		array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
      'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y',
      'Z', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К',
      'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч',
      'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'),
		$fl);
		$str = $fl.$el;

		// Убираем точку в конце
		$fl = mb_substr($str, 0, -1);
		$el = mb_substr($str, -1);
		$str = $el == '.' ? $fl : $fl.$el;

		return $str;
	}
	/**
	 * Голый хост
	 * @param string $url
	 * @return string
	 */
	public static function prepareHost($url) {
		$host = strip_tags($url);
		
		if(preg_match("#(\d+).(\d+).(\d+).(\d+)#", $host, $match)) {
			return $match[0];
		}
		
		$host = str_replace(array('http://', 'https://'), '', $host);
		$host = preg_replace('#\/.*#', '', $host);
		return $host;
	}
	/**
	 * Извлекает из url имя домена
	 * @param string $url
	 * @param boolean $trim_www Обрезать 'www' [optional] [default: false]
	 * @return string
	 */
	public static function url2domain($url, $www = false)
	{
		//XXX(neone): parse_url($url, PHP_URL_HOST) ?!
		$url = str_replace('www. ', 'www.', $url);
		$chunks = explode_r(array(' ', ';', ',', 'http://', 'https://'), $url);
		foreach ($chunks as $chunk) {
			if (strpos($chunk, '.') !== false) {
				if (strpos($chunk, '@') !== false) continue;
				if (strpos($chunk, '_') !== false) continue;

				if($www) {
					$chunk = str_replace('www.', '', $chunk);
				}

				$slashes = explode('/', $chunk);
				$chunk = array();
				foreach ($slashes as $slash) if ($slash) $chunk[] = $slash;
				$chunk = implode('/', $chunk);

				return trim(strtolower($chunk));
			}
		}
		return false;
	}
	/**
	 * Проверка валидности адреса электронной почты
	 * @param string $mail E-Mail для проверки
	 * @return boolean
	 */
	public static function checkMail($mail) {
		$mail = trim($mail);
		return !!preg_match("%^[A-Za-z0-9](([_\.\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([\.\-]?[a-zA-Z0-9]+)*)\.([A-Za-z])+$%", $mail);
	}
	/**
	 * Подготовливает директорию к работе
	 * Проверяет наличие закрывающего слеша в конце директории
	 * @param string $path
	 * @param boolean $check_exists Проверить и, если директория не существует, создать её
	 * @return string
	 */
	public static function preparePath($path, $check_exists = false) {
		$path_len = strlen($path);
		$path[$path_len-1]==DIRECTORY_SEPARATOR or $path .= DIRECTORY_SEPARATOR;
		 
		$path = str_replace(array('\/', '//'), '/', $path);
		 
		if($check_exists && !file_exists($path)) {
			@mkdir($path, 0775, true);
		}
		 
		return $path;
	}
	/**
	 * Преобразует первый символ строки в верхний регистр
	 * @param string $stri
	 * @return string
	 */
	public static function ucfirst_utf8($stri) {
	 if($stri{0}>="\xc3")
	 return (($stri{1}>="\xa0")?
	 ($stri{0}.chr(ord($stri{1})-32)):
	 ($stri{0}.$stri{1})).substr($stri,2);
	 else return ucfirst($stri);
	}
}

/**
 * Блокировка параллельных вызовов по ключу
 * @author neone(km) 2012
 * 
 */
class Locker {
	private static $init;
	private static $locks;
	public static $PATH;

	private static function init() {
		if(self::$init) return;
		
		if(!self::$PATH) {
			self::$PATH = sys_get_temp_dir() . '/';
			self::$PATH = str_replace('//', '/', self::$PATH);
		}
		
		file_exists(self::$PATH) or @mkdir(self::$PATH, 0775, true) or self::$PATH = sys_get_temp_dir();
		self::$init = true;
	}
	public static function setLockPath($path) {
		self::$PATH = $path;
	}
	/**
	 * Блокировка параллельных вызовов
	 * @param string $name имя блокировки
	 * @param boolean $return вернуть результат, иначе при неудачной блокировке выход из программы
	 * @return void || boolean
	 */
	public static function lock($name, $return = false) {
		self::init();
		$lockfile = self::$PATH . "{$name}.lock";
		if(!file_exists($lockfile)) file_put_contents($lockfile, 1);
		self::$locks[$name] = fopen($lockfile, 'r');
		$lock_status = flock(self::$locks[$name], LOCK_EX + LOCK_NB);

		if($return) return $lock_status;

		if(!$lock_status)
			exit("Another instance is running!\n");
	}
	/**
	 * Разблокировать
	 * @param string $name имя блокировки
	 */
	public static function unlock($name) {
		if(!isset(self::$locks[$name])) return;
		@flock(self::$locks[$name], LOCK_UN);
		@fclose(self::$locks[$name]);
	}
}



class Curl {
	private static $ua_id;
	/**
	 * Имя собственного User-Agent`а
	 * @var string
	 */
	public static $self_ua = "Mozilla/5.0 (compatible; Core Single bot;)";

	/**
	 * User-agent браузера ( рандом )
	 * @return string
	 */
	public static function getUserAgent() {
		$user_agents = array(
				'Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15',
				'Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.7.62 Version/11.01',
				'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.127 Safari/534.16',
				'Mozilla/5.0 (Windows; U; Windows NT 6.1; ru; rv:1.9.2.12) Gecko/20110303 Firefox/3.6.12',
				'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)',
				'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
				'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.15) Gecko/20110303 Ubuntu/10.10 (maverick) Firefox/3.6.15',
				'Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.134',
				'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.68 Safari/534.24',
				'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C)',
				'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 6.1; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C)',
				'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.127 Safari/534.16',
				'Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Epiphany/2.30.6 Safari/534.7',
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0b11pre) Gecko/20110126 Firefox/4.0b11pre',
				'Mozilla/5.0 (X11; Linux i686; rv:2.0) Gecko/20110322 Firefox/4.0 Iceweasel/4.0',
				'Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; ru-RU)'
				);

				if(self::$ua_id) return $user_agents[self::$ua_id];
				self::$ua_id = rand(0, count($user_agents)-1);
				return $user_agents[self::$ua_id];
	}

	/**
	 * Получить контент по URL`у
	 * @param string $url URL
	 * @param boolean $emulate_ua Эмулировать браузерный User-Agent [optional] [default: true]
	 * @return string or false on failure
	 */
	public static function getContent($url, $emulate_ua = true) {

		$ua = $emulate_ua ? self::getUserAgent() : self::$self_ua;

		$options = array(
		CURLOPT_RETURNTRANSFER 	=> true,
		CURLOPT_HEADER         	=> false,
		CURLOPT_FOLLOWLOCATION 	=> true,
		CURLOPT_USERAGENT				=> $ua,
		CURLOPT_AUTOREFERER    	=> true,
		CURLOPT_CONNECTTIMEOUT 	=> 60,
		CURLOPT_TIMEOUT        	=> 60,
		CURLOPT_MAXREDIRS      	=> 5
		);

		try {
			$ch = curl_init($url);
			curl_setopt_array($ch, $options);
			$err			=		curl_errno($ch);
			//$err_msg	=		curl_error($ch);
			$content 	= 	curl_exec($ch);
			$http_code = 	curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
		} catch (Exception $e) {
			return false;
		}

		if(!$err && $http_code >= 200 && $http_code<=302) {
			return $content;
		}

		return false;
	}

	/**
	 * Проверка доступности ресурса
	 * Возвращает положительный ответ, если статус = 200-302
	 * @param string $url Проверяемый ресурс
	 * @todo //param boolean $strict Строгая проверка на ответ 200 [optional] [default:false]
	 * @return boolean
	 */
	public static function checkUrl($url/*, $strict = false*/) {
		if(strpos($url, 'http')===false) return false;
		$headers = get_headers($url);

		$result = false;
		foreach ($headers as $header) {
			if(stripos($header, 'HTTP')===0) {
				if(stripos($header, ' 200')>0) {
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

}

class Math {
	/**
	 * Среднее арифметическое
	 *
	 * @param array $set
	 * @return double
	 */
	function average ($set)
	{
		$px = array_sum($set);
		$pc = count($set);
		return $px / $pc;
	}
	/**
	 * Дискретное математическое ожидание
	 *
	 * @param array $set
	 * @return double
	 */
	function discreteExpectedValue ($set)
	{
		$distr = $this->discreteProbabilityDistribution($set);
		$exp = 0;
		$weight = array();
		foreach ($set as $i=>$x) {
			$weight[$x] += $distr[$i];
			$exp += (integer)$set[$i] * $distr[$i];
		}

		$exp = 0;
		foreach ($weight as $i=>$x) {
			$exp += $i * $x;
		}
		return $exp;
	}
	/**
	 * Дискретное распределение вероятности
	 *
	 * @param array $set
	 * @return array
	 */
	function discreteProbabilityDistribution ($set)
	{
		$px = array_sum($set);
		$p = array();
		foreach ($set as $i=>$x) {
			$p[$i] = (integer)$x / $px;
		}
		return $p;
	}
}

/**
 * Возвращает список классов реализующих определенный интерфейс
 * @author km (neone) 2012
 * @param string $interface Название интерфейса
 * @param string $classPrefix Начальная часть имена класса [optional] [default: Site]
 * @return array
 */
function getClassesByInterface($interface, $classPrefix = 'Site') {
	$classes = array();
	$declared_classes = get_declared_classes();

	foreach ($declared_classes as $class) {
		if($classPrefix && strpos($class, $classPrefix)!==0) continue;
		$RClass = new ReflectionClass($class);
		if(!$RClass->isUserDefined() || $RClass->isAbstract() || $RClass->isInterface()) continue;
		$interfaces = $RClass->getInterfaces();
		if(array_key_exists($interface, $interfaces)) {
			array_push($classes, $class);
		}
	}
	return $classes;
}

/**
 * Возвращает список классов, начинающихся с $firstPart
 * @param string $firstPart Начальная часть имени класса
 * @return array
 */
function getClassesByName($firstPart) {
	$needed = array();
	$firstPart = strval($firstPart);
	$classes = get_declared_classes();
	foreach ($classes as $class)
	if(strpos($class, $firstPart) === 0)
	$needed[] = $class;

	return $needed;
}


/**
 * Рекурсивный explode
 * @param $delimiters
 * @param $string
 * @return array
 */
function explode_r($delimiters, $string)
{
	if (!is_array($delimiters)) $delimiters = array($delimiters);
	$chunks = array($string);
  foreach ($delimiters as $delimiter) {
  	$result = array();
	  foreach ($chunks as $chunk) {
	  	$temp = explode($delimiter, $chunk);
			$result = array_merge($result, $temp);
	  }
	  $chunks = $result;
	}
  return $chunks;
}
/**
 * Перезагружает текущую страницу
 */
function reload()
{
	header("Location: {$_SERVER["REQUEST_URI"]}");
	exit;
}
/**
 * Переходит по указанному адресу
 * @param $url
 * @param $parmanent
 */
function redirect($url, $parmanent = false)
{
	if ($parmanent) header("HTTP/1.1 301 Moved Permanently");
	header("Location: {$url}");
	exit;
}
/**
 * Переход назад
 */
function back()
{
	redirect($_SERVER['HTTP_REFERER']);
}
/**
 * Ошибка HTTP 404 Not Found
 */
function HTTP_404_NotFound()
{
	$_SESSION['redirect']['404'] = $_SERVER['REQUEST_URI'];
	header("HTTP/1.1 404 Not Found");
	header("Location: /404/");
	exit;
}
/**
 * Ошибка HTTP 403 Forbidden
 */
function HTTP_403_Forbidden()
{
	$_SESSION['redirect']['403'] = $_SERVER['REQUEST_URI'];
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: /403/");
	exit;
}