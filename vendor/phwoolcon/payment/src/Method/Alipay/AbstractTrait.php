<?php
namespace Phwoolcon\Payment\Method\Alipay;

use Exception;
use Phwoolcon\Log;
use Phwoolcon\Payment\Exception\CallbackException;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Process\Result;
use Phwoolcon\Security;

/**
 * Class AbstractTrait
 * @package Phwoolcon\Payment\Method\Alipay
 *
 * @method Order getCallbackOrder(string $orderId)
 */
trait AbstractTrait
{

    public function callback($data)
    {
        try {
            $this->verifyCallbackParameters($data);
            $this->verifyCallbackSign(null, $data, fnGet($data, 'sign'));
            $order = $this->getCallbackOrder(fnGet($data, 'out_trade_no'));
            $status = fnGet($data, 'trade_status');

            // Processing
            if ($status == 'WAIT_BUYER_PAY') {
                if ($order->canConfirm()) {
                    $order->confirm(__('Alipay callback status %status%', ['status' => $status]));
                    $order->save();
                }
                return Result::create([
                    'order' => $order,
                    'response' => 'success',
                ]);
            }

            // Success
            if ($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS') {
                if ($order->canComplete()) {
                    $this->verifyCallbackAmount($order, fnGet($data, 'total_fee'));
                    $order->complete('Test Callback');
                    $order->save();
                }
                return Result::create([
                    'order' => $order,
                    'response' => 'success',
                ]);
            }

            // Failure
            if ($order->canFail()) {
                $order->fail(__('Alipay callback status %status%', ['status' => $status]));
                $order->save();
            }
            return Result::create([
                'order' => $order,
                'response' => 'success',
            ]);
        } catch (CallbackException $e) {
            return Result::create([
                'error' => $e,
                'response' => 'FAILED',
            ]);
        } // @codeCoverageIgnoreStart
        catch (Exception $e) {
            Log::exception($e);
            return Result::create([
                'error' => $e,
                'response' => 'FAILED',
            ]);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param $order
     * @param $callbackData
     * @codeCoverageIgnore
     */
    public function createCallbackSign($order, $callbackData)
    {
    }

    public function verifyCallbackSign($order, $callbackData, $sign, $errorCode = CallbackException::INVALID_SIGN)
    {
        if (!$this->verifyRsaSign($callbackData, $sign)) {
            $this->throwCallbackException(__('Invalid callback sign [%sign%]', [
                'sign' => $sign,
            ]), $errorCode);
        }
    }

    protected function createGatewayUrl($data)
    {
        return $this->gatewayUrl . '?' . http_build_query($data);
    }

    protected function getRequestData(Order $order)
    {
        return [
            'service' => $this->service,
            'partner' => $this->config['partner'],
            '_input_charset' => $this->config['charset'],
            'sign_type' => $this->config['sign_type'],
            'notify_url' => secureUrl($this->config['notify_url']),
            'return_url' => secureUrl($this->config['return_url']),

            'out_trade_no' => $order->getOrderId(),
            'subject' => $order->getProductName(),
            'total_fee' => $order->getAmount(),
            'seller_id' => $this->config['seller_id'],
            'payment_type' => 1,
        ];
    }

    public function rsaSign(array $data)
    {
        unset($data['sign'], $data['sign_type']);
        $string = Security::prepareSignatureData($data);
        $privateKey = openssl_pkey_get_private($this->config['private_key']);
        openssl_sign($string, $sign, $privateKey);
        openssl_free_key($privateKey);
        return base64_encode($sign);
    }

    public function verifyRsaSign(array $data, $sign)
    {
        unset($data['sign'], $data['sign_type']);
        $string = Security::prepareSignatureData($data);
        $publicKey = openssl_pkey_get_public($this->config['ali_public_key']);
        $result = openssl_verify($string, base64_decode($sign), $publicKey);
        openssl_free_key($publicKey);
        return $result == 1;
    }
}
