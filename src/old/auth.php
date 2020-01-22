<?php

/**
 * A static class for authorization functions.
 */
class Auth 
{
	const KEY = 2;
	const ADMIN = 1;

	/**
	 * Returns the current username for the admin or key.
	 * If no username if found, returns NULL.
	 */
	public static function username() 
	{
		static $username;
		if (isset($username)) return $username;
		if (Auth::master()) return '';
		if (!is_null($session = Params::request('session')) &&
			($row = DB::get('sessions', array('username'), array('id' => $session))) ) {
			return $username = $row['username'];
		}
		if (!is_null($key = Params::request('key')) &&
			($row = DB::get('users', array('username'), array('key' => $key))) ) {
			return $username = $row['username'];
		}
		return NULL;
	}

	/**
	 * Returns whether the master password is provided.
	 */
	public static function master() 
	{
		return Params::request('master') === 
			'THIS_PASSWORD_MUST_BE_CHANGED_TO_A_STRONG_SECRET_BEFORE_PUSHING_TO_PRODUCTION';
	}

	/**
	 * Returns if the current session exists.
	 */
	public static function session() 
	{
		if (Params::is_not_empty(Params::request('session')))
			return DB::has('sessions', array('id' => Params::request('session')));
		return false;
	}

	/**
	 * Returns if the current API key exists.
	 */
	public static function key() 
	{
		$key = NULL;
		if (is_null($key)) {
			$auth = Params::request('Authorization'); // Case-insensitive...
			// Basic, Bearer, or nothing... We just need the key!
			if (Params::is_not_empty($auth)) {
				$auth_re = '/^(\s*?\S+\s*?)?(.+)/'; 
				if (preg_match($auth_re, $auth, $match)) {
					$m1 = trim($match[1]);
					$m2 = trim($match[2]);
					$key = strlen($m2) > 0 ? $m2 : $m1;
				}	
			}
		}
		if (is_null($key)) {
			if (Params::is_not_empty(Params::request('key')))
				$key = Params::request('key');
		}
		if (!is_null($key))
			return DB::has('users', array('key' => $key));
		return false;
	}

	/**
	 * Returns if either the master password, a valid session, 
	 * or a valid API key is provided.
	 */
	public static function any() 
	{
		return Auth::master() || Auth::session() || Auth::key();
	}

	/**
	 * Sets the response to unauthorized and terminates the script.
	 */
	public static function unauthorized() 
	{
		http_response_code(401);
		Errors::append('Unauthorized');	
		die();
	}

	/**
	 * Returns a new unique session.
	 */
	public static function gen_session() 
	{
		for ($i = 0; $i < 9; $i++) 
			if (!DB::has('sessions', array('id' => $r = Crypto::rand_base36(31)))) 
				return $r;
		return '';
	}

	/**
	 * Returns a new unique API key.
	 */
	public static function gen_key() 
	{
		for ($i = 0; $i < 9; $i++) 
			if (!DB::has('users', array('key' => $r = Crypto::rand_base36(31)))) 
				return $r;
		return '';	
	}
}

