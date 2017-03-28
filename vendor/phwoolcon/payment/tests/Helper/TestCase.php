<?php
namespace Phwoolcon\Payment\Tests\Helper;

use Phwoolcon\Payment\Processor;
use Phwoolcon\Tests\Helper\TestCase as PhwoolconTestCase;

abstract class TestCase extends PhwoolconTestCase
{
    /**
     * @var Processor
     */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        Processor::register($this->di);
        new TestOrderModel();
        new TestOrderDataModel();
        $this->processor = $this->di->getShared('payment');
    }
}
