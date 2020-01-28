<?php

namespace MidPay;

include('lib.php');
include('modules.php');


$query = file_get_contents('schema.sql');

foreach (explode(';', $query) as $q) {
	Db::query($q);	
}

$password = 'THIS_PASSWORD_MUST_BE_CHANGED_TO_A_STRONG_SECRET_BEFORE_PUSHING_TO_PRODUCTION';
$apiKey = 'OH_VERY_SECRET_API_KEY';

Db::insert('users', array(
	'user_id' => 'master',
	'password' => Crypto::hashPassword($password),
	'api_key' => $apiKey,
	'balance' => '0.00',
	'data' => '',
	'type' => 'MASTER'
));

