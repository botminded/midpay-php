

<?php

include_once('/var/www/html/midpay/src/lib/Params.php');
include_once('/var/www/html/midpay/src/lib/Cases.php');
include_once('/var/www/html/midpay/src/lib/Utils.php');
include_once('/var/www/html/midpay/src/lib/Json.php');
use PHPUnit\Framework\TestCase;
use MidPay\Params;

class ParamsTest extends TestCase
{
    private $_SERVER;
    public function testClientFunction()
    {
        $this->assertIsArray( Params::client() );
    }

    public function testUrlFunction()
    {
        $_SERVER['REQUEST_URI'] = 'www.google.com';
        $this->assertIsArray(Params::url());
    }

    public function testHeaderFunction()
    {
       $this->assertIsArray(Params::headers());
    }

    public function testQueryFunction()
    {
       $this->assertIsArray(Params::query());
    }

    public function testCookiesFunction()
    {
        $this->assertIsArray(Params::cookies());
    }

    public function testMethodFunction()
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $this->assertIsString(Params::method());
    }

    public function testJsonFunction()
    {
        $this->assertTrue(Params::isJson());
    }
}