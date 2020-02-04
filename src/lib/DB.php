<?php

namespace MidPay;

include(realpath(dirname(__FILE__)).'/external/medoo.php');

/**
 * This is a static wrapper around the Medoo database class.
 */
class Db
{
	private static $_hasTransaction = false;
	private static $_isSqlite = false;
	private static $_schema = null;
	private static $_dbName;

	public static function instance($useMysql=null) 
	{
		static $db;
		if (is_null($useMysql) && isset($db)) 
			return $db;
		
		if (is_null($useMysql)) $useMysql = 1;

		/* currently, we hardcode these settings... */

		self::$_dbName = 'midpay_dev_ben';

		if ($useMysql) { // MySQL	
			$db = new \Medoo(array(
				'database_type' => 'mysql',
				'database_name' => self::$_dbName,
				'server' => '127.0.0.1',
				'username' => self::$_dbName,
				'password' => 'THIS_PASSWORD_MUST_BE_CHANGED_TO_A_STRONG_SECRET_BEFORE_PUSHING_TO_PRODUCTION'
			));
			self::$_schema = null;
		} else { // Sqlite
			$db = new \Medoo(array(
				'database_type' => 'sqlite',
				'database_file' => realpath(dirname(__FILE__)).
					'/../db/THIS_FILENAME_MUST_BE_CHANGED_TO_A_STRONG_SECRET_BEFORE_PUSHING_TO_PRODUCTION.sqlite'
			));
			// To make immediate transaction work.
			$db->pdo->exec('PRAGMA busy_timeout=10000');
			self::$_isSqlite = true;
			self::$_schema = null;
		}	

		return $db;
	}

	public static function debug()
	{
		self::instance()->debug();	
	}

	public static function hasTransaction() 
	{
		return self::$_hasTransaction;
	}

	public static function beginTransaction() 
	{ 
		$pdo = self::instance()->pdo;
		if (!self::$_hasTransaction) {
			if (self::$_isSqlite) {
				// sqlite use deferred transactions by default 
				// (locks only upon write),
				// which can screw up reads.
				$pdo->exec('BEGIN IMMEDIATE'); 
			} else {
				$pdo->beginTransaction(); 
			}
			self::$_hasTransaction = true;
		}
	}

	public static function rollback() 
	{ 
		$pdo = self::instance()->pdo;
		if (self::$_hasTransaction) {
			if (self::$_isSqlite) {
				// Need use the exec method instead of the pdo's method.
				$pdo->exec('ROLLBACK');
			} else {
				$pdo->rollback(); 
			}
			self::$_hasTransaction = false;
		}
	}

	// End transaction
	public static function commit() 
	{ 
		$pdo = self::instance()->pdo;
		if (self::$_hasTransaction) {
			if (self::$_isSqlite) {
				// Need use the exec method instead of the pdo's method.
				$pdo->exec('COMMIT');
			} else {
				$pdo->commit(); 
			}
			self::$_hasTransaction = false;
		}
	}

	public static function bulkInsert($table, $datas)
	{	
		if (is_array($datas) && isset($datas[0]) && is_array($datas[0])) {
			$columns = array_keys($datas[0]);
			$allSame = true;
			foreach ($datas as $row) 
				if (!is_array($row) || array_diff(array_keys($row), $columns)) 
					$allSame = false;	
			if ($allSame) {
				foreach (array_chunk($datas, 100) as $chunk) {
					$sql = 'INSERT INTO "' . $table . '" (' . implode(', ', $columns) . ') VALUES ';
					$insertValues = array();
					foreach ($chunk as $row) foreach ($row as $value) $insertValues[] = $value;
					$questionMarks = '(' . implode(array_fill(0, sizeof($columns), '?'), ',') . ')';
					$sql .= implode(array_fill(0, sizeof($chunk), $questionMarks), ',');
					$stmt = self::instance()->pdo->prepare($sql);
					$stmt->execute($insertValues);
				}
				return true;
			}
		}
		return false;
	}

	public static function _inMedooSpecialKeys($k) 
	{
		return in_array($k, array('AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH'));
	}

	private static function _prepareWhere($where) 
	{
		if (is_array($where) && sizeof($where) > 1) {
			$a = array(); 
			$b = array();
			foreach ($where as $key => $value) {
				if (!self::_inMedooSpecialKeys($key)) $a[$key] = $value;
				else $b[$key] = $value;
			}
			if (sizeof($a)) $b['AND'] = $a;
			return $b;
		}
		return $where;
	}

	public static function query($query) 
	{ 
		return self::instance()->query($query); 
	}

	public static function select($table, $join, $columns=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->select($table, $join, $columns, $where); 
	}

	public static function insert($table, $datas) 
	{ 
		return self::instance()->insert($table, $datas); 
	}

	public static function update($table, $data, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->update($table, $data, $where); 
	}

	public static function delete($table, $where) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->delete($table, $where); 
	}

	public static function replace($table, $columns, $search=null, $replace=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->replace($table, $columns, $search, $replace, $where); 
	}

	public static function get($table, $join=null, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->get($table, $join, $column, $where); 
	}

	public static function has($table, $join, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->has($table, $join, $where); 
	}

	public static function count($table, $join=null, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->count($table, $join, $column, $where); 
	}

	public static function max($table, $join, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->max($table, $join, $column, $where); 
	}

	public static function min($table, $join, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->min($table, $join, $column, $where); 
	}

	public static function avg($table, $join, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->avg($table, $join, $column, $where); 
	}

	public static function sum($table, $join, $column=null, $where=null) 
	{ 
		$where = self::_prepareWhere($where);
		return self::instance()->sum($table, $join, $column, $where); 
	}

	public static function schema($table=null)
	{
		if (is_null(self::$_schema)) {
			
			self::$_schema = array();
			
			self::instance();

			$cols = self::query('SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, TABLE_NAME, '.
				'IS_NULLABLE, COLUMN_KEY '.
				'FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = \'' . self::$_dbName . '\'');
			
			$numericTypes = array('decimal', 'numeric', 'float', 'double');
			$integerTypes = array('int', 'integer', 'smallint', 'mediumint', 'bigint');
			$booleanTypes = array('bool', 'boolean', 'tinyint');
			// All booleans are integers. All integers are numeric.
			
			if ($cols !== false) { // MySQL

				$mysqlNonUniqueKeyTypes = array('MUL');

				foreach ($cols as $f) {
					$isBoolean = in_array($f['DATA_TYPE'], $booleanTypes);
					$isInteger = in_array($f['DATA_TYPE'], $integerTypes) || $isBoolean;
					$isNumeric = in_array($f['DATA_TYPE'], $numericTypes) || $isInteger;

					self::$_schema[$f['TABLE_NAME']][$f['COLUMN_NAME']] = (object) array(
						'type' => $f['DATA_TYPE'],
						'maxLength' => (int) $f['CHARACTER_MAXIMUM_LENGTH'],
						'isNumeric' => $isNumeric,
						'isInteger' => $isInteger,
						'isBoolean' => $isBoolean,
						'isNullable' => strtoupper(substr($f['IS_NULLABLE'], 0, 1)) === 'Y',
						'isUnique' => isset($f['COLUMN_KEY'][0]) && 
							!in_array(strtoupper($f['COLUMN_KEY']), $mysqlNonUniqueKeyTypes)
					);
				}

			} else { // sqlite

				$tables = self::query('SELECT name FROM sqlite_master where type=\'table\'');

				foreach ($tables as $t) {
					$t = $t['name'];
					// Sqlite will require a different method (using pragma) to get the metadata
					$uniqueColumns = array();
					foreach (self::query("PRAGMA index_list('$t')") as $f) if ($f['unique'])
						foreach (self::query("PRAGMA index_info('".$f['name']."')") as $g) 
							$uniqueColumns[] = $g['name'];

					foreach (self::query("PRAGMA table_info('$t')") as $f) {
						$v = explode(' ', strtolower($f['type']))[0];
						$maxLength = 0;
						if (($p = strpos($v, '(')) !== false) {
							$a = explode(',', substr($v, $p+1, strpos($v, ')', $p)-$p-1));
							if (sizeof($a) == 1) $maxLength = (int) $a[0];
							$type = substr($v, 0, $p);	
						} else {
							$type = $v;	
						}

						$isBoolean = in_array($type, $booleanTypes);
						$isInteger = in_array($type, $integerTypes) || $isBoolean;
						$isNumeric = in_array($type, $numericTypes) || $isInteger;

						self::$_schema[$t][$f['name']] = (object) array(
							'type' => $type, 
							'maxLength' => $maxLength,
							'isNumeric' => $isNumeric,
							'isInteger' => $isInteger,
							'isBoolean' => $isBoolean,
							'isNullable' => !((bool) $f['notnull']),
							'isUnique' => in_array($f['name'], $uniqueColumns)
						);
					}
				}
			}
		}
	
		if (!is_null($table) && isset(self::$_schema[$table]))
			return self::$_schema[$table];
		else if (is_null($table) && isset(self::$_schema))
			return self::$_schema;
		else 
			return null;
	
	}

}