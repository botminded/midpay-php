<?php
include_once('/var/www/html/midpay/src/lib/Currency.php');
use PHPUnit\Framework\TestCase;
use MidPay\Currency;

/**
 * 
 */
class JsonCurrency extends TestCase
{
    protected function setUp() : void
    {
        $this->currency = new Currency();
    }

    protected function tearDown() : void
    {
        $this->currency = NULL;
    }

    public function testScale()
    {
        $this->assertEquals(5, $this->currency->scale('5'));
        $this->assertEquals(5, $this->currency->scale());
    }

    public function testZero()
    {
        $this->assertEquals(0, $this->currency->zero());
    }

    public function testComp()
    {
        $this->assertEquals(0, $this->currency->comp(45,45.0));
        $this->assertEquals(1, $this->currency->comp(10,5));
        $this->assertEquals(1,$this->currency->comp(15.00001,15));
        $this->assertEquals(-1,$this->currency->comp(1.9998,1.9999));
    }

    public function testCheck()
    {
        $this->assertTrue($this->currency->check(5));
        $this->assertFalse($this->currency->check('s'));
        $this->assertTrue($this->currency->check(45, '==', 45.0));
        $this->assertTrue($this->currency->check(45, '!=', 41));
        $this->assertTrue($this->currency->check(45, '<=', 45));
    }

    public function testFormat()
    {
        $this->assertEquals(45.0000, $this->currency->format(45));
        $this->currency->scale(0);
        $this->assertEquals(45, $this->currency->format(45.1));
    }

    public function testSub()
    {
        $this->assertEquals(5, $this->currency->sub(45, 40));
        $this->currency->scale(0);
        $this->assertEquals(0, $this->currency->sub(45.59, 45));
    }

    public function testAdd()
    {
        $this->assertEquals(85, $this->currency->add(45, 40));
        $this->currency->scale(0);
        $this->assertEquals(90, $this->currency->add(45.1, 45));
    }
}
?>