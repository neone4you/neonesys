<?php

interface iDB {
	/**
	 * Устанавливает соединение с базой данных
	 * @return connection
	 */
	function connect();
	function select_database($dbname);
	function escape($string);
	function query($sql);
	function insert_id();
	function row($result);
	function rows($result);
	function affected_rows();
	function free_result($result);
	function reset($result, $pos = 0);
	function host_info();
	function error();
	function errno();
	function disconnect();
}