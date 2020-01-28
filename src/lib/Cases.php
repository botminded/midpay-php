<?php

namespace MidPay;

/**
 * A static class for case-agnostic matching.
 * Be it camelCase, StudlyCase, snake_case, kebab-case.
 * It split the string into word boundaries.
 */
class Cases
{
	/**
	 * Returns an array of the words in the string with a case insensitive match.
	 * Returned words are all lowercased.
	 * @param  string $str The string to break up into words.
	 * @return array       The array containing the words.
	 */
	public static function words($str) 
	{
		static $memo;
		if (is_string($str)) {
			if (!isset($memo)) 
				$memo = array();
			if (isset($memo[$str])) 
				return $memo[$str];
			preg_match_all('/(?:(?:^|[A-Z])[a-z]+|[0-9]+|[A-Za-z]+)/', $str, $matches);
			return $memo[$str] = array_map('strtolower', $matches[0]);	
		}
		return $str;
	}

	/**
	 * Uses case-agnostic matching to find a value in the array.
	 * If no value can be found, returns null.
	 * @param  array  $array The array.
	 * @param  string $key   The key. 
	 * @return mixed         The value.
	 */
	public static function get($array, $key)
	{
		$words = self::words($key);
		foreach ($array as $k => $v) {
			if (self::words($k) == $words)
				return $v;
		}
		return null;
	}
}