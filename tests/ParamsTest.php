





<?php

include_once('/root/dev/rakesh/src/lib/Params.php');

use PHPUnit\Framework\TestCase;
use MidPay\Params;

class ParamsTest extends TestCase
{
    private $_SERVER;
    public function testClientFunction()
    {
        $this->assertIsArray( Params::client() );
    }

    public function atestUrlFunction()
    {
        echo(Params::url());
    }

    public function atestHeaderFunction()
    {
        print_r(Params::headers('SSH_CLIENT'));

    }
    public function testCookiesFunction()
    {
        $this->assertIsArray(Params::cookies('SSH_CLIENT'));
    }
}