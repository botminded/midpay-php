<?php

namespace MidPay;

/**
 * A class for bulk extracting fields from request parameters,
 * and validating them for simple CRUD operations.
 */
class Fields
{
	public $params;
	public $mappings;

	const WHERE = 1;
	const DATA = 2;

	private static function _inSpecialKeys($k) 
	{
		return in_array($k, array('AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH'));
	}

	public static function _validateValue($table, $dbFieldName, $value, &$mappings, $mode)
	{
		$schema = Db::schema($table);
		if (is_null($schema)) return false;
		if (!isset($schema[$dbFieldName])) return false;

		$p = $schema[$dbFieldName];

		$sl = strlen('' . $value);
		$notEmpty = $sl > 0;

		$name = $dbFieldName;  
		if (isset($mappings[$dbFieldName]))
			$name = $mappings[$dbFieldName]; 

		if ($p->maxLength && $sl > $p->maxLength) {
			$message = 'The "' . $name . '" must be below ' 
				. $p->maxLength . 'bytes.';
			Errors::append(5001, $message, $name);
			return false;
		}
		if ($notEmpty && $p->isNumeric && !is_numeric($value)) {
			$message = 'The "' . $name . '" must a valid number.';
			Errors::append(5001, $message, $name);
			return false;	
		}
		if ($notEmpty && $p->isInteger && 
			filter_var($value, FILTER_VALIDATE_INT) === false) {
			$message = 'The "' . $name . '" must a valid integer.';
			Errors::append(5001, $message, $name);
			return false;	
		}
		if (!$p->isNullable && (is_null($value) || !$notEmpty)) {
			$message = 'The "' . $name . '" must not be empty or omitted.';
			Errors::append(5001, $message, $name);
			return false;		
		}
		if ($mode == self::DATA && 
			Db::has($table, array($dbFieldName => $value))) {
			$message = 'The "' . $name . '" already exists.';
			Errors::append(5001, $message, $name);
			return false;			
		}
		if ($mode == self::WHERE && 
			Db::has($table, array($dbFieldName => $value))) {
			$message = 'The "' . $name . '" does not exists.';
			Errors::append(5001, $message, $name);
			return false;			
		}
		return true;
	}

	public static function _validate($table, $array, &$result, &$mappings, $mode)
	{
		foreach ($array as $key => $value) {
			if (self::_inSpecialKeys($key)) {
				if (is_array($value))
					self::validate($table, $value, $result, $mappings, $mode);
			} else {
				$result &= self::_validateValue($table, $key, $value, $mappings, $mode);
			}
		}
	}

	public function validate($mode, $table)
	{
		$result = true;
		self::_validate($table, $this->params, $result, $this->mappings, $mode);
		return $result;
	}

	private function __construct($mappings, $source=null)
	{
		$this->mappings = $mappings;

		if (!is_array($source))
			$source = Params::body();
		
		$this->params = array();

		foreach ($mappings as $dbFieldName => $sourceFieldName) {
			$value = null;
			if (isset($source[$sourceFieldName]))
				$value = $source[$sourceFieldName];
			$this->params[$dbFieldName] = $value;
		}

	}

	public static function from($mappings, $source=null)
	{
		return new self($mappings, $source);
	}

	/**
	 * Return the non null values in the array.
	 * @param  array   $array   The array to filter.
 	 * @param  boolean $recurse Whether to recurse.
	 * @return array            An array without the null values.
	 */
	public static function _nonNulls($array, $recurse=true) 
	{
		$results = array(); 
		foreach ($array as $key => $value) {
			if ($recurse && is_array($value)) 
				$results[$key] = self::_nonNulls($value, true);
			else if (!is_null($value)) $results[$key] = $value;
		}
		return $results;
	}

	public function removeNulls()
	{
		$this->params = self::_nonNulls($this->params);
		return $this;
	}

}