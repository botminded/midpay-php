<?php

namespace MidPay;

/**
 * A static class for logging of errors to be outputed in the response.
 */
class Errors
{
	private static $_errors;

	private static function _init()
	{
		if (!isset(self::$_errors)) 
			self::$_errors = array();
	}

	/**
	 * Returns all errors.
	 * @return array 
	 */
	public static function errors()
	{
		self::_init();
		return self::$_errors;
	}

	/**
	 * Returns if there are any errors.
	 * @return boolean 
	 */
	public static function any()
	{
		self::_init();
		return sizeof(self::$_errors) > 0;
	}

	/**
	 * Appends the error.
	 * @param  integer $code    The error code.
	 * @param  string  $message The message for the error.
	 * @param  string  $field   The field of the error.
	 */
	public static function append($code, $message=null, $field=null)
	{
		$e = array('code' => $code);
		if (!is_null($message)) 
			$error['message'] = $message;
		if (!is_null($field)) 
			$error['field'] = $field;
		self::$_errors[] = $error;
	}

}