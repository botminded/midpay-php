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
	Callbacks::insert($data['callback'], array(
		'body' => array(
			'orderId' => $order['user_order_id'],
			'amount' => $order['amount'], 
			'status' => $status,
			'closed' => $now
		)
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