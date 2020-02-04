<?php

namespace MidPay;

class Enums
{
	/**
	 * Returns an array of key => value for all the 
	 * constants in the class.
	 * @param  string $className The class name.
	 * @param  string $prefix    The prefix.
	 * @param  string $sep       The separator.
	 * @return array             An array of key => value.
	 */
	public static function from($className, $prefix=null, $sep='_')
	{
		static $cache;
		if (!isset($cache[$className])) {
			$cache[$className] = (new \ReflectionClass($className))->getConstants();
		}
		if (is_null($prefix)) {
			return $cache[$className];	
		} 
		$p = $prefix . $sep;
		$c = $className . chr(1) . $p;
		if (!isset($cache[$c])) {
			$assoc = self::from($className);
			$filtered = array();
			$len = strlen($p);
			foreach ($assoc as $key => $value) {
				if (substr($key, 0, $len) == $p) {
					$filtered[substr($key, $len)] = $value;
				}
			}
			$cache[$c] = $filtered;
		}
		return $cache[$c];
	}

	const COERCE_KEY_TO_VALUE = 0;
	const COERCE_VALUE_TO_VALUE = 1;
	const COERCE_KEY_TO_KEY = 2;
	const COERCE_VALUE_TO_KEY = 3;

	public static function coerce($assoc, $keyOrValue, $mode)
	{
		$keyOrValue = Cases::words($keyOrValue);
		foreach ($assoc as $key => $value) {
			switch ($mode) {
				case self::COERCE_KEY_TO_VALUE:
					if (Cases::words($key) == $keyOrValue)
						return $value;
				case self::COERCE_KEY_TO_KEY:
					if (Cases::words($key) == $keyOrValue)
						return $key;
				case self::COERCE_VALUE_TO_KEY:
					if (Cases::words($value) == $keyOrValue)
						return $key;
				case self::COERCE_VALUE_TO_VALUE:
					if (Cases::words($value) == $keyOrValue)
						return $value;
			}
		}
	}
}
