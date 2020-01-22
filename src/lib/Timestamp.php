<?php

namespace MidPay;

/**
 * A static class for creating and rounding down timestamps.
 */
class Timestamp
{	
	/**
	 * Sets or gets the default timezone.
	 * @param  string $timezone The default timezone.
	 * @return string           The default timezone.
	 */
	public static function defaultTimezone($timezone=null) 
	{
		static $current;
		$changed = false;
		if (is_null($timezone) && !isset($current)) {
			$timezone = 'Singapore';
			$changed = true;
		} else if (!is_null($timezone) && isset($current) && $timezone != $current) {
			$changed = true;
		}
		if ($changed) {
			date_default_timezone_set($timezone);
			$current = $timezone;
		}
		return $current;
	}

	/**
	 * Returns the current timestamp.
	 * @return integer
	 */
	public static function now() 
	{ 
		self::defaultTimezone();
		return time();
	}

	/**
	 * Returns the number of microseconds for the current second.
	 * @return integer
	 */
	public static function usec()
	{
		return explode(' ', microtime())[0];
	}

	/**
	 * Rounds down the timestamp to the nearest time point.
	 * Options for $to:
	 * 'year', 'month', 'day', 'sunday', 'monday', 'hour', 'minute'
	 * @param  string  $to        The time point to round to
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function floor($to, $timestamp=null) 
	{
		self::defaultTimezone();
		if (is_null($timestamp)) $timestamp = Timestamp::now();
		$c = explode('-', date('Y-n-d-N-j', $timestamp));
		if (strcasecmp($to, 'year') == 0) {
			$timestamp = mktime(0,0,0,1,1,$c[0]);
		} else if (strcasecmp($to, 'month') == 0) { 
			$timestamp = mktime(0,0,0,$c[1],1,$c[0]);
		} else if (strcasecmp($to, 'day') == 0) { 
			$timestamp = mktime(0,0,0,$c[1],$c[2],$c[0]);
		} else if (strcasecmp($to, 'monday') == 0) { 
			$timestamp = mktime(0,0,0,$c[1],$c[4]-$c[3]+1,$c[0]);
		} else if (strcasecmp($to, 'sunday') == 0) { 
			$timestamp = mktime(0,0,0,$c[1],$c[4]-$c[3],$c[0]);
		} else if (strcasecmp($to, 'hour') == 0) { 
			$timestamp = mktime(0,0,$c[5],$c[1],$c[2],$c[0]);
		} else if (strcasecmp($to, 'minute') == 0) { 
			$timestamp = mktime(0,$c[6],$c[5],$c[1],$c[2],$c[0]);
		}
		return $timestamp;
	}
	
	/**
	 * Rounds down the time to the nearest day.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function day($timestamp=null) 
	{ 
		return Timestamp::floor('day', $timestamp); 
	}

	/**
	 * Rounds down the time to the nearest monday.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function monday($timestamp=null) 
	{ 
		return Timestamp::floor('monday', $timestamp); 
	}

	/**
	 * Rounds down the time to the nearest sunday.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function sunday($timestamp=null) 
	{ 
		return Timestamp::floor('sunday', $timestamp); 
	}

	/**
	 * Rounds down the time to the nearest hour.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function hour($timestamp=null) 
	{ 
		return Timestamp::floor('hour', $timestamp); 
	}

	/**
	 * Rounds down the time to the nearest minute.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function minute($timestamp=null) 
	{ 
		return Timestamp::floor('minute', $timestamp); 
	}

	/**
	 * Rounds down the time to the nearest year.
	 * @param  integer $timestamp The timestamp. Uses the current timestamp if omitted.
	 * @return integer            The rounded down timestamp.
	 */
	public static function year($timestamp=null) 
	{ 
		return Timestamp::floor('year', $timestamp); 
	}
}