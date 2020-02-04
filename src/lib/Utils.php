<?php

namespace MidPay;

/**
 * A static class for misc utility functions.
 */
class Utils
{
	/**
	 * Selecting rows from the database returns 
	 * rows which have both integer and string keys.
	 * This function removes the interger keys.
	 * @param  array  $rows The rows. 
	 * @return array        
	 */
	public static function assocRows($rows)
	{
		$collected = array();
		foreach ($rows as $row) {
			$assoc = array();
			foreach ($row as $key => $value) {
				if (!is_int($key)) $assoc[$key] = $value;
			}
			$collected[] = $assoc;
		}
		return $collected;
	}

	/**
	 * Returns the value for the key in the array.
	 * If the key does not exist, returns null.
	 * @param  string $array The array.
	 * @param  mixed  $key   The key.
	 * @return mixed         The value.
	 */
	public static function get($array, $key)
	{
		if (is_array($array) && isset($array[$key]))
			return $array[$key];
		return null;
	}

	/**
	 * Returns the value at the index in the array.
	 * Allows negative indices to start from the end.
	 * -1 returns the last item, and so on.
	 * If it is out of bounds, returns null.
	 * @param  array   $array The array.
	 * @param  integer $index The index.
	 * @return mixed          The value.
	 */
	public static function at($array, $index)
	{
		if ($index < 0) $index = count($array) + $index;
		return $index < count($array) && $index >= 0 ? $array[$index] : null;
	}

	/**
	 * Returns the last element in an array
	 * If the array is empty, returns null.
	 * @param  array  $array The array.
	 * @return mixed         The last element.
	 */
	public static function last($array)
	{
		return self::at($array, -1);
	}

	
}
