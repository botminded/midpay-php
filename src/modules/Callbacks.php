<?php

namespace MidPay;

require_once(realpath(dirname(__FILE__)).'/../lib/external/Requests.php');
use Requests;

Requests::register_autoloader();

class Callbacks
{		
	const ENCODING_X_WWW_FORM_ENCODED = 'application/x-www-form-urlencoded';
	const ENCODING_JSON = 'application/json';

	const DEFAULT_METHOD = 'POST';

	public static function parse($callback, $defaultMethod=null, $defaultEncoding=null)
	{
		if (is_string($callback)) {
			$decoded = Json::decode($callback);
			if (is_array($callback)) {
				$callback = $decoded;
			} else {
				$callback = array(
					'url' => $callback
				);	
			}
		}
		if (is_string($callback)) {
			$callback = array(
				'url' => $callback
			);
		}
		if (!isset($callback['method'])) {
			$callback['method'] = is_null($defaultMethod) ? 
				self::DEFAULT_METHOD :
				$defaultMethod;
		}
		if (!isset($callback['encoding'])) {
			$callback['encoding'] = is_null($defaultEncoding) ?
				(Params::isJson() ?
					self::ENCODING_JSON : 
					self::ENCODING_X_WWW_FORM_ENCODED) :
				$defaultEncoding;
		}
		return $callback;
	}

	public static function isValid($callback) 
	{
		$callback = self::parse($callback);
		if (!is_null($callback) &&
			is_array($callback) &&
			isset($callback['url']) && 
			!empty($callback['url']) && 
			filter_var($callback['url'], FILTER_VALIDATE_URL)) {
			return true;
		}
		return false;
	}

	public static function insert($callback, $data, 
		$numRetries=8,
		$intervalGrowthExponent=2,
		$intervalInitialValue=2)
	{
		$callback = self::parse($callback);
		if (!self::isValid($callback)) 
			return null;

		try {
			$now = Timestamp::now();

			$callbackId = Crypto::randUniqueString('callbacks', 'callback_id');
			Db::insert('callbacks', array(
				'callback_id' => $callbackId,
				'retries_left' => $numRetries + 1,
				'last_attempt' => $now - $intervalInitialValue,
				'next_attempt' => $now,
				'serialized' => Json::encode(array(
					'callback' => $callback, 
					'data' => $data,
					'backoff' => $intervalGrowthExponent
				))
			));
			return $callbackId;	

		} catch (Exception $e) {}

		return null;
	}

	private static function _request($callback, $data)
	{
		try {
			$toSend = array(
				'headers' => array(), 
				'query' => array(), 
				'body' => array()
			);

			foreach ($toSend as $k => $v) 
				if (is_array($data) && isset($data[$k]) && is_array($data[$k])) 
					$toSend[$k] = $data[$k];

			if (!isset($callback['url']))
				return null;
			
			$url = $callback['url'];
			$method = $callback['method'];
			$encoding = $callback['encoding'];
				
			if (!empty($toSend['query'])) {

				$queryString = http_build_query($toSend['query'], null, '&');
				$urlParts = parse_url($url);

				if (empty($urlParts['query'])) {
					$queryString = trim($queryString, '&');
					$url .= '&' . $queryString;
				} else {
					$queryString = trim($urlParts['query'] . '&' . $queryString, '&');
					$url = str_replace($urlParts['query'], $queryString, $url);
					$url .= '&' . $queryString;
				}
			}
			$options = array('data_format' => 'body');

			if ($encoding == self::ENCODING_JSON) {
				$toSend['body'] = Json::encode($toSend['body']);
				$options = array();
			}

			return Requests::request($url, 
				$toSend['headers'], 
				$toSend['body'], 
				$method, 
				$options
			);	

		} catch (Exception $e) { }
		return null;
	}

	public static function sendPendings($deleteDone=false)
	{
		$rows = Db::select('callbacks', '*', array('AND' => array(
			'retries_left[>]' => 0,
			'next_attempt[<=]' => Timestamp::now()
		)));
		
		$updates = array();
		foreach ($rows as $row) {
			$deserialized = Json::decode($row['serialized']);
			$now = Timestamp::now();
			if (self::isValid($deserialized['callback'])) {
				$request = self::_request($deserialized['callback'], $deserialized['data']);
				if (!is_null($request)) {
					
					if ($request->status_code == 200) {
						$updates[$row['callback_id']] = array(
							'retries_left' => 0,
							'last_attempt' => $now
						);
						$hasDone = true;
					} else {
						$interval = ((int)($row['last_attempt']) - (int)($row['next_attempt'])) * 
							(int)($deserialized['backoff']);
						if (!(0 < $interval && $interval < 86400)) $interval = 60;
						$retriesLeft = (int)($row['retries_left']) - 1;
						$updates[$row['callback_id']] = array(
							'retries_left' => $retriesLeft,
							'last_attempt' => $now,
							'next_attempt' => $now + $interval
						);
						if ($retriesLeft <= 0) $hasDone = true;
					}
				}	
			} else {
				$updates[$row['callback_id']] = array(
					'retries_left' => 0,
					'last_attempt' => $now
				);
				$hasDone = true;
			}
		}

		if (sizeof($updates) > 0) {
			foreach ($updates as $id => $row) {
				if ($row['retries_left'] > 0) {
					Db::update('callbacks', $row, array('callback_id' => $id));
				} else {
					if ($deleteDone && $hasDone)
						Db::delete('callbacks', array('callback_id' => $id));
					else
						Db::update('callbacks', $row, array('callback_id' => $id));
				}
			}
		}
	}
}