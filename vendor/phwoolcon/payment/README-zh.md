# Phwoolcon Payment
[![Build Status](https://travis-ci.org/phwoolcon/payment.svg?branch=master)](https://travis-ci.org/phwoolcon/payment)
[![Code Coverage](https://codecov.io/gh/phwoolcon/payment/branch/master/graph/badge.svg)](https://codecov.io/gh/phwoolcon/payment)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

Phwoolcon 支付模块

本模块已默认集成支付宝。

## 1. 安装
用 composer 把本模块加入到你的项目中：

```
composer require "phwoolcon/payment":"dev-master"
```

## 2. 配置
请创建文件 `app/config/production/payment.php` 来填写支付宝收款帐号：
```php
<?php
return [
    'gateways' => [
        'alipay' => [
            'partner' => 'YOUR_PARTNER_ID_APPLIED_FROM_ALIPAY',
            'seller_id' => 'YOUR_SELLER_EMAIL_APPLIED_FROM_ALIPAY',
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----
YOUR_PRIVATE_KEY_PROVIDED_TO_ALIPAY
-----END RSA PRIVATE KEY-----',
            'ali_public_key' => '-----BEGIN PUBLIC KEY-----
THE_PUBLIC_KEY_APPLIED_FROM_ALIPAY
-----END PUBLIC KEY-----',
        ],
    ],
];

```

## 3. 使用

Phwoolcon Payment 抽象了一个支付处理器，所有支付动作都将通过 `Processor::run()`  
来调用。

### 3.1. 发送支付宝支付请求（手机 Web 支付）
```php
<?php
use Phalcon\Di;
use Phwoolcon\Payment\Processor;

$di = Di::getDefault();
Processor::register($di);

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
        'amount' => 1,
    ],
]));
echo get_class($payload);       // 输出 Phwoolcon\Payment\Process\Payload

$result = $payload->getResult();
echo get_class($result);        // 输出 Phwoolcon\Payment\Process\Result

$order = $result->getOrder();
echo get_class($order);         // 输出 Phwoolcon\Payment\Model\Order

echo $order->getStatus();       // 输出 pending

$redirectUrl = $order->getPaymentGatewayUrl();
echo $redirectUrl;              // 输出类似这样的 url：
                                // https://mapi.alipay.com/gateway.do?service=alipay.wap.create.direct.pay.by.user&partner=...
                                // 你可以一个用 302 响应把用户浏览器重定向
                                // 到这个 url 以完成支付

$returnUrl = $order->getOrderData('alipay_request.return_url');
echo $returnUrl;                // 输出类似这样的 url：
                                // http://yoursite.com/api/alipay/return
                                // 支付成功或失败后，支付宝会把用户跳转到
                                // 这个 url

$notifyUrl = $order->getOrderData('alipay_request.notify_url');
echo $notifyUrl;                // 输出类似这样的 url：
                                // http://yoursite.com/api/alipay/callback
                                // 支付成功或失败后，支付宝服务器会对这个
                                // url 发起 post 回调
```

### 3.2. 处理支付宝回调
```php
<?php
use Phalcon\Di;
use Phwoolcon\Payment\Processor;

$di = Di::getDefault();
Processor::register($di);
$payload = Processor::run(Payload::create([
    'gateway' => 'alipay',
    'method' => 'mobile_web',
    'action' => 'callback',
    'data' => $_POST,
]));
$result = $payload->getResult();
echo $result->getResponse();    // 输出 success
```

## 4. 如何创建自定义支付方式
所有支付方式都是在配置文件 `payment.php` 里定义的，如果有需要，你可以添加  
任何支付网关/支付方式。

### 4.1. 创建支付网关/支付方式配置结构
编辑 `app/config/payment.php`:
```php
<?php
return [
    'gateways' => [

        .
        .
        .

        'your_gateway' => [
            'label' => '支付网关名称',
            'order_prefix' => 'SOME_PREFIX',
            'methods' => [
                'payment_method_1' => [
                    'class' => 'Fully\Qualified\Class\Name',
                    'label' => '支付方式名称',
                ],
                'payment_method_2' => [
                    'class' => 'Fully\Qualified\Class\Name',
                    'label' => '支付方式名称',
                ],
            ],
            'required_callback_parameters' => [
                'order_id',
                'amount',
                'status',
                'sign',
            ],
            'any_options' => 'value',
            'another_option' => 'value',
        ],
    ],
];
```

### 4.2. 填写真实帐号资料
请**不要**在上面的配置文件里面填写真实帐号资料，假如你把这个文件添加到版本  
控制系统（例如 git，svn），你的支付网关帐号可能会被泄露。

正确的做法是，把真实帐号填写到 `app/config/production/payment.php` 文件中，  
并且在版本控制系统里面忽略这个文件。

### 4.3. 创建支付方式类
支付方式类**必须**实现接口 `Phwoolcon\Payment\MethodInterface`

`Phwoolcon\Payment\MethodTrait` 已经抽象了一些通用功能，你可以把它 use 进  
你的支付方式类里面。

可以参考 `Phwoolcon\Payment\Tests\Helper\TestPaymentMethod`

每个支付方式至少**应该**实现两个动作：  
`payRequest` 和 `callback`

你可以给你的支付方式添加任何动作，通过设置 `Processor::run()` Payload 
里面的 `action` 参数来调用。

任何动作都**应该**返回一个 `Phwoolcon\Payment\Process\Result` 对象，该对象  
要么包含一个订单 `Order`，要么包含一个错误信息。
