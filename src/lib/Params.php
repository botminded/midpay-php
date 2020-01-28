<?php

namespace MidPay;

/**
 * This static class help us extract parameters from 
 * the headers, url, and body.
 */
class Params
{
	private static $_body;
	private static $_headers;
	private static $_client;
	private static $_isJson;
	private static $_url;

	private static function _get($array, $key)
	{
		return is_null($key) ? $array : Cases::get($array, $key);
	}

	/**
	 * Returns the HTTP method in UPPERCASE.
	 * @param  string $is If provided, returns if the http method is the same.
	 * @return string     The HTTP method in UPPERCASE.
	 */
	public static function method()
	{
		return strtoupper(trim($_SERVER['REQUEST_METHOD']));
	}
	
	/**
	 * Returns an array containing client information.
	 * @return array 
	 */
	public static function client()
	{
		if (!isset(self::$_client)) {
			$keys = array(
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'REMOTE_ADDR',
				'REMOTE_HOST',
				'HTTP_REFERER',
				'HTTP_USER_AGENT'
			);
			self::$_client = array();
			foreach ($keys as $k) {
				if (isset($_SERVER[$k]))
					self::$_client[$k] = $_SERVER[$k];
			}
		} 
		return self::$_client;
	}

	/**
	 * Returns the component of the url path, or the whole url path.
	 * To get an index from the back, use a negative index.
	 * @param  integer $index The index of the path component. 
	 *                        Use null to get the whole path.
	 * @return mixed          The path component, or the whole url path.
	 */
	public static function url($index=null) 
	{	
		if (is_null($index)) {
			if (isset(self::$_url)) return self::$_url;
			$r = $_SERVER['REQUEST_URI']; 
			$r = strtok($r, '?');
			$s = explode('/', $_SERVER['SCRIPT_NAME']); 
			return (self::$_url = 
				array_values(array_diff(isset($r) ? explode('/', $r) : $s, $s)));	
		} else {
			return Utils::at(self::url(), $index);
		}
	}
	
	/**
	 * Returns the query value for the key. ($_GET)
	 * Uses case-agnostic search.
	 * @param  mixed   $key The key.
	 * @return string       The value.
	 */
	public static function query($key=null)
	{
		return self::_get($_GET, $key);
	}

	/**
	 * Returns the header value for the key.
	 * Uses case-agnostic search.
	 * @param  mixed   $key The key.
	 * @return string       The value.
	 */
	public static function headers($key=null)
	{
		if (!isset(self::$_headers)) {
			$prefix = 'http_';
			self::$_headers = array();
			$o = strlen($prefix);
			foreach ($_SERVER as $k => $v) 
				if (substr(($k = strtolower($k)), 0, $o)  == $prefix) {
					self::$_headers[substr($k, $o)] = $v;
				}
		}
		return self::_get(self::$_headers, $key);
	}
	
	/**
	 * Returns the cookie value for the key. ($_COOKIE)
	 * Uses case-agnostic search.
	 * @param  mixed   $key The key.
	 * @return string       The value.
	 */
	public static function cookies($key=null)
	{
		return self::_get($_COOKIE, $key);
	}
	
	/**
	 * Internal function for parsing the body.
	 */
	private static function _parseBody()
	{
		if (isset(self::$_body)) return;
		
		$input = file_get_contents('php://input');
		if (strtolower(self::headers('Content-Type')) == 'application/json') {
			self::$_body = Json::decode($input);
			self::$_isJson = true;
			if (!is_array(self::$_body)) {
				self::$_body = array();
			}
		} else {
			self::$_body = Json::decode($input);
			if (!is_array(self::$_body)) {
				parse_str($input, self::$_body);
				self::$_isJson = false;	
			} else {
				self::$_isJson = true;
			}
		}
	}

	/**
	 * Returns whether the body is json.
	 * @return boolean 
	 */
	public static function isJson()
	{
		self::_parseBody();
		return self::$_isJson;
	}

	/**
	 * Returns the value for the key in the body.
	 * @param  mixed  $key The key.
	 * @return mixed       The value.
	 */
	public static function body($key=null) 
	{
		self::_parseBody();
		return self::_get(self::$_body, $key);
	}
	
}
