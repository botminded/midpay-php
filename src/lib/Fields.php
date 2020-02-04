<?php

namespace MidPay;

/**
 * A class for bulk extracting fields from request parameters,
 * and validating them for simple CRUD operations.
 */
class Fields
{
	const WHERE = 1;
	const DATA = 2;

	private static $_errorCode = 0;

	public static function errorCode($code=null)
	{
		if (!is_null($code)) {
			self::$_errorCode = $code;
		}
		return self::$_errorCode;
	}

	private static function _validateValue($mode, $table, $dbFieldName, $value, &$mappings)
	{
		$schema = Db::schema($table);
		if (is_null($schema)) return false;
		if (!isset($schema[$dbFieldName])) return false;

		$p = $schema[$dbFieldName];

		$valueLength = strlen('' . $value);
		$notEmpty = $valueLength > 0;

		$name = $dbFieldName;  
		if (isset($mappings[$dbFieldName]))
			$name = $mappings[$dbFieldName]; 

		if ($p->maxLength && $valueLength > $p->maxLength) {
			$message = 'The "' . $name . '" must be below ' 
				. $p->maxLength . 'bytes.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;
		}
		if ($notEmpty && $p->isNumeric && !is_numeric($value)) {
			$message = 'The "' . $name . '" must a valid number.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;	
		}
		if ($notEmpty && $p->isInteger && 
			filter_var($value, FILTER_VALIDATE_INT) === false) {
			$message = 'The "' . $name . '" must a valid integer.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;	
		}
		if (!$p->isNullable && (is_null($value) || !$notEmpty)) {
			$message = 'The "' . $name . '" must not be empty or omitted.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;		
		}
		if ($mode == self::DATA && 
			$p->isUnique &&
			Db::has($table, array($dbFieldName => $value))) {
			$message = 'The "' . $name . '" already exists.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;			
		}
		if ($mode == self::WHERE && 
			Db::has($table, array($dbFieldName => $value))) {
			$message = 'The "' . $name . '" does not exists.';
			Errors::append(self::$_errorCode, $message, $name);
			return false;			
		}
		return true;
	}

	private static function _validate($mode, $table, $array, &$result, &$mappings)
	{
		foreach ($array as $key => $value) {
			if (Db::_inMedooSpecialKeys($key)) {
				if (is_array($value))
					self::validate($mode, $table, $value, $result, $mappings);
			} else {
				$result &= self::_validateValue($mode, $table, $key, $value, $mappings);
			}
		}
	}

	public static function validate($mode, $table, $array, $mappings=array())
	{
		$result = true;
		self::_validate($mode, $table, $array, $result, $mappings);
		return $result;
	}

	/**
	 * Bulk extract the fields from a source via case-agnostic match.
	 * The mappings is an assoc array of 
	 * '{dbFieldName}' => '{sourceFieldName}' pairs
	 * @param  array  $source   The source array.
	 * @param  array  $mappings The mappings array.
	 * @return array            The extracted result.
	 */
	public static function get($source, $mappings)
	{
		$collected = array();
		foreach ($mappings as $dbFieldName => $sourceFieldName) {
			$collected[$dbFieldName] = Cases::get($source, $sourceFieldName);
		}
		return $collected;
	}

	/**
	 * Return the non null values in the array.
	 * @param  array   $array   The array to filter.
 	 * @param  boolean $recurse Whether to recurse.
	 * @return array            An array without the null values.
	 */	
	public static function nonNulls($array, $recurse=true) 
	{
		$results = array(); 
		foreach ($array as $key => $value) {
			if ($recurse && is_array($value)) 
				$results[$key] = self::nonNulls($value, true);
			else if (!is_null($value)) $results[$key] = $value;
		}
		return $results;
	}

}