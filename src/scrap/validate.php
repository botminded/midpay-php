<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');


$source = array(
	'orderId' => 'HAHAH',
	'amount' => 12345
);

Fields::validate('users',
	$where = Fields::get($source, 
		$mappings = array(
			'user_order_id' => 'orderId', 
			'amount' => 'amount',
			'user_id' => 'userId'
		)),
	$mappings,
	Fields::WHERE
);

var_dump($where);


/* deposit: 

- orderId
- amount

*/

/* Hmm, should we not have a validate class? */


var_dump(Errors::errors());