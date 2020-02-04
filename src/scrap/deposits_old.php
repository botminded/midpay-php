<?php

namespace MidPay;
use Requests;

if (!Auth::success()) 
	Auth::unauthorized();


if (Params::method() == 'PUT') {

	$mappings = array(
		'user_order_id' => 'orderId',
		'amount' => 'amount'
	);

	$data = Fields::get(Params::body(), $mappings);

	$valid = Fields::validate(
		Fields::DATA, 'orders', array(
			'user_order_id' => Params::url(1),
			'amount' => Params::body('amount')
		), $mappings);

	if (Db::has('orders', array(
		'user_id' => Auth::userId(),
		'user_order_id' => Params::url(1),
	))) {
		$valid = false;
		Errors::append(Fields::VALIATION_ERROR_CODE, 
			'The \'orderId\' already exists.',
			'orderId'
		);
	}
	
	if ($valid) {
		
		$orderId = Crypto::randUniqueString('orders', 'order_id');

		$attemptId = Crypto::randUniqueString('channel_attempts', 'attempt_id');
		$gateway = 'FakePay';
		$channel = 'wechat';

		Db::insert('channel_attempts', array(
			'attempt_id' => $attemptId,
			'gateway_id' => $gateway,
			'channel' => $channel,
			'created' => Timestamp::now(),
			'success' => 0
		));

		// The adapter logic starts here -------------

		$vendorUrl = 'https://midpay.cc/fakepay/' . $orderId;
		$callback = 'https://midpay.cc/dev/ben/api/callback/' . $orderId;
			
		$response = Requests::request($vendorUrl, 
			array(
				'API-Key' => 'A_SUPER_SECRET_API_KEY'
			), Fields::nonNulls(array(
				'amount' => Params::body('amount'), 
				'callback' => $callback,
				'ip' => Params::body('ip'), 
				'return' => Params::body('return'), 
				'testStatus' => Params::body('testStatus')
			)), 
			'POST'
		);

		$responseJson = Json::decode($response->body);
		$status = 'PENDING';
		$responseType = 'URL';
		
		// The adapter logic ends here -------------
		
		if (!is_null(Params::body('testResponseType'))) {
			$responseType = Params::body('testResponseType');
		}
		
		Db::insert('orders', array(
			'order_id' => $orderId, 
			'user_id' => Auth::userId(),
			'user_order_id'=> Params::url(1),
			'attempt_id' => $attemptId,
			'gateway_id' => $gateway,
			'channel' => $channel,

			'status' => $status,
			'amount' => Params::body('amount'),
			
			'data' => Json::encode(Fields::nonNulls(array(
				'callback' => Params::body('callback'),
				'ip' => Params::body('ip'),
				'return' => Params::body('return'),
				'response' => $responseJson['url'],
				'responseType' => $responseType,
			))),

			'created' => Timestamp::now(),
			'auth_log_id' => Auth::authLogId(),
		));

		Response::set('status', $status);
		Response::set('response', $responseJson['url']);
		Response::set('responseType', $responseType);

	} else {

		$order = Db::get('orders', '*', array(
			'user_id' => Auth::userId(),
			'user_order_id'=> Params::url(1)
		));

		if (!is_null($order)) {
			$data = Json::decode($order['data']);
			Response::set('status', $order['status']);
			Response::set('response', $data['response']);
			Response::set('responseType', $data['responseType']);
		}
	}


} else if (Params::method() == 'GET') {

	$valid = true;
	if (!Db::has('orders', array(
		'user_id' => Auth::userId(),
		'user_order_id' => Params::url(1),
	))) {
		$valid = false;
		Errors::append(Fields::VALIATION_ERROR_CODE, 
			'The \'orderId\' does not exist.',
			'orderId'
		);
	}

	if ($valid) {

		$order = Db::get('orders', '*', array(
			'user_id' => Auth::userId(),
			'user_order_id'=> Params::url(1)
		));

		if (!is_null($order)) {
			
			$data = Json::decode($order['data']);
			Response::set('status', $order['status']);
			Response::set('amount', $order['amount']);
			Response::set('created', $order['created']);
			Response::set('closed', $order['closed']);

			Response::set('response', $data['response']);
			Response::set('responseType', $data['responseType']);
			Response::set('callback', $data['callback']);
			Response::set('ip', $data['ip']);
			Response::set('return', $data['return']);
		}	
	}

	/* currently, we only support outputing a single deposit */

}