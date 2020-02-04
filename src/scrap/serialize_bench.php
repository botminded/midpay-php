<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');

$mappings = array();

for ($i = 0; $i < 300; $i++) {
	$mappings[Crypto::randString(31)] = array(
		Crypto::randString(31), 
		Crypto::randString(31), 
		Crypto::randString(31)
	);
}

$start = microtime(1);

$serialized = serialize($mappings);
file_put_contents('serialized.txt', $serialized);

echo microtime(1) - $start;

$start = microtime(1);
$serialized = file_get_contents('serialized.txt');
$deserialized = unserialize($serialized);

echo microtime(1) - $start;
