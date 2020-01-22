<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');


$source = array(
	'orderId' => 'HAHAH',
	'amount' => 12345
);


$where = Fields::from(array(
	'user_order_id' => 'orderId', 
	'amount' => 'amount',
	'user_id' => 'userId'
), $source);

var_dump($where->params);

var_dump($where->validate('users', Fields::DATA));

/* deposit: 

- orderId
- amount

*/

/* Hmm, should we not have a validate class? */


var_dump(Errors::errors());