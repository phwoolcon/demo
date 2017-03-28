<?php
namespace Phwoolcon\Payment\Tests\Unit\Process;

use Phwoolcon\Payment\Exception\ResultException;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Process\Result;
use Phwoolcon\Payment\Tests\Helper\TestCase;

class ResultTest extends TestCase
{

    public function testNormalResult()
    {
        $result = Result::create([
            'order' => new Order,
        ]);
        $this->assertInstanceOf(Order::class, $result->getOrder());
    }

    public function testErrorResult()
    {
        $result = Result::create([
            'error' => $error = [
                'code' => 123,
                'message' => 'foo',
            ],
        ]);
        $this->assertEquals($error, $result->getError());
    }

    public function testInvalidResult()
    {
        $e = null;
        try {
            Result::create([
                'foo' => 'bar',
            ]);
        } catch (ResultException $e) {
        }
        $this->assertInstanceOf(ResultException::class, $e);
        $this->assertEquals(ResultException::INVALID_RESULT_FORMAT, $e->getCode());
    }
}
