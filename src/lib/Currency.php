<?php

namespace MidPay;

/**
 * A static class for currency calculations.
 */
class Currency
{
	private static $_scale = 4;

	/**
	 * Sets or gets the current scale (The number of decimal places).
	 * @param  integer $scale The current scale.
	 * @return integer        The current scale.
	 */
	public static function scale($scale=null)
	{
		if (is_null($scale)) return self::$_scale;
		return self::$_scale = (int) $scale;
	}

	/**
	 * Returns the zero value.
	 * @return string  The zero value.
	 */
	public static function zero()
	{
		$val = '0';
		if (self::$_scale > 0) {
			$val .= '.';
			for ($i = 0; $i < self::$_scale; ++$i)
				$val .= '0';
		}
		return $val;
	}

	/**
	 * Compares two values.
	 * Returns +1 if $a > $b .
	 * Returns  0 if $a == $b .
	 * Returns -1 if $a < $b .
	 * @param  string  $a The left operand.
	 * @param  string  $b The right operand.
	 * @return integer    The comparison result.
	 */
	public static function comp($a, $b)
	{
		return bccomp($a, $b, self::$_scale);
	}

	/**
	 * Checks if the value is valid, 
	 * and optionally compares it against another value.
	 * Usage:
	 * // checks if the string is a valid currency
	 *     Currency::check('0.12')
	 * // checks if the value is < 0.5
	 *     Currency::check('0.12', '<', '0.5') 
	 * @param  mixed  $value    The value.
	 * @param  string $comp     '>', '<', '>=', '<=', '!=', '=='
	 * @param  mixed  $y        The value to compare against.
	 * @return boolean          True if the condition is fufilled.
	 */
	public static function check($x, $comp='', $y='')
	{
		$valid = isset($x) && is_numeric($x);
		if ($valid && $comp != '' && is_numeric($y)) {
			$c = self::comp($x, $y);
			switch (trim($comp)) {
				case '>': return $c > 0;
				case '<': return $c < 0;
				case '>=': return $c >= 0;
				case '<=': return $c <= 0;
				case '==': return $c == 0;
				case '!=': return $c != 0;
				default: return false;
			}
		}
		return $valid;
	}

	/**
	 * Formats the currency to current scale.
	 * @param  string $x The value.
	 * @return string    The formatted value.
	 */
	public static function format($x)
	{
		return bcadd($x, '0', self::$_scale);
	}

	/**
	 * Adds two currency values.
	 * @param  string $a The left operand.
	 * @param  string $b The right operand.
	 * @return string    $a + $b.
	 */
	public static function add($a, $b)
	{
		return bcadd($a, $b, self::$_scale);
	}

	/**
	 * Subtracts a currency value from another.
	 * @param  string $a The left operand.
	 * @param  string $b The right operand.
	 * @return string    $a - $b.
	 */
	public static function sub($a, $b)
	{
		return bcsub($a, $b, self::$_scale);	
	}
}