<?php

namespace MidPay;

/**
 * A static class for handling the response.
 */
class Response
{
	private static $_scriptStartTime;
	private static $_keyValues;
	private static $_closed = false;

	private static function _init()
	{
		if (!isset(self::$_keyValues)) {
			self::$_keyValues = array();
		}
	}

	/**
	 * Get a value from the response.
	 * @param  mixed $key     The key of the value.
	 * @param  mixed $default The default value to return if the key is not found.
	 * @return mixed          The stored value.
	 */
	public static function get($key, $default=null) 
	{
		self::_init();
		if (isset(self::$_keyValues[$key])) {
			return self::$_keyValues[$key];
		}
		return $default;
	}

	/**
	 * Set a value in the response.
	 * @param  mixed $key   The key of the value.
	 * @param  mixed $value The value to store
	 * @return mixed        The stored value.
	 */
	public static function set($key, $value) 
	{
		self::_init();
		return self::$_keyValues[$key] = $value;
	}

	/**
	 * Sets the headers to enable CORS.
	 */
	public static function enableCors()
	{
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Max-Age: 1000');
		header('Access-Control-Allow-Headers: '.
			'X-Requested-With, Content-Type, Origin, Cache-Control, '.
			'Pragma, Authorization, Accept, Accept-Encoding');
		header('Access-Control-Allow-Methods: '.
			'PUT, POST, GET, OPTIONS, DELETE');
	}

	/**
	 * Returns the response as a json string.
	 * @return string The response as a json string.
	 */
	public static function toJson() 
	{
		self::_init();
		$response = array_merge(self::$_keyValues, array(
			'errors' => Errors::errors()
		));
		if (isset(self::$_scriptStartTime)) {
			$response['time_elapsed'] = microtime(true) - self::$_scriptStartTime;
		}
		return Json::encode($response);
	}

	/**
	 * Outputs the response.
	 */
	public static function output() 
	{
		$hasTransaction = Db::hasTransaction();
		if ($hasTransaction) 
			Db::commit();
		if (!self::$_closed)
			echo self::toJson();
		if ($hasTransaction) 
			Db::beginTransaction();
	}

	/**
	 * Closes the connection and outputs the response.
	 * After closing the connection, the server can then do 
	 * any further processing without the client waiting.
	 */
	public static function close() 
	{
		self::output();
		ignore_user_abort(true);
		set_time_limit(0);
		self::$_closed = true;
	}

	/**
	 * Register the response to be output when the script ends.
	 * @param  boolean $outputTimeElapsed Whether to output the time elapsed.
	 */
	public static function registerOutputOnExit($outputTimeElapsed=false) 
	{
		if ($outputTimeElapsed) self::$_scriptStartTime = microtime(true);
		register_shutdown_function('MidPay\Response::output');
	}
}