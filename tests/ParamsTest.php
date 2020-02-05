

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
    private $_GET;
    private $_COOKIE;

    protected function setUp() : void
    {
        $this->params = new Params();
    }

    protected function tearDown() : void
    {
        $this->params = NULL;
    }

    public function testClientFunction()
    {
        $this->assertIsArray($this->params->client());
    }

    public function testUrlFunction()
    {
        $_SERVER['REQUEST_URI'] = 'www.google.com?anks=1';
        $this->assertIsArray($this->params->url());
    }

    public function testHeaderFunction()
    {
        $this->assertIsArray($this->params->headers());
    }

    public function testQueryFunction()
    {
        $_GET['params'] = 1;
        $this->assertEquals($_GET ,$this->params->query());
    }

    public function testCookiesFunction()
    {
        $this->assertEquals($_COOKIE, $this->params->cookies());
    }

    public function testMethodFunction()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals($_SERVER['REQUEST_METHOD'], $this->params->method());
    }

    public function testBodyFunction()
    {
        $this->assertIsArray($this->params->body());
    }

    public function testJsonFunction()
    {
        $this->assertTrue($this->params->isJson());
    }
}