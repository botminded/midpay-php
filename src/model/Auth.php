<?php

namespace MidPay;

class Auth
{
	private static $_userId;
	private static $_userType;
	private static $_authType;
	private static $_sessionId;

	private static function _initSession($sessionId)
	{
		if (!is_null($sessionId)) {
			$sessionRow = Db::get('sessions', array(
				'user_id',
			), array(
				'session_id' => $sessionId
			));

			if ($sessionRow) {
				self::$_authType = 'SESSION';
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

	private static function _initApiKey($apiKey)
	{
		if (!is_null($apiKey)) {
			$userRow = Db::get('users', array(
				'user_id',
				'type'
			), array(
				'api_key' => $apiKey
			));

			if ($userRow) {
				self::$_authType = 'API_KEY';
				self::$_userId = $userRow['user_id'];
				self::$_userType = $userRow['type'];
				return true;	
			}
		}
		return false;	
	}

	private static function _init()
	{
		if (!isset(self::$_userId)) {
			self::$_authType = null;
			self::$_userId = null;

			if (self::_initSession(Params::body('session')) ||
				self::_initSession(Params::body('sessionId')) ||
				self::_initSession(Params::headers('session')) ||
				self::_initSession(Params::headers('sessionId')) ||
				self::_initSession($_COOKIE['session']) ||
				self::_initSession($_COOKIE['sessionId']))
				return;

			if (self::_initApiKey(Params::headers('API-Key')) ||
				self::_initApiKey(Params::headers('API_Key')) ||
				self::_initApiKey(Params::headers('apiKey')) ||
				self::_initApiKey(Params::headers('key')) ||
				self::_initApiKey(Params::body('API-Key')) ||
				self::_initApiKey(Params::body('API_Key')) ||
				self::_initApiKey(Params::body('apiKey')) ||
				self::_initApiKey(Params::body('key')))
				return;
		}
	}

	public static function userId()
	{
		self::_init();
		return self::$_userId;
	}

	public static function type()
	{
		self::_init();
		return self::$_authType;
	}

	public static function unauthorized()
	{
		http_response_code(401);
		Errors::append('401', 'Unauthorized');
		die();
	}
}