<?php
include_once('/var/www/html/midpay/src/lib/Timestamp.php');
use PHPUnit\Framework\TestCase;
use MidPay\Timestamp;

/**
 * 
 */
class UtilsTest extends TestCase
{
	protected function setUp() : void
    {
        $this->timestamp = new Timestamp();
    }

    protected function tearDown() : void
    {
        $this->timestamp = NULL;
    }
    // this case is not giving proper result as functions defination(comments)
    public function testDefaultTimezone()
    {
    	$this->assertEquals('Asia/Calcutta', $this->timestamp->defaultTimezone('Asia/Calcutta'));
    }

    public function testNow()
    {
    	$this->assertEquals(time(), $this->timestamp->now());
    }

    public function testFloor()
    {
    	$this->assertEquals(mktime(0,0,0,2,6,2020),$this->timestamp->floor('day'));
    	$this->assertEquals(mktime(0,0,0,2,1,2020),$this->timestamp->floor('month'));
    	$this->assertEquals(mktime(0,0,0,1,1,2020),$this->timestamp->floor('year'));

    	// we need to check below functions as these are not giving results as expected

    	$this->assertEquals(mktime(0,4,18,1,1,2020),$this->timestamp->floor('minute'));
    	$this->assertEquals(mktime(0,0,18,1,1,2020),$this->timestamp->floor('hour'));
    }


    // functions related to day giving errors while checking them
    public function testDay()
    {
    	$this->assertEquals(mktime(0,0,0,2,6,2020),$this->timestamp->day());
    }

    public function testMonday()
    {
    	$this->assertEquals(mktime(0,0,0,2,3,2020),$this->timestamp->monday());
    }

    public function testSunday()
    {
    	$this->assertEquals(mktime(0,0,0,2,2,2020),$this->timestamp->sunday());
    }

    public function testHour()
    {
    	$this->assertEquals(mktime(0,0,18,1,1,2020),$this->timestamp->hour());
    }

    public function testMinute()
    {
    	$this->assertEquals(mktime(0,4,18,1,1,2020),$this->timestamp->minute());
    }

    public function testYear()
    {
    	$this->assertEquals(mktime(0,0,0,1,1,2020),$this->timestamp->year());
    }
}
?>