<?php
namespace Phwoolcon\Payment\Tests\Integration;

use Phwoolcon\Db;
use Phwoolcon\Payment\Exception\OrderException;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Model\OrderData;
use Phwoolcon\Payment\Tests\Helper\TestCase;
use Phwoolcon\Payment\Tests\Helper\TestOrderModel;
use Phwoolcon\Payment\Tests\Helper\TestOrderDataModel;

class OrderModelTest extends TestCase
{

    /**
     * @return TestOrderModel
     */
    protected function getOrderModelInstance()
    {
        return $this->di->get(Order::class);
    }

    /**
     * @return TestOrderModel
     */
    protected function getTestOrder()
    {
        $order = $this->getOrderModelInstance();
        if ($tmp = $order::getByTradeId($tradeId = 'TEST-TRADE-ID', 'test_client')) {
            $order = $tmp;
        } else {
            $order = Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'trade_id' => $tradeId,
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
            $this->assertTrue($order->save(), $order->getStringMessages());
        }
        return $order;
    }

    public function setUp()
    {
        parent::setUp();
        Db::clearMetadata();
        $this->di->set(Order::class, TestOrderModel::class);
        $this->di->set(OrderData::class, TestOrderDataModel::class);
        $this->getOrderModelInstance()->delete();
    }

    public function testCreateOrder()
    {
        $tradeId = md5(microtime());

        // Fail if trade_id not set
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_BAD_PARAMETERS, $e->getCode());

        // Fail if client_id not set
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'trade_id' => $tradeId,
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_BAD_PARAMETERS, $e->getCode());

        // Fail if product_name not set
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'trade_id' => $tradeId,
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_BAD_PARAMETERS, $e->getCode());

        // Fail if amount <= 0
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '0.0',
                'trade_id' => $tradeId,
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_BAD_PARAMETERS, $e->getCode());

        // Fail if cash_to_pay < 0
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'cash_to_pay' => '-1',
                'trade_id' => $tradeId,
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_BAD_PARAMETERS, $e->getCode());

        // Success
        $order = Order::prepare([
            'order_prefix' => 'TEST',
            'amount' => '1',
            'trade_id' => $tradeId,
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($tradeId, $order->getTradeId());
        $this->assertEquals($order->getId(), $order->getOrderData()->getId());
    }

    public function testSetAndGetOrderData()
    {
        $order = $this->getTestOrder();

        // Test set string data
        $key = 'foo';
        $value = 'bar';
        $order->setOrderData($key, $value);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order = $this->getTestOrder();
        $this->assertEquals($value, $order->getOrderData($key));

        // Test set null data (remove data)
        $order->setOrderData($key, null);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order = $this->getTestOrder();
        $this->assertNull($order->getOrderData($key));
    }

    public function testPrepareExistingOrder()
    {
        $order = $this->getTestOrder();

        // Success if crucial attributes are not changed and status is pending
        $order = Order::prepare([
            'order_prefix' => 'MUTATED',
            'amount' => '1',
            'trade_id' => $order->getTradeId(),
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());

        // Fail if crucial attributes are changed
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '2',
                'trade_id' => $order->getTradeId(),
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_KEY_PARAMETERS_CHANGED, $e->getCode());

        $order->complete('Mark Order Complete');
        $this->assertTrue($order->save(), $order->getStringMessages());

        // Fail if status is not pending
        $e = null;
        try {
            Order::prepare([
                'order_prefix' => 'TEST',
                'amount' => '1',
                'trade_id' => $order->getTradeId(),
                'product_name' => 'Test product',
                'user_identifier' => 'Test User',
                'client_id' => 'test_client',
                'payment_gateway' => 'alipay',
                'payment_method' => 'mobile_web',
                'currency' => 'CNY',
                'amount_in_currency' => '1',
            ]);
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_PROCESSING, $e->getCode());
    }

    public function testConfirmOrder()
    {
        $tradeId = md5(microtime());

        $order = Order::prepare([
            'order_prefix' => 'PROCESSING',
            'amount' => '1',
            'trade_id' => $tradeId,
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order->confirm();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_PROCESSING, $order->getStatus());
        $e = null;
        try {
            $order->confirm();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_PROCESSING, $e->getCode());
    }

    public function testCompleteOrder()
    {
        $tradeId = md5(microtime());

        $order = Order::prepare([
            'order_prefix' => 'COMPLETE',
            'amount' => '1',
            'trade_id' => $tradeId,
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order->complete();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_COMPLETE, $order->getStatus());
        $e = null;
        try {
            $order->complete();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_COMPLETED, $e->getCode());
    }

    public function testCancelOrder()
    {
        $tradeId = md5(microtime());

        $order = Order::prepare([
            'order_prefix' => 'CANCELED',
            'amount' => '1',
            'trade_id' => $tradeId,
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order->cancel();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_CANCELING, $order->getStatus());
        $e = null;
        try {
            $order->cancel();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_CANNOT_BE_CANCELED, $e->getCode());

        $order->confirmCancel();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_CANCELED, $order->getStatus());

        $e = null;
        try {
            $order->confirmCancel();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_CANNOT_BE_CANCELED, $e->getCode());
    }

    public function testFailOrder()
    {
        $tradeId = md5(microtime());

        $order = Order::prepare([
            'order_prefix' => 'FAILED',
            'amount' => '1',
            'trade_id' => $tradeId,
            'product_name' => 'Test product',
            'user_identifier' => 'Test User',
            'client_id' => 'test_client',
            'payment_gateway' => 'alipay',
            'payment_method' => 'mobile_web',
            'currency' => 'CNY',
            'amount_in_currency' => '1',
        ]);
        $this->assertTrue($order->save(), $order->getStringMessages());
        $order->fail();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_FAILING, $order->getStatus());
        $e = null;
        try {
            $order->fail();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_CANNOT_BE_FAILED, $e->getCode());

        $order->confirmFail();
        $this->assertTrue($order->save(), $order->getStringMessages());
        $this->assertEquals($order::STATUS_FAILED, $order->getStatus());

        $e = null;
        try {
            $order->confirmFail();
        } catch (OrderException $e) {
        }
        $this->assertInstanceOf(OrderException::class, $e);
        $this->assertEquals($e::ERROR_CODE_ORDER_CANNOT_BE_FAILED, $e->getCode());
    }
}
