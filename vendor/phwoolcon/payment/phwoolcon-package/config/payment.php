<?php

return [
    'gateways' => [
        'alipay' => [
            'label' => 'Alipay',
            'order_prefix' => 'ALIPAY',
            'methods' => [
                'mobile_web' => [
                    'class' => 'Phwoolcon\Payment\Method\Alipay\MobileWebPay',
                    'label' => 'Alipay Mobile Web Pay',
                ],
            ],
            'partner' => 'PARTNER_ID',
            'seller_id' => 'seller@phwoolcon.com',
            'charset' => 'utf-8',
            'sign_type' => 'RSA',
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----
YOUR_PRIVATE_KEY_HERE
-----END RSA PRIVATE KEY-----',
            'ali_public_key' => '-----BEGIN PUBLIC KEY-----
ALI_PUBLIC_KEY_HERE
-----END PUBLIC KEY-----',
            'return_url' => 'api/alipay/return',
            'notify_url' => 'api/alipay/callback',
            'required_callback_parameters' => [
                'trade_no',
                'out_trade_no',
                'trade_status',
                'total_fee',
                'notify_id',
                'sign_type',
                'sign',
            ],
        ],
    ],
];
