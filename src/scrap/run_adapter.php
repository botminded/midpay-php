<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');

$start = microtime(true);
$OUT = Adapters::run(
	'sample.php',
	array(
		'A' => 'A',
		'B' => 'B'
	),
	array('C')
);

var_dump($OUT);