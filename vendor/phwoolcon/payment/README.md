# Phwoolcon Payment
[![Build Status](https://travis-ci.org/phwoolcon/payment.svg?branch=master)](https://travis-ci.org/phwoolcon/payment)
[![Code Coverage](https://codecov.io/gh/phwoolcon/payment/branch/master/graph/badge.svg)](https://codecov.io/gh/phwoolcon/payment)
[![License](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](https://opensource.org/licenses/Apache-2.0)

Payment module for Phwoolcon

Alipay is delivered by default with this module.

## 1. Installation
Add this library to your project by composer:

```
composer require "phwoolcon/payment":"dev-master"
```

## 2. Configuration
Please create a new config file `app/config/production/payment.php` to  
override the default settings with real Alipay profile:
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

## 3. Usage

Phwoolcon Payment implements a abstracted payment processor, all  
payment procedures are invoked by `Processor::run()`

### 3.1. Start Alipay Pay Request (Mobile Web Pay)
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
echo get_class($payload);       // prints Phwoolcon\Payment\Process\Payload

$result = $payload->getResult();
echo get_class($result);        // prints Phwoolcon\Payment\Process\Result

$order = $result->getOrder();
echo get_class($order);         // prints Phwoolcon\Payment\Model\Order

echo $order->getStatus();       // prints pending

$redirectUrl = $order->getPaymentGatewayUrl();
echo $redirectUrl;              // prints url like this:
                                // https://mapi.alipay.com/gateway.do?service=alipay.wap.create.direct.pay.by.user&partner=...
                                // You can send 302 response to make browser
                                // redirecting to this url to complete a pay request

$returnUrl = $order->getOrderData('alipay_request.return_url');
echo $returnUrl;                // prints url like this:
                                // http://yoursite.com/api/alipay/return
                                // Alipay will redirect the user back to this url
                                // once the payment is complete or closed

$notifyUrl = $order->getOrderData('alipay_request.notify_url');
echo $notifyUrl;                // prints url like this:
                                // http://yoursite.com/api/alipay/callback
                                // Alipay will post callback data to this url
                                // once the payment is complete or closed
```

### 3.2. Process Alipay Callback
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
echo $result->getResponse();    // prints success
```

## 4. How to Create Custom Payment Methods
Payment methods are defined in config file `payment.php`, you can add  
any payment gateways/methods if necessary.

### 4.1. Create Payment Gateway/Method Config Structure
Edit `app/config/payment.php`:
```php
<?php
return [
    'gateways' => [

        .
        .
        .

        'your_gateway' => [
            'label' => 'Payment Gateway Name',
            'order_prefix' => 'SOME_PREFIX',
            'methods' => [
                'payment_method_1' => [
                    'class' => 'Fully\Qualified\Class\Name',
                    'label' => 'Payment Method Name',
                ],
                'payment_method_2' => [
                    'class' => 'Fully\Qualified\Class\Name',
                    'label' => 'Payment Method Name',
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

### 4.2. Fill Real World Profile
Please **DO NOT** fill real world profile in previous file, if you add it  
to a version control software (such as git, svn), it may leak your  
payment gateway account to public.

Instead, please fill them in a file in `app/config/production/payment.php`  
and then add it to your VCS ignore list.

### 4.3. Create Payment Method Class
A payment method class MUST implement `Phwoolcon\Payment\MethodInterface`

Some common features are abstracted in `Phwoolcon\Payment\MethodTrait`,  
you can use it in your class.

Take a quick glance at `Phwoolcon\Payment\Tests\Helper\TestPaymentMethod`

A payment method SHOULD implements at least two actions:  
`payRequest` and `callback`

You can add any actions to your payment method, invoke them by pass  
the action name to the payload of `Processor::run()`

Any actions SHOULD return a `Phwoolcon\Payment\Process\Result`, which  
SHOULD contain either an `Order` or an error.

[中文](README-zh.md)
