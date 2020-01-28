<?php

namespace MidPay;

$groupId = Params::url(1);

if (Params::method() == 'PUT') {
	/* do stuff */

	if (!Auth::success() && Auth::type() == 'ADMIN') {
		Auth::unauthorized();
	}

	
}

