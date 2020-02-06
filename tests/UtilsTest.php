<?php
include_once('/var/www/html/midpay/src/lib/Utils.php');
use PHPUnit\Framework\TestCase;
use MidPay\Utils;

/**
 * 
 */
class UtilsTest extends TestCase
{
	protected function setUp() : void
    {
        $this->utils = new Utils();
    }

    protected function tearDown() : void
    {
        $this->utils = NULL;
    }

    public function testAssocRows()
    {
    	$rows = array();
    	$rows[] = array(0 => 'integer', 'one' => 'char', 'xyz' => 'two', 456 => 'fdf');
    	$this->assertEquals(array(array('one' => 'char', 'xyz' => 'two')), $this->utils->assocRows($rows));
    }

    public function testget()
    {
    	$data = array(0 => 'integer', 'one' => 'char', 'xyz' => 'two', 456 => 'fdf');
    	$this->assertEquals($data['xyz'], $this->utils->get($data, 'xyz'));
    	$this->assertEquals($data[0], $this->utils->get($data, 0));
    }
    // Below case are not working if we check for index(key) of type char 
    public function testat()
    {
    	$data = array(0 => 'integer', 1 => 'char', 2 => 'two', 3 => 'fdf');
    	$this->assertEquals('fdf', $this->utils->at($data, 3));
    	$this->assertEquals('char', $this->utils->at($data, 1));
    }

    public function testlast()
    {
    	$data = array(0 => 'integer', 1 => 'char', 2 => 'two', 3 => 'fdf');
    	$this->assertEquals('fdf', $this->utils->last($data));
    }
}

?>