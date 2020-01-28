<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');

file_put_contents('recv_callback.txt', Json::encode(array(
	'headers' => Params::headers(),
	'query' => Params::query(),
	'body' => Params::body()
)));