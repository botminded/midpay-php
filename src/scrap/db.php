<?php

namespace MidPay;

include('../lib.php');

$startTime = microtime(true);

var_dump(Db::schema());

var_dump(Db::schema('orders'));

$endTime = microtime(true);

echo $endTime - $startTime;

/*
var_dump(Db::schema('users')['password']->maxLength);


// Validation examples

// Check if field is:
// 	- provided
// 	- non-empty
// 	- within character limits
// 	- already exists 

if (is_null(Params::body('userId'))) {

} else if (strlen('' . Params::body('userId')) < 1) {

} else if (strlen('' . Params::body('userId')) > Db::schema('users')['user_id']->maxLength) {

}

// Check if field is:
// 	- provided
// 	- non-empty
// 	- within character limits
// 	- already exists 

//Validate::insert('table', array())




//Params::body(array())
*/