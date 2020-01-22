<?php

namespace MidPay;

/* This is the starting point for routing the endpoints. */

include('../lib.php');
include('../modules.php');

Response::enableCors();
Response::registerOutputOnExit(true);

Db::beginTransaction();

$pages = array(
	'deposits', 
	'gateways',
	'groups',
	'users'
);

foreach ($pages as $c) 
	if (Params::url(0) === $c && file_exists($p = $c.'.php')) {
		include($p);
		break;
	}

Db::commit();