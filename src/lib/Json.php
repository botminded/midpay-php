<?php

namespace MidPay;

/**
 * A static class for interoperable JSON parsing.
 * The issue is that an empty PHP array can mean either [] or {}
 * in json. This class helps to solve this problem by adding
 * a chr(1) to all associative arrays to identify them.
 */
class Json
{
	/**
	 * Returns an associative array that contains a tag
	 * to identify it as an associative array in json encoding.
	 * @param  array  $array An existing array to wrap if any.
	 * @return array         The associative array.
	 */
	public static function assoc($array=array())
	{
		if (is_object($array))
			$array = (array) $array;
		if (!is_array($array)) 
			$array = array();
		
		$array[chr(1)] = '';
		return $array;
	}

	/**
	 * Internal function for converting associative arrays
	 * created via Json::assoc() into objects.
	 */
	private static function _assocToObjects(&$t)
	{
		if (is_array($t)) {
			if (isset($t[chr(1)])) {
				unset($t[chr(1)]);
				if (sizeof($t) < 1) {
					$t = (object) array();
					return;
				}
			} 
			foreach ($t as $k => &$v) 
				self::_assocToObjects($v);
		} 
	}

	/**
	 * Encodes the array into a json string, 
	 * with proper treatment of empty associative arrays
	 * created via Json::assoc().
	 * @param  array $array The array to encode.
	 * @return string       The json string.
	 */
	public static function encode($array)
	{
		self::_assocToObjects($array);
		return json_encode($array);
	}

	/**
	 * Internal function for converting objects
	 * created into associative arrays.
	 */
	private static function _objectsToAssoc(&$t)
	{
		if (is_object($t)) {
			$t = (array) $t;
			$t[chr(1)] = '';
		}

		if (is_array($t)) {
			foreach ($t as $k => &$v) 
				self::_objectsToAssoc($v);
		} 
	}

	/**
	 * Decodes the string into json.
	 * @param  string $string The string to be decoded.
	 * @return mixed          Returns the array if decoded successfully.
	 *                        Else returns null.
	 */
	public static function decode($string)
	{
		$decoded = json_decode($string);
		if (is_object($decoded) || is_array($decoded))
			self::_objectsToAssoc($decoded);
		return $decoded;
	}	
}
