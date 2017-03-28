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
MIICWwIBAAKBgQDBzNyCrl6r9ZP/+Mz/1llw5GV0mfD6FVMe04e5KOAvMgfcduin
uTm6j8TzZR6MZSRVUejsiUn4jFp46+qXmEdJEIc1i7C7RsK8pjt+uloCUE/OJmEw
kXdI0FbWgWPpnEqRpZneqhXTV89JYyZ6+pYwT1SkML9Kzkd3GMAcagVEGQIDAQAB
AoGAGMNKcdBB/Ir2jECIQTBsYUZtyEZbSjkrU8cHkpssehtUcdEnzTaXr8TrD4ae
LqZFzDkZIBAyyXV1ofnTEee9Q9nMYz3ZKEko1FPJlLLJMFKt9EK5y8irle8EQPYQ
VU4k5LCX/c/W4SgOwLe9N46rd+bTVkyMdC+uwn07sozE1RkCQQDlbem72vYyDk9G
uNyTV15Og86biY/zknqTESs9GMvbJt7e7Ks6kbov+tbHGAy4GS+jAhrfkuiEf+SB
hDgpnHmDAkEA2D6fM6rIfsBdlGaN9uUyl+Qe03oQrNTj7RwX595s+rqSBRTS1kYh
oCjMp/shpxiXMeEF7V4CK1z/XKeRkuiFMwJAdAza09j3+23dj8pmWGkzHMfzNB2r
IOuQ8N8YXfky9JF+3ogcPK4F3csl5OM3W8/xlqSz7y8iShNfBFxbBEFP/QJAYtbE
p7YA7EZ6v3DcpKQAKwLewCSD6Ktp/p+foaC9ySBry5zH/Z6SkgTz/jfAGwMXYHoM
3oDglvdr1OrWlAJplwJAODiD5SGbCXa4R8fZ3yGzyzWDH2/cHfJ5jT3A2Gm2JBpK
njjPwBI9qXb2OtlnXoUQCDB8E9AuC7L+pSiThQnyVQ==
-----END RSA PRIVATE KEY-----',
            'ali_public_key' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDBzNyCrl6r9ZP/+Mz/1llw5GV0
mfD6FVMe04e5KOAvMgfcduinuTm6j8TzZR6MZSRVUejsiUn4jFp46+qXmEdJEIc1
i7C7RsK8pjt+uloCUE/OJmEwkXdI0FbWgWPpnEqRpZneqhXTV89JYyZ6+pYwT1Sk
ML9Kzkd3GMAcagVEGQIDAQAB
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
        'test_gateway' => [
            'label' => 'Test',
            'order_prefix' => 'TEST',
            'methods' => [
                'test_pay' => [
                    'class' => 'Phwoolcon\Payment\Tests\Helper\TestPaymentMethod',
                    'label' => 'Test Pay',
                ],
                'invalid_method' => [
                    'class' => 'Phwoolcon\Payment\Tests\Helper\InvalidPaymentMethod',
                    'label' => 'Invalid Pay',
                ],
            ],
            'required_callback_parameters' => [
                'order_id',
                'amount',
                'status',
                'sign',
            ],
        ],
    ],
];
