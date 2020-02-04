<?php

namespace MidPay;

$order = Db::get('orders', '*', array(
	'order_id'=> Params::url(-1)
));

$data = Json::decode($order['data']);
//var_dump(Params::url());
//var_dump($order);
//var_dump($data);
if (!is_null($order)) {

	// Just use the status as it is for FakePay
	$status = Params::body('status');

	$now = Timestamp::now();
	$body = array(
		'amount' => $order['amount'], 
		'closed' => $now,
		'orderId' => $order['user_order_id'],
		'status' => $status,
	);
	
	ksort($body);
	$sign = '';
	foreach ($parameter as $key => $value) {
		$sign .= $key . '=' . $value . '&';
	}
	$sign .= Auth::apiKey();
	$sign = hash('sha256', $sign);
	$body['sign'] = $sign;

	Callbacks::insert($data['callback'], array(
		'body' => $body
	));

	Db::update('orders', array(
		'status' => $status,
		'closed' => $now
	), array(
		'order_id' => Params::url(-1)
	));

	$success = $status == 'SUCCESS';
	Db::update('channel_attempts', array(
		'closed' => $now,
		'success' => $success
	), array(
		'attempt_id' => $order['attempt_id']
	));

}