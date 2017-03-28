<?php
namespace Phwoolcon\Payment\Method\Alipay;

use Phwoolcon\Payment\MethodInterface;
use Phwoolcon\Payment\MethodTrait;
use Phwoolcon\Payment\Process\Result;

class MobileWebPay implements MethodInterface
{
    use MethodTrait, AbstractTrait {
        AbstractTrait::verifyCallbackSign insteadof MethodTrait;
    }

    protected $service = 'alipay.wap.create.direct.pay.by.user';
    protected $gatewayUrl = 'https://mapi.alipay.com/gateway.do';

    public function payRequest($data)
    {
        $order = $this->prepareOrder($data);
        $alipayRequest = $this->getRequestData($order);
        $alipayRequest['sign'] = $this->rsaSign($alipayRequest);
        $order->setOrderData('alipay_request', $alipayRequest)
            ->setPaymentGatewayUrl($this->createGatewayUrl($alipayRequest));
        $order->save();
        return Result::create([
            'order' => $order,
        ]);
    }
}
