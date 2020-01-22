<?php

require_once('../lib/init.php');
require_once('../lib/external/Requests.php');
Requests::register_autoloader();

class Callback
{
	function _var_sub(&$array, &$data) 
	{
		foreach ($array as $key => &$value) {
			if (is_string($value)) {
				if (preg_match_all('/\{\$[A-z0-9_-]+\}|\$[A-z0-9_-]+/', $value, $matches)) {
					foreach ($matches as $g) {
						$w = Params::case_insensitive_words(trim($g[0], '{}$'));
						foreach ($data as $k => $v) 
							if (Params::case_insensitive_words($k) === $w)
								$value = str_replace($g[0], $v, $value);
					}
				}
			} else if (is_array($value)) {
				Callback::_var_sub($value, $data);
			}
		}
	}

	function request($callback, $data=array()) 
	{
		try {
			$ori_callback = $callback;
			if (is_string($callback)) 
				$callback = json_decode($callback, true);
			
			$url = '';
			$method = 'GET';
			$to_send = array('headers' => array(), 'query' => array(), 'body' => array());
			
			if (is_array($callback)) { 
				// If the callback is some json which defines a custom format:
				$callback = array_combine(
					array_map('strtolower', array_keys($callback)),
					array_values($callback));
				if (isset($callback['url'])) 
					$url = $callback['url'];
				if (isset($callback['method'])) 
					$method = strtoupper(trim($callback['url']));
				foreach ($to_send as $key => $value) 
					if (isset($callback[$key])) 
						$to_send[$key] = $callback[$key];
				Callback::_var_sub($to_send, $data);

			} else if (is_null($callback) && is_string($ori_callback)) {
				// If the callback is simply a string which may be a url:
				// We'll just set it as the url,
				$url = $ori_callback;
				// And send all the data in the body.
				$to_send['body'] = $data;
				// And set the method to POST
				$method = 'POST'; 
			}
			if (strlen($url)) {
				// We'll directly smack the query params into the url,
				// Cuz the Requests library doesn't allow us to do 
				// both query and body params together!
				if (!empty($data = $to_send['query'])) {
					$url_parts = parse_url($url);
					if (empty($url_parts['query'])) {
						$query = $url_parts['query'] = '';
					} else {
						$query = $url_parts['query'];
					}
					$query = trim($query.'&'.http_build_query($data, null, '&'), '&');
					if (empty($url_parts['query'])) {
						$url .= '?' . $query;
					} else {
						$url = str_replace($url_parts['query'], $query, $url);
					}
				}
				return Requests::request($url, $to_send['headers'], $to_send['body'], $method, 
					array('data_format' => 'body') );	
			} 
		} catch (Exception $e) { }
		return NULL;
	}	
}

$rows = DB::select('callbacks', '*', array('AND' => array(
	'retries_left[>]' => 0,
	'next_attempt[<=]' => Timestamp::now()
)));

$updates = array();
foreach ($rows as $row) {
	$deserialized = json_decode($row['serialized'], true);
	$request = Callback::request($deserialized['callback'], $deserialized['data']);
	if (!is_null($request)) {
		$now = Timestamp::now();
		if ($request->status_code == 200) {
			$updates[$row['id']] = array(
				'retries_left' => 0,
				'last_attempt' => $now
			);
			$has_delete = true;
		} else {
			$interval = ((int)($row['last_attempt']) - (int)($row['next_attempt'])) * 
				(int)($deserialized['interval_growth_exponent']);
			if (!(0 < $interval && $interval < 86400)) $interval = 60;
			$retries_left = (int)($row['retries_left']) - 1;
			$updates[$row['id']] = array(
				'retries_left' => $retries_left,
				'last_attempt' => $now,
				'next_attempt' => $now + $interval
			);
			if ($retries_left <= 0) $has_delete = true;
		}
	}
}

if (sizeof($updates) > 0) {
	foreach ($updates as $id => $row) {
		if ($row['retries_left'] > 0) {
			DB::update('callbacks', $row, array('id' => $id));
		} else {
			DB::delete('callbacks', array('id' => $id));
		}
	}
}