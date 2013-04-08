<?php

abstract class aDB implements iDB {
	protected $config;
	
	/**
	 * Создаёт объект для работы с БД
	 * @param string $host
	 * @param string $dbname
	 * @param string $login
	 * @param string $password [optional]
	 * @param boolean $initialize Установить соединение сразу? [optional] [default: false]
	 * @throws DB_Exception
	 */
	public function __construct($host, $dbname, $login, $password = null, $initialize = false) {
		$this->config['host'] = $host;
		$this->config['dbname'] = $dbname;
		$this->config['login'] = $login;
		$this->config['password'] = $password;
	
		if($initialize) {
			if(!$this->connect()) {
				throw new DB_Exception($this->error());
			}
		}
		
	}
}

/**
 * Класс-обертка для работы с MySQL API
 * @author km
 */
final class MySQL_DB extends aDB {
	private $connector;

	public function connect() {
		if($this->config['password']) {
			$connector = mysql_connect($this->config['host'], $this->config['login'], $this->config['password']);
		} else {
			$connector = mysql_connect($this->config['host'], $this->config['login']);
		}

		if($connector) {
			$this->connector = $connector;
			if($this->config['dbname']) {
				$this->select_database($this->config['dbname']);
			}
		}

		return $connector;
	}

	public function select_database($dbname) {
		return mysql_select_db($dbname, $this->connector);
	}

	public function escape($string) {
		return mysql_real_escape_string($string, $this->connector);
	}

	public function query($sql) {
		return mysql_query($sql, $this->connector);
	}

	public function insert_id() {
		return mysql_insert_id($this->connector);
	}

	public function row($result) {
		return mysql_fetch_assoc($result);
	}

	public function rows($result) {
		return mysql_num_rows($result);
	}
	
	public function affected_rows() {
		return mysql_affected_rows($this->connector);
	}
	public function free_result($result) {
		return mysql_free_result($result);
	}	

	public function reset($result, $pos = 0) {
		return @mysql_data_seek($result, $pos);
	}
	
	public function host_info() {
		return mysql_get_host_info($this->connector);
	}

	public function error() {
		return mysql_error($this->connector);
	}

	public function errno() {
		return mysql_errno($this->connector);
	}

	public function disconnect() {
		return mysql_close($this->connector);
	}
}

/**
 * Класс-обертка для работы с MySQLi API
 * @author km
 */
final class MySQLi_DB extends aDB {
	private $connector;

	public function connect() {
		$connector = new mysqli($this->config['host'], $this->config['login'], $this->config['password'], $this->config['dbname']);

		if ($connector->connect_error) {
			return false;
		} else {
			$this->connector = $connector;
			return $this->connector;
		}
	}

	public function select_database($dbname) {
		return $this->connector->select_db($dbname);
	}

	public function escape($string) {
		return $this->connector->real_escape_string($string);
	}

	public function query($sql) {
		return $this->connector->query($sql);
	}

	public function insert_id() {
		return $this->connector->insert_id;
	}

	public function row($result) {
		if(!$result) return false;
		if(!$result instanceof MySQLi_Result) throw new DB_Exception("Result is not mysqli result object!");
		return $result->fetch_assoc();
	}

	public function rows($result) {
		if(!$result) return false;
		if(!$result instanceof MySQLi_Result) throw new DB_Exception("Result is not mysqli result object!");
		return $result->num_rows;
	}
	
	public function affected_rows() {
		return $this->connector->affected_rows;		
	}
	public function free_result($result) {
		if(!$result) return false;
		if(!$result instanceof MySQLi_Result) throw new DB_Exception("Result is not mysqli result object!");
		return $result->free();		
	}

	public function reset($result, $pos = 0) {
		if(!$result) return false;
		if(!$result instanceof MySQLi_Result) throw new DB_Exception("Result is not mysqli result object!");
		return $result->data_seek($pos);
	}
	
	public function host_info() {
		return $this->connector->host_info;
	}

	public function error() {
		return $this->connector->errno;
	}

	public function errno() {
		return $this->connector->error;
	}

	public function disconnect() {
		return $this->connector->close();
	}
}

/**
 * Статический класс для работы с базой данных
 * @author Cellard && NeOne
 */

abstract class Database {
	
	const MySQL = 'mysql';
	const MySQLi = 'mysqli';
	
  /**
   * Объект-оболочка для работы с API DB
   * @var iDB
   */
  private static $db;
  
  protected static $fail_filename;
  
  private static $queriesExecs = array();
  private static $queriesCache = array();
  private static $serverConfigs = array();
  
  /**
   * Кеш запросов
   * @var array
   */
  public static $cache = array();
  
  /**
   * Можно ли кешировать запросы?
   * @var boolean
   */
  private static $cacheable = true;
  
  /**
   * Имя файла, в котором хранится состояние доступности серверов
   * @var string
   */
  const FAIL_FLAG_NAME = "db_autoswitcher.state";
  /**
   * Время жизни флага недоступности базы
   * @var int
   */
  public static $FAIL_STATE_TTL = 300;
  
  
  /**
   * Establishes a connection to a MySQL server
   * @throws DB_Exception
   * @param string $server IP-адрес или хост сервера БД
   * @param string $dbname Имя базы данных, если ненадо то оставить поле пустым, лтбо false
   * @param string $login Имя пользователя
   * @param string $password Пароль, если необходим
   * @param string $useAPI Принудительно использовать выбранную API для работы с DB (see Database::API_*). По-умолчанию: автовыбор [optional]
   * @return void
   */
  public static function init($server, $dbname, $login, $password = false, $useAPI = null)
  {  	
  	switch($useAPI) {
  		case self::MySQL: $db_class = 'MySQL_DB'; break;
  		case self::MySQLi: $db_class = 'MySQLi_DB'; break;
  		default: $db_class = class_exists('mysqli') ? 'MySQLi_DB' : 'MySQL_DB';
  	}
  	
  	$db = new $db_class($server, $dbname, $login, $password);
  	$connect = $db->connect();
  		
  	if($connect) {
  		self::$db = $db;
  	} else {
  		$error_msg = "Couldn`t connect to {$server} from {$_SERVER['REMOTE_ADDR']}: " . $db->error();
  		self::log($error_msg);
  		exit($error_msg);
  	}
  }
  
  /**
   * Переопределяет объект, используемый для действий с БД
   * @param iDB $db
   */
  public static function decorate(iDB $db)
  {
  	self::$db = $db;
  }
  
  /**
   * Turns on/off queries caching
   * @param boolean $state
   */
  public static function cache($state)
  {
  	self::$cacheable = $state;
  }
  
  /**
   * Выбрать базу данных
   * @param string $dbname Имя базы данных
   * @throws DB_Exception
   */
  public static function select_database($dbname)
  {
  	if(!$dbname) return false;
  	self::$db->select_database($dbname);
  }  
  
  /**
   * Выполнить SQL запрос
   * @throws DB_Exception
   * @param string $sql
   * @param boolean $cache кешировать?
   * @return resource
   */
  public static function query($sql, $cache = false)
  {
  	if(!self::$db) throw new DB_Exception("Connection is not established.");

  	$s = microtime(true);

    if ($cache && self::$cacheable) {
    	$id = md5($sql);
      if (isset(self::$cache[$id]) && self::$cache[$id]) {
      	if (!self::reset(self::$cache[$id], 0)) {
      		unset(self::$cache[$id]);
      		return self::query($sql, false);
      	}
      	if (self::$cacheable) self::$queriesCache[] = $sql;
      	return self::$cache[$id];
      }
    }

    $result = self::$db->query($sql);
    
    $timeToQuery = round(microtime(true) - $s, 3) . 's  > ';
    self::$queriesExecs[] = "{$timeToQuery}{$sql}";
    
    if ($result) {
      if ($cache && self::$cacheable) {
        self::$cache[$id] = $result;
      }
      return $result;
    } else {
      throw new DB_Exception("Invalid query '{$sql}': " . self::$db->error());
    }
  }

  /**
   * Синоним к методу query()
   * @see query()
   * @throws DB_Exception
   * @param string $sql
   * @param boolean $cache кешировать?
   * @return resource
   */
  public static function q($sql, $cache = false)
  {
    return self::query($sql, $cache);
  }
  
  /**
   * Escapes special characters in a string for use in a SQL statement
   * @param $string
   * @return string
   */
  public static function escape($string)
  {
  	return self::$db->escape($string);
  }
  
  /**
   * Время NOW() из базы ( YYYY-MM-DD HH:MM:SS )
   * @return string
   */
  public function now() {
  	return (string)self::fetch(self::query("SELECT NOW()"), 0);
  }
  
  /**
   * TIMESTAMP ( unix ) из базы
   * @return int
   */
  public static function timestamp() {
  	return (int)self::fetch(self::query("SELECT UNIX_TIMESTAMP()"), 0);
  }

  /**
   * Performs an SQL query and returns the number of seconds it's spent
   * @param $sql
   * @return double
   */
  public static function profile($sql)
  {
    $start = microtime(1);
    self::query($sql);
    return microtime(1) - $start;
  }
  
  /**
   * Fetch a next result row as an associative array
   * @param resource $result
   * @return array
   */
  public static function row($result)
  {
  	return self::$db->row($result);
  }

  /**
   * Get number of rows in result
   * @param resource $result
   * @return integer
   */
  public static function rows($result)
  {
    return (integer)self::$db->rows($result);
  }
  
  public static function affected_rows() {
  	return self::$db->affected_rows();
  }
  /**
   * Очищает результатирующий набор их памяти
   * @param resource $result
   * @return boolean
   */
  public static function free_result($result) {
  	return self::$db->free_result($result);
  }

  /**
   * Fetch a result as an associative multi array
   *
   * If $key is defined the key of returned array is the value of column with such name.
   * Usefull to use with index column.
   *
   * @throws DB_Exception
   * @param resource $result
   * @param string $key
   * @return array
   */
  public static function fetchall($result, $key = false)
  {
    $rows = array();
    while($row = self::row($result)) {
      if ($key) {
        if (isset($row[$key])) {
          //$rows[$row[$key]] = $row;
          $rows[] = $row[$key];
        } else {
          throw new DB_Exception("There is no {$key} column in {$result}");
        }
      } else {
        $rows[] = $row;
      }
    }
    return $rows;
  }

  /**
   * Fetch a first row of result as an associative array
   *
   * If $column is defined the result will be the value of this column.
   *
   * @throws DB_Exception
   * @param resource $result
   * @param string $column
   * @return mixed
   */
  public static function fetch($result, $column = false)
  {
    $row = self::row($result);
    if(!$row) return false;
    
    if ($column !== false) {
      if (intval($column) == $column) {
        foreach ($row as $cell) {
          if ($column == 0) return $cell;
          $column--;
        }
      } else {
        if (isset($row[$column])) {
          return $row[$column];
        } else {
          throw new DB_Exception("There is no {$key} column in {$result}");
        }
      }
    } else {
      return $row;
    }
  }
  
  /**
   * Получить кол-во записей, возвращаемых запросом
   * Формирует запрос COUNT(*), т.е. записи не получает
   * Запрос обязан содержать ключевые слова SELECT и FROM
   * @param string $sql Запрос выборки
   * @return int or false on failure
   */
  public static function count_sql($sql) {  	
  	$sql = trim($sql);
  	$pos_from = stripos($sql, "FROM");
  	if(strpos($sql, 'SELECT') !== 0 || !$pos_from) {
  		return false;
  	}
  	
  	$sql = "SELECT COUNT(*) as cnt " . substr($sql, $pos_from);
  	
  	$result = self::query($sql);
  	$row = Database::row($result);
  	
  	$cnt = isset($row['cnt']) ? intval($row['cnt']) : false;  	
  	return $cnt;
  }
  
  /**
   * Move internal result pointer to the very first row
   * @param resource $result
   * @return boolean
   */
  public static function reset($result, $pos = 0) 
  {
    return self::$db->reset($result, $pos);
  }

  /**
   * Returns the ID generated for an AUTO_INCREMENT column by the previous INSERT query
   * @param string $sql Send query first
   * @return integer
   */
  public static function insert_id($sql = null)
  {
  	if($sql) self::query($sql);
    return self::$db->insert_id();
  }

  /**
   * Synonym for insert_id
   * @see insert_id()
   * @param $sql Send query first
   * @return integer
   */
  public static function id($sql = null) 
  {
    return self::insert_id($sql);
  }
  
  /**
   * Информация о текущем подключении
   * @return string
   */
  public static function host_info()
  {
  	return self::$db->host_info();
  }
  
  /**
   * Описание последней ошибки
   * @return string
   */
  public static function error()
  {
  	return self::$db->error();
  }
  
  /**
   * Внутренний код последеней ошибки
   * @return int 
   */
  public static function errno()
  {
  	return self::$db->errno();
  }
  
  /**
   * Закрывает соединение с базой данных
   * @return boolean
   */
  public static function disconnect()
  {
  	return self::$db->disconnect();
  }
  
  /**
   * Записать событие в лог
   * @param string $msg
   */
  private static function log($msg) 
  {
  	$msg = date("Y-m-d H:i") . " > {$msg}\n";
  	$filename = (defined('SITE_LOG_DIR') ? SITE_LOG_DIR : sys_get_temp_dir()) . "/database.log";
  	file_put_contents($filename, $msg, FILE_APPEND);
  }
  /**
   * Статистика запросов
   * @return array
   */
  public static function statistics()
  {
  	return array(
  			"execs" => count(self::$queriesExecs),
  			"cache" => count(self::$queriesCache),
  			"aexecs" => self::$queriesExecs,
  			"acache" => self::$queriesCache,
  	);
  }
}

/**
 * Типизатор данных
 * @author neone
 */
class DbTypizer {
	
	const TYPE_INT = 000;
	const TYPE_NO_TAGS = 111;
	const TYPE_BOOLEAN = 222;
	const TYPE_ESCAPE = 333;
	//const TYPE_TEXT_ONLY = 777;
	
	/**
	 * Преобразовать данные под конкретный тип, либо отфильтровать
	 * @param mixed $value
	 * @param int $type например: DbExpression::TYPE_INT
	 * @see DbExpression::TYPE_*
	 * @return string
	 */
	public static function typize($value, $type) {
		switch ($type) {
			case self::TYPE_INT:
				$value = intval($value);
				break;
			case self::TYPE_BOOLEAN:
				$value = !!$value;
				break;
			case self::TYPE_NO_TAGS:
				$value = strip_tags($value);
				break;
			case self::TYPE_ESCAPE:
				$value = Database::escape($value);
				break;
		}
		return $value;
	}
	/**
	 * Преобразовать массив данных под конкретный тип, либо отфильтровать
	 * @param array $values
	 * @param int $type например: DbExpression::TYPE_INT
	 * @param boolean $clean_empty Удалять ключи с пустыми значениями, если возможно [optional] [default: false]
	 * @return array
	 */
	public static function typizeArray(array $values, $type, $clean_empty = false) {
		foreach ($values as $key => $value) {
			$value = self::typize($value, $type);
			if($clean_empty && !$value && in_array($type, array(self::TYPE_INT))) {
				unset($values[$key]);
			} else {
				$values[$key] = self::typize($value, $type);
			}
		}
		return $values;
	}
}