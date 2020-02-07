<?php
include_once('/var/www/html/midpay/src/lib/Json.php');
use PHPUnit\Framework\TestCase;
use MidPay\Json;

/**
 * 
 */
class JsonTest extends TestCase
{
    protected function setUp() : void
    {
        $this->json = new Json();
    }

    protected function tearDown() : void
    {
        $this->json = NULL;
    }

    public function testAssoc() 
    {
        $this->assertIsArray($this->json->assoc());
        $this->assertIsArray($this->json->assoc('name'));
    }

    public function testEncode()
    {
        $arr1 = array('id' => 'one', 'first_name' => 'Ankit', 'last_name' => 'Gupta');
        $this->assertEquals(json_encode($arr1), $this->json->encode($arr1));
    }

    public function testDecode()
    {
        $arr1 = array('id' => 'one', 'first_name' => 'Ankit', 'last_name' => 'Gupta');
        $this->assertIsArray($this->json->decode(json_encode($arr1)));
    }

    public function testIsAssoc()
    {
        $arr1 = array('id' => 'one', 'first_name' => 'Ankit', 'last_name' => 'Gupta', chr(1) => true);
        //this is case not working fine because we have a mistake in assoc function.
        $this->assertTrue($this->json->isAssoc($arr1));
    }
}

?>