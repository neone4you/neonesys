<?php

/**
 * Работа с мета-данными
 * @author neone
 *
 */
class appModelHelperMeta {
	private $data = array();
	
	/**
	 * 
	 * @param string $meta_str Данные из поля с мета-данными из таблицы
	 */
	public function __construct($meta_str) {
		if(!($meta && strpos($meta_str, ':')>0)) return;
		$data = unserialize($meta_str);
		if(is_array($data)) {
			$this->data = $data;
		}
	}
	/**
	 * Получить данные по ключу
	 * @param string $key Ключ
	 * @return mixed or false
	 */
	public function get($key) {
		return (isset($this->data[$key]) ? $this->data[$key] : false);
	}
	public function getData() {
		return $this->data;
	}
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	public function remove($key) {
		unset($this->data[$key]);
	}
	public function __toString() {
		return !empty($this->data) ? serialize($this->data) : '';
	}
	
}

class appMeta {
	const JSON = 'json';
	const SERIALIZE = 'serialize';
	
	protected $data = array();
	/**
	 * 
	 * @param array $data Данные
	 * @param string $format Формат json/serialize [optional] [default: json]
	 * @see appMeta::*
	 */
	private function __construct(array $data = null, $format = null) {
		$this->data = is_array($data) ? $data : array();
		//throw new AppException("appHelperMeta static use only ;)"); 
	}
	public function set($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}
	public function remove($key) {
		unset($this->data[$key]);
		return $this;
	}
	public function getSerialize() {
		return serialize($this->data);
	}
	public function getJSON() {
		return json_encode($this->data);
	}
	public function getArray() {
		return $this->data;
	}
	public function __toString() {
		return !empty($this->data) ? serialize($this->data) : '';
	}
	
	public static function create() {
		/**
		 * TODO(neone): интелектуальная система хранения мета-информации, экономия памяти, проверить
		 */
		return new self();
	}
	public static function use_array(array $data) {
		return new self($data);
	}
	public static function use_string($data_str) {
		if($data_str) {
			if(strpos($data_str, '{')===0 && $data = json_decode($data_str)) { // JSON
				return new self($data, appMeta::JSON);
			}
			if(strpos($data_str, ':')>0 && $data = unserialize($data_str)) {
				return new self($data, self::SERIALIZE);
			}
		}
		return new self;
	}
	
}


/**
 * Мисс Модель 2013 ;)
 * @author neone
 */
abstract class aModel extends aTable {
	/**
	 * @var appModelHelperMeta
	 */
	private $Meta;
	
	public function onInsert($id, array $row) {}
	public function onUpdate($id, array $row) {}
	public function onDelete($id) {}
	
	/**
	 * SQL-помошник
	 * @return SQL_Helper
	 */
	public function SQL_Helper() {
		return new SQL_Helper($this);
	}
	
	/**
	 * Получить массив данных по ID записи
	 * @param int $id Идентификатор записи
	 * @return array or false
	 */
	public function getById($id) {
		if(!$id = intval($id)) return false;
		$result = Database::query("SELECT * FROM {$this->_tableName()} WHERE {$this->prefix('id')} = {$id} LIMIT 1");
		return Database::row($result);
	}
	
	/**
	 * Получить данные по условию
	 * @param SQL_Helper $SQL
	 * @return array or false on failure
	 */
	public function get(SQL_Helper $SQL) {
		$result = Database::query($SQL->__toString());
		if(!$result) return false;
		
		$rows = array();
		$id_column = $this->idName();
		while ($row = Database::row($result)) {
			$id = $row[$id_column];
			$rows[$id] = $row;
		}
		return $rows;
	}
	
	/**
	 * Получить все записи
	 * @return array or false
	 */
	public function getAll() {
		$rows = array();
		$sql = "SELECT * FROM {$this->_tableName()}";
		$result = Database::query($sql);
		while ($row = Database::row($result)) {
			$rows[] = $row;
		}
		return $rows;
	}
	
	/**
	 * Кол-во записей
	 * @param SQL_Helper $SQL
	 * @return int or false
	 */
	public function count(SQL_Helper $SQL = null) {
		if(!$SQL) {
			$sql = "SELECT * FROM {$this->_tableName()}";
		} else {
			$sql = $SQL->__toString();
		}
		return Database::count_sql($sql);
	}
	
	/**
	 * Удалить запись по первичному ключу
	 * @param int $id Идентификатор записи
	 * @return boolean
	 */
	public function removeById($id) {
		if(!$id = intval($id)) return false;
		$status = Database::query("DELETE FROM {$this->_tableName()} WHERE {$this->prefix('id')} = {$id}");
		if($status) {
			$this->onDelete($id);
		}
		return $status;
	}
	
	/**
	 * Имя поля, содержащего идентификатор записи (с префиксом)
	 * @return string
	 */
	public function idName() {
		return $this->prefix('id');
	}
	
	/**
	 * Объект для работы с мета-информацией объекта
	 * Пример использования:
	 * 
	 * @throws InterfaceRealisationException
	 * @return appModelHelperMeta
	 */
	/*public function Meta() {
		//TODO(neone): !экономия памяти, интелектальный алгоритм
		if(!$this->Meta) { 
			if(!$this instanceof iMeta) throw new InterfaceRealisationException('iMeta', $this);
			$this->Meta = new appModelHelperMeta($this);
		}
		return $this->Meta;
	}*/
	
	/**
	 * Префикс, либо обернуть/проверить имя поля
	 * @param string $prefixize Если не установлено, то вернёт префикс (с '_'), иначе вернёт "префиксованное" имя поля [opt] [defalt: false]
	 * @return string
	 */
	public function prefix($column_name = false) {
		if(!$this->prefix) {
			$_prefix = $this->_prefix();
			$this->prefix = '_' === $_prefix[strlen($_prefix)-1] ? $_prefix : $_prefix . '_';
		}
		
		if(!$column_name) return $this->prefix;
		if(strpos($column_name, $this->prefix)!==0) {
			$column_name = $this->prefix . $column_name;
		}
		return $column_name;
	}
	
	private function prepareRow(array $row) {
		$row_new = array();
		foreach ($row as $column_name => $column_data) {
			if(is_object($column_data) && method_exists($column_data, '__toString')) {
				$column_data = $column_data->__toString();
			}
			$column_name = $this->prefix($column_name);
			$row_new[$column_name] = Database::escape($column_data); //XXX(neone): db escape
		}
		return $row_new;
	}
	
	private function implodeSetRow(array $row) {
		$arr = array();
		foreach ($row as $column_name => $column_data) {
			if(strpos($column_data, "'")!==0) $column_data = "'{$column_data}'";
			$arr[] = "{$column_name} = {$column_data}";
		}
		return implode(', ', $arr);
	}
	
	/**
	 * Вставить запись
	 * @param array $row Массив данных 'column' => 'data', можно без префиксов
	 * @return int or false
	 */
	public function insertRow(array $row) {
		$row = $this->prepareRow($row);
		$set_str = $this->implodeSetRow($row);		
		$sql = "INSERT INTO {$this->_tableName()} SET {$set_str}";
		$insert_id = Database::insert_id($sql);
		if($insert_id) {
			$this->onInsert($insert_id, $row);
		}
		return $insert_id;
	}
	
	/**
	 * Обновить запись
	 * @param int $id Идентификатор записи
	 * @param array $row Массив с новыми данными
	 * @return boolean
	 */
	public function updateRow($id, array $row) {
		$id = intval($id);
		if(!$id || empty($row)) return false;
		
		$row = $this->prepareRow($row);
		$set_str = $this->implodeSetRow($row);
		$sql = "UPDATE {$this->_tableName()} SET {$set_str} WHERE {$this->idName()} = {$id}";
		$status = Database::query($sql);
		if($status) {
			$this->onUpdate($id, $row);
		}
		return $status;
	}
	
}


class SQL_Helper {
	private $Model;
	private $select = '*';
	private $where = array();
	private $order;
	private $limit;
	
	public function __construct(aModel $Model) {
		$this->Model = $Model;
	}
	public function select($select = '*') {
		$this->select = $select;
	}
	public function limit($on_page, $page = 1) {
		$on_page = intval($on_page);
		if($page>0) $page = $page - 1;
		$from = $on_page * $page;
		$this->limit = "{$from}, {$on_page}";
		return $this;
	}
	public function like($key, $value) {
		$this->where[] = $this->Model->prefix($key) . ' LIKE ' . $value;
		return $this;
	}
	public function equals($key, $value, $not = '') {
		if(is_null($value)) {
			$not and $not = ' NOT';
			$this->where[] = $this->Model->prefix($key) . " IS{$not} NULL";
		} else {
			$not and $not = '!';
			$this->where[] = $this->Model->prefix($key) . " {$not}= '" . $value . "'";
		}
		return $this;
	}
	public function where($where_str) {
		$this->where[] = Database::escape($where_str);
		return $this;
	}
	public function order($order) {
		$this->order = $this->Model->prefix($order);
		return $this;
	}
	public function __toString() {
		$sql = "SELECT {$this->select} FROM {$this->Model->_tableName()} ";
		
		if(count($this->where)) {
			$sql .= "WHERE " . implode(' AND ', $this->where) . ' ';
		}
		if($this->order) {
			$sql .= "ORDER BY {$this->order} ";
		}
		if($this->limit) {
			$sql .= "LIMIT {$this->limit}";
		}
		return $sql;
	}
}


abstract class aTable {
	private $prefix;
	/**
	 * Префикс имён полей таблицы
	 * @return string
	 */
	abstract function _prefix();
	/**
	 * Имя таблицы
	 * @return string
	 */
	abstract function _tableName();
}

/**
 * Записи могут содержать мета-данные
 */
interface iMeta {
	/**
	 * Имя поля с полем типа TEXT для хранения мета-данных
	 * @return string
	 */
	function _metaColumnName();
}