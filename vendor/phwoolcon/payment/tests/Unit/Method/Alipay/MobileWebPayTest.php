<?php
namespace Phwoolcon\Payment\Tests\Unit\Method\Alipay;

use Phwoolcon\Payment\Exception\CallbackException;
use Phwoolcon\Payment\Method\Alipay\MobileWebPay;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Process\Payload;
use Phwoolcon\Payment\Process\Result;
use Phwoolcon\Payment\Processor;
use Phwoolcon\Payment\Tests\Helper\TestCase;

class MobileWebPayTest extends TestCase
{

    public function testPayRequest()
    {
        $tradeId = md5(microtime());
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => $tradeId,
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());
        $this->assertEquals($amount, $order->getAmount());
        $this->assertEquals($amount, $order->getCashToPay());
        $this->assertEquals($order->getOrderId(), $order->getOrderData('alipay_request.out_trade_no'));
        $this->assertEquals($order->getProductName(), $order->getOrderData('alipay_request.subject'));
        $this->assertEquals($order->getAmount(), $order->getOrderData('alipay_request.total_fee'));
        $this->assertNotEmpty($order->getPaymentGatewayUrl());
        $this->assertNotEmpty($order->getOrderData('alipay_request.notify_url'));
        $this->assertNotEmpty($order->getOrderData('alipay_request.return_url'));
    }

    public function testCallbackProcessing()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-ALIPAY-PROCESSING',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());

        $callbackData = [
            'discount' => '0.00',
            'payment_type' => '1',
            'subject' => $order->getProductName(),
            'trade_no' => date('YmdHis') . '123456789012',
            'buyer_email' => 'buyer@example.com',
            'gmt_create' => $time = date('Y-m-d H:i:s', $order->getCreatedAt()),
            'notify_type' => 'trade_status_sync',
            'quantity' => '1',
            'out_trade_no' => $order->getOrderId(),
            'seller_id' => $order->getOrderData('alipay_request.partner'),
            'notify_time' => date('Y-m-d H:i:s'),
            'body' => $order->getProductName(),
            'trade_status' => 'WAIT_BUYER_PAY',
            'is_total_fee_adjust' => 'N',
            'total_fee' => $amount,
            'gmt_payment' => $time,
            'seller_email' => $order->getOrderData('alipay_request.seller_id'),
            'gmt_close' => $time,
            'price' => $amount,
            'buyer_id' => 'BUYER_ID',
            'notify_id' => 'f2b0c402904bc47d2913fd9ad085731jh2',
            'use_coupon' => 'N',
            'sign_type' => 'RSA',
        ];

        /* @var MobileWebPay $alipay */
        $alipay = $this->processor->getPaymentMethod($gateway = 'alipay', $method = 'mobile_web');
        $callbackData['sign'] = $alipay->rsaSign($callbackData);
        $callback = Processor::run(Payload::create([
            'gateway' => $gateway,
            'method' => $method,
            'action' => 'callback',
            'data' => $callbackData,
        ]));
        $this->assertInstanceOf(Payload::class, $callback);
        $this->assertInstanceOf(Result::class, $result = $callback->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEquals($order::STATUS_PROCESSING, $order->getStatus());
        $this->assertEquals($amount, $order->getAmount());
        $this->assertEquals('success', $result->getResponse());
    }

    public function testCallbackSuccess()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-ALIPAY-COMPLETE',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());

        $callbackData = [
            'discount' => '0.00',
            'payment_type' => '1',
            'subject' => $order->getProductName(),
            'trade_no' => date('YmdHis') . '123456789012',
            'buyer_email' => 'buyer@example.com',
            'gmt_create' => $time = date('Y-m-d H:i:s', $order->getCreatedAt()),
            'notify_type' => 'trade_status_sync',
            'quantity' => '1',
            'out_trade_no' => $order->getOrderId(),
            'seller_id' => $order->getOrderData('alipay_request.partner'),
            'notify_time' => date('Y-m-d H:i:s'),
            'body' => $order->getProductName(),
            'trade_status' => 'TRADE_FINISHED',
            'is_total_fee_adjust' => 'N',
            'total_fee' => $amount,
            'gmt_payment' => $time,
            'seller_email' => $order->getOrderData('alipay_request.seller_id'),
            'gmt_close' => $time,
            'price' => $amount,
            'buyer_id' => 'BUYER_ID',
            'notify_id' => 'f2b0c402904bc47d2913fd9ad085731jh2',
            'use_coupon' => 'N',
            'sign_type' => 'RSA',
        ];

        /* @var MobileWebPay $alipay */
        $alipay = $this->processor->getPaymentMethod($gateway = 'alipay', $method = 'mobile_web');
        $callbackData['sign'] = $alipay->rsaSign($callbackData);
        $callback = Processor::run(Payload::create([
            'gateway' => $gateway,
            'method' => $method,
            'action' => 'callback',
            'data' => $callbackData,
        ]));
        $this->assertInstanceOf(Payload::class, $callback);
        $this->assertInstanceOf(Result::class, $result = $callback->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEquals($order::STATUS_COMPLETE, $order->getStatus());
        $this->assertEquals($amount, $order->getAmount());
        $this->assertEquals($amount, $order->getCashPaid());
        $this->assertEquals(0, $order->getCashToPay());
        $this->assertEquals('success', $result->getResponse());
    }

    public function testCallbackClosed()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-ALIPAY-CLOSED',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());

        $callbackData = [
            'discount' => '0.00',
            'payment_type' => '1',
            'subject' => $order->getProductName(),
            'trade_no' => date('YmdHis') . '123456789012',
            'buyer_email' => 'buyer@example.com',
            'gmt_create' => $time = date('Y-m-d H:i:s', $order->getCreatedAt()),
            'notify_type' => 'trade_status_sync',
            'quantity' => '1',
            'out_trade_no' => $order->getOrderId(),
            'seller_id' => $order->getOrderData('alipay_request.partner'),
            'notify_time' => date('Y-m-d H:i:s'),
            'body' => $order->getProductName(),
            'trade_status' => 'TRADE_CLOSED',
            'is_total_fee_adjust' => 'N',
            'total_fee' => $amount,
            'gmt_payment' => $time,
            'seller_email' => $order->getOrderData('alipay_request.seller_id'),
            'gmt_close' => $time,
            'price' => $amount,
            'buyer_id' => 'BUYER_ID',
            'notify_id' => 'f2b0c402904bc47d2913fd9ad085731jh2',
            'use_coupon' => 'N',
            'sign_type' => 'RSA',
        ];

        /* @var MobileWebPay $alipay */
        $alipay = $this->processor->getPaymentMethod($gateway = 'alipay', $method = 'mobile_web');
        $callbackData['sign'] = $alipay->rsaSign($callbackData);
        $callback = Processor::run(Payload::create([
            'gateway' => $gateway,
            'method' => $method,
            'action' => 'callback',
            'data' => $callbackData,
        ]));
        $this->assertInstanceOf(Payload::class, $callback);
        $this->assertInstanceOf(Result::class, $result = $callback->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEquals($order::STATUS_FAILING, $order->getStatus());
        $this->assertEquals($amount, $order->getAmount());
        $this->assertEquals('success', $result->getResponse());
    }

    public function testVerifyCallbackParameters()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-ALIPAY-BAD-CALLBACK-PARAMS',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());

        $callback = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'callback',
            'data' => [
                'discount' => '0.00',
                'payment_type' => '1',
                'subject' => $order->getProductName(),
                'trade_no' => date('YmdHis') . '123456789012',
                'buyer_email' => 'buyer@example.com',
                'gmt_create' => $time = date('Y-m-d H:i:s', $order->getCreatedAt()),
                'notify_type' => 'trade_status_sync',
                'quantity' => '1',
                'out_trade_no' => $order->getOrderId(),
                'seller_id' => $order->getOrderData('alipay_request.partner'),
                'notify_time' => date('Y-m-d H:i:s'),
                'body' => $order->getProductName(),
                'trade_status' => 'TRADE_CLOSED',
                'is_total_fee_adjust' => 'N',
                'gmt_payment' => $time,
                'seller_email' => $order->getOrderData('alipay_request.seller_id'),
                'gmt_close' => $time,
                'price' => $amount,
                'buyer_id' => 'BUYER_ID',
                'notify_id' => 'f2b0c402904bc47d2913fd9ad085731jh2',
                'use_coupon' => 'N',
                'sign_type' => 'RSA',
                'sign' => 'bad-sign',
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $callback);
        $this->assertInstanceOf(Result::class, $result = $callback->getResult());
        $this->assertInstanceOf(CallbackException::class, $error = $result->getError());
        $this->assertEquals(CallbackException::BAD_PARAMETERS, $error->getCode());
        $this->assertContains('total_fee', $error->getMessage());
    }

    public function testVerifyCallbackSign()
    {
        $payload = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'payRequest',
            'data' => [
                'trade_id' => 'TEST-ALIPAY-BAD-CALLBACK-PARAMS',
                'product_name' => 'Test product',
                'client_id' => 'test_client',
                'user_identifier' => 'Test User',
                'amount' => $amount = 1,
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertInstanceOf(Result::class, $result = $payload->getResult());
        $this->assertInstanceOf(Order::class, $order = $result->getOrder());
        $this->assertEmpty($order->getStringMessages());
        $this->assertEquals($order::STATUS_PENDING, $order->getStatus());

        $callback = Processor::run(Payload::create([
            'gateway' => 'alipay',
            'method' => 'mobile_web',
            'action' => 'callback',
            'data' => [
                'discount' => '0.00',
                'payment_type' => '1',
                'subject' => $order->getProductName(),
                'trade_no' => date('YmdHis') . '123456789012',
                'buyer_email' => 'buyer@example.com',
                'gmt_create' => $time = date('Y-m-d H:i:s', $order->getCreatedAt()),
                'notify_type' => 'trade_status_sync',
                'quantity' => '1',
                'out_trade_no' => $order->getOrderId(),
                'seller_id' => $order->getOrderData('alipay_request.partner'),
                'notify_time' => date('Y-m-d H:i:s'),
                'body' => $order->getProductName(),
                'trade_status' => 'TRADE_CLOSED',
                'is_total_fee_adjust' => 'N',
                'total_fee' => $amount,
                'gmt_payment' => $time,
                'seller_email' => $order->getOrderData('alipay_request.seller_id'),
                'gmt_close' => $time,
                'price' => $amount,
                'buyer_id' => 'BUYER_ID',
                'notify_id' => 'f2b0c402904bc47d2913fd9ad085731jh2',
                'use_coupon' => 'N',
                'sign_type' => 'RSA',
                'sign' => 'bad-sign',
            ],
        ]));
        $this->assertInstanceOf(Payload::class, $callback);
        $this->assertInstanceOf(Result::class, $result = $callback->getResult());
        $this->assertInstanceOf(CallbackException::class, $error = $result->getError());
        $this->assertEquals(CallbackException::INVALID_SIGN, $error->getCode());
    }
}
