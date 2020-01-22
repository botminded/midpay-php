<?php

namespace MidPay;

/**
 * This static class help us extract parameters from 
 * the headers, url, and body.
 */
class Params
{
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
		static $client;
		if (!isset($client)) {
			$keys = array(
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'REMOTE_ADDR',
				'REMOTE_HOST',
				'HTTP_REFERER',
				'HTTP_USER_AGENT'
			);
			$client = array();
			foreach ($keys as $k) {
				if (isset($_SERVER[$k]))
					$client[$k] = $_SERVER[$k];
			}
		} 
		return $client;
	}

	/**
	 * Returns the component of the url path, or the whole url path.
	 * To get an index from the back, use a negative index.
	 * All components will be converted to lowercase.
	 * @param  integer $index The index of the path component. 
	 *                        Use null to get the whole path.
	 * @return mixed          The path component, or the whole url path.
	 */
	public static function url($index=null) 
	{
		static $url;
		if (is_null($index)) {
			if (isset($url)) return $url;
			$r = $_SERVER['REQUEST_URI']; 
			$r = strtok($r, '?');
			$s = explode('/', $_SERVER['SCRIPT_NAME']); 
			return ($url = array_map('strtolower', 
				array_values(array_diff(isset($r) ? explode('/', $r) : $s, $s))));	
		} else {
			$p = self::url();			
			if ($index < 0) $index = sizeof($p) + $index;
			return $index < sizeof($p) && $index >= 0 ? $p[$index] : '';
		}
	}

	/**
	 * Returns the header value for the key.
	 * Uses case-insensitive search.
	 * @param  mixed   $key The key of the header,
	 * @return string       The value of the header.
	 */
	public static function headers($key=null)
	{
		static $headers;
		if (!isset($headers)) {
			$prefix = 'http_';
			$headers = array();
			$o = strlen($prefix);
			foreach ($_SERVER as $k => $v) 
				if (substr(($k = strtolower($k)), 0, $o)  == $prefix) {
					$k = str_replace('-', '_', strtolower('' . substr($k, $o)));
					$headers[$k] = $v;
				}
		}
		if (is_null($key)) {
			return $headers;
		}
		$key = str_replace('-', '_', strtolower('' . $key));
		foreach ($headers as $k => $v) {
			if ($key == $k)
				return $v;
		}
		return null;
	}
	
	/**
	 * Returns the header value for the key.
	 * Uses case-insensitive search.
	 * @param  mixed   $key The key of the header,
	 * @return string       The value of the header.
	 */
	public static function cookies($key=null)
	{
		if (is_null($key)) {
			return $_COOKIE;
		}
		foreach ($_COOKIE as $k => $v)  {
			if (strtolower($k) == $key)
				return $v;
		}
		return null;
	}

	private static $_body;
	private static $_isJson;
	
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
	 * @return mixed       The value for the key.
	 */
	public static function body($key=null) 
	{
		self::_parseBody();
		if (is_null($key)) {
			return self::$_body;
		} else {
			if (isset(self::$_body[$key])) {
				return self::$_body[$key];
			} 
			return null;
		}	
	}
	
}
