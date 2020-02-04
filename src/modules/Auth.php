<?php

namespace MidPay;

class Auth
{
	private static $_inited = false;
	private static $_success = false;
	private static $_userId = null;
	private static $_userType = null;
	private static $_authType = null;
	private static $_sessionId = null;
	private static $_authLogId = null;
	private static $_apiKey = null;

	const API_KEY = 'API_KEY';
	const SESSION = 'SESSION';

	const SUCCESS = 'SUCCESS';
	const FAILURE = 'FAILURE';

	const LOG_TYPE = 'AUTH';

	/**
	 * Tries to authenticate based on session.
	 * @param  string $sessionId The session id.
	 * @return boolean           Whether success or not.
	 */
	private static function _authSession($sessionId)
	{
		if (!is_null($sessionId)) {
			$sessionRow = Db::get('sessions', array(
				'user_id',
			), array(
				'session_id' => $sessionId
			));

			if ($sessionRow) {
				self::$_authType = self::API_SESSION;
				self::$_sessionId = $sessionId;
				self::$_userId = $sessionRow['user_id'];
				$userRow = Db::get('users', array(
					'type'
				), array(
					'user_id' => self::$_userId
				));
				if ($userRow) {
					self::$_userType = $row['user_type'];	
				}
				return true;	
			}
		}
		return false;
	}

	/**
	 * Tries to authenticate based on api key.
	 * @param  string $sessionId The api key.
	 * @return boolean           Whether success or not.
	 */
	private static function _authApiKey($apiKey)
	{
		if (!is_null($apiKey)) {
			$userRow = Db::get('users', array(
				'user_id',
				'type'
			), array(
				'api_key' => $apiKey
			));

			if ($userRow) {
				self::$_apiKey = $apiKey;
				self::$_authType = self::API_KEY;
				self::$_userId = $userRow['user_id'];
				self::$_userType = $userRow['type'];
				return true;	
			}
		}
		return false;	
	}

	/**
	 * Initialize the authentication variables 
	 * when any method of this static class is first called.
	 */
	private static function _init()
	{
		if (!self::$_inited) {
			self::$_inited = true;

			self::$_success = (
				self::_authSession(Params::body('session')) ||
				self::_authSession(Params::body('sessionId')) ||
				self::_authSession(Params::headers('session')) ||
				self::_authSession(Params::headers('sessionId')) ||
				self::_authSession(Params::cookies('session')) ||
				self::_authSession(Params::cookies('sessionId')) ||

				self::_authApiKey(Params::headers('API-Key')) ||
				self::_authApiKey(Params::headers('key')) ||
				self::_authApiKey(Params::body('API-Key')) ||
				self::_authApiKey(Params::body('key'))
			);

			$logMessage = Json::encode(array(
				'client' => Params::client(),
				'type' => self::$_authType
			));
			
			Logs::insert(
				self::LOG_TYPE, 
				self::$_success ? self::SUCCESS : self::FAILURE, 
				$logMessage
			);
		}
	}

	/**
	 * Returns whether the user is authenticated.
	 * @return boolean
	 */
	public static function success()
	{
		self::_init();
		return self::$_success;
	}

	/**
	 * Returns the user id of the current user.
	 * @return string
	 */
	public static function userId()
	{
		self::_init();
		return self::$_userId;
	}

	/**
	 * Returns the type of authentication used
	 * @return string
	 */
	public static function type()
	{
		self::_init();
		return self::$_authType;
	}

	/**
	 * Returns the api key, if authenticated.
	 * @return string
	 */
	public static function apiKey()
	{
		self::_init();
		return self::$_apiKey;
	}

	public static function authLogId()
	{
		self::_init();
		return self::$_authLogId;
	}

	public static function unauthorized()
	{
		http_response_code(401);
		Errors::append(401, 'Unauthorized');
		die();
	}
}