<?php
include_once('/var/www/html/midpay/src/lib/Cases.php');
use PHPUnit\Framework\TestCase;
use MidPay\Cases;

/**
 * 
 */
class CasesTest extends TestCase
{
	protected function setUp() : void
    {
        $this->cases = new Cases();
    }

    protected function tearDown() : void
    {
        $this->cases = NULL;
    }

    public function testWords()
    {
    	$str = "AnkiT GupTA";
        $str1 = "anks";
    	$is_str = 111;
        $this->assertEquals(array(0 => 'anki', 1 => 't', 2 => 'gup', 3 =>'ta'), $this->cases->words($str));
        $this->assertEquals(array(0 => 'anks'), $this->cases->words($str1));
        $this->assertEquals($is_str, $this->cases->words($is_str));
    }

    public function testget()
    {
        $arr1 = array('ABC' => 123, 'xyz' => 'anks', 1 => '22' );
        $this->assertEquals(123, $this->cases->get($arr1,'abc'));
        $this->assertEquals('22', $this->cases->get($arr1, 1));
        $this->assertEquals(null, $this->cases->get($arr1,8));
    }
}
?>