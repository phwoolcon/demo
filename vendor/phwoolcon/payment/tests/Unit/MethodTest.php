<?php
namespace Phwoolcon\Payment\Tests\Unit;

use Exception;
use Phwoolcon\Config;
use Phwoolcon\Payment\Exception\CallbackException;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Tests\Helper\TestCase;
use Phwoolcon\Payment\Tests\Helper\TestPaymentMethod;

class MethodTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testSetAndGetConfig()
    {
        $method = new TestPaymentMethod();
        $config = Config::get('payment.gateways.test_gateway');
        $method->setConfig($config);
        $this->assertEquals($config, $method->getConfig());
        $foo = [
            'class' => 'Foo',
            'description' => 'Bar',
        ];
        $method->setConfig('methods.foo', $foo);
        $this->assertEquals($foo, $method->getConfig('methods.foo'));
    }

    public function testGetNonExistingCallbackOrder()
    {
        $method = new TestPaymentMethod(Config::get('payment.gateways.test_gateway'));
        $e = null;
        try {
            $method->getCallbackOrder('non-existing');
        } catch (Exception $e) {
        }
        $this->assertInstanceOf(CallbackException::class, $e);
        $this->assertEquals(CallbackException::ORDER_NOT_FOUND, $e->getCode());
    }

    public function testVerifyCallbackParameters()
    {
        $method = new TestPaymentMethod(Config::get('payment.gateways.test_gateway'));

        // Good parameters
        $data = [
            'order_id' => 'order-id',
            'amount' => 1,
            'status' => 'complete',
            'sign' => 'any-sign-would-work',
        ];
        $e = null;
        try {
            $method->verifyCallbackParameters($data);
        } catch (Exception $e) {
        }
        $this->assertNull($e);

        // Bad parameters
        $data = [
            'order_id' => 'order-id',
            'amount' => 1,
            'status' => 'complete',
        ];
        $e = null;
        try {
            $method->verifyCallbackParameters($data);
        } catch (Exception $e) {
        }
        $this->assertInstanceOf(CallbackException::class, $e);
        $this->assertEquals(CallbackException::BAD_PARAMETERS, $e->getCode());
        $this->assertContains('sign', $e->getMessage());
    }

    public function testVerifyCallbackAmount()
    {
        $method = new TestPaymentMethod(Config::get('payment.gateways.test_gateway'));
        $order = new Order;
        $order->setAmount($amount = 10);

        // Good amount
        $e = null;
        try {
            $method->verifyCallbackAmount($order, $amount);
        } catch (Exception $e) {
        }
        $this->assertNull($e);

        // Bad amount
        $amount = 1;
        $e = null;
        try {
            $method->verifyCallbackAmount($order, $amount);
        } catch (Exception $e) {
        }
        $this->assertInstanceOf(CallbackException::class, $e);
        $this->assertEquals(CallbackException::INVALID_AMOUNT, $e->getCode());
    }

    public function testVerifyCallbackSign()
    {
        $method = new TestPaymentMethod(Config::get('payment.gateways.test_gateway'));
        $order = new Order;
        $order->setAmount($amount = 10);

        // Good amount
        $data = [
            'order_id' => 'order-id',
            'amount' => 1,
            'status' => 'complete',
            'sign' => $sign = 'any-sign-would-work',
        ];
        $e = null;
        try {
            $method->verifyCallbackSign($order, $data, $sign);
        } catch (Exception $e) {
        }
        $this->assertNull($e);

        // Bad amount
        $sign = 'bad-sign';
        $e = null;
        try {
            $method->verifyCallbackSign($order, $data, $sign);
        } catch (Exception $e) {
        }
        $this->assertInstanceOf(CallbackException::class, $e);
        $this->assertEquals(CallbackException::INVALID_SIGN, $e->getCode());
    }
}
