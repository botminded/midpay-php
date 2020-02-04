<?php

namespace MidPay;

include('../lib.php');
include('../modules.php');


var_dump(Channels::coerceMethod('aliPay'));
var_dump(Channels::coerceMethod('ALI_Pay'));


var_dump(Channels::coerceType('deposits'));
var_dump(Channels::coerceType('withdrawals'));