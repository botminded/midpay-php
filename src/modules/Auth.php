<?php

namespace MidPay;

class Auth
{
	private static $_inited = false;
	private static $_userId = null;
	private static $_userType = null;
	private static $_authType = null;
	private static $_sessionId = null;
	private static $_authLogId = null;

	private static function _authSession($sessionId)
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
		if (!self::$_inited) {
			self::$_inited = true;

			$authed = (
				self::_authSession(Params::body('session')) ||
				self::_authSession(Params::body('sessionId')) ||
				self::_authSession(Params::headers('session')) ||
				self::_authSession(Params::headers('sessionId')) ||
				self::_authSession(Params::cookies('session')) ||
				self::_authSession(Params::cookies('sessionId')) ||

				self::_authApiKey(Params::headers('API-Key')) ||
				self::_authApiKey(Params::headers('API_Key')) ||
				self::_authApiKey(Params::headers('apiKey')) ||
				self::_authApiKey(Params::headers('key')) ||
				self::_authApiKey(Params::body('API-Key')) ||
				self::_authApiKey(Params::body('API_Key')) ||
				self::_authApiKey(Params::body('apiKey')) ||
				self::_authApiKey(Params::body('key'))
			);

			$logMessage = Json::encode(array(
				'client' => Params::client(),
				'type' => self::$_authType
			));
			
			if ($authed) {
				self::$_authLogId = Log::insert('AUTH', 'SUCCESS', $logMessage);
			} else {
				self::$_authLogId = Log::insert('AUTH', 'FAILURE', $logMessage);
			}
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