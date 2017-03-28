<?php
namespace Phwoolcon\Payment;

use Phwoolcon\Log;
use Phwoolcon\Payment\Exception\CallbackException;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payment\Process\Payload;
use Phwoolcon\Payment\Process\Result;

trait MethodTrait
{
    protected $config;
    protected $gateway;
    protected $method;

    public function __construct(array $config = null)
    {
        $config and $this->config = $config;
    }

    /**
     * @param Order $order
     * @param array $callbackData
     * @return string
     */
    abstract public function createCallbackSign($order, $callbackData);

    public function getConfig($key = null, $default = null)
    {
        return $key === null ? $this->config : fnGet($this->config, $key, $default);
    }

    /**
     * @param string $orderId
     * @return Order
     */
    public function getCallbackOrder($orderId)
    {
        if (!$order = Order::getByPrefixedOrderId($orderId)) {
            $this->throwCallbackException(__('Unable to find order [%orderId%]', [
                'orderId' => $orderId,
            ]), CallbackException::ORDER_NOT_FOUND);
        }
        return $order;
    }

    protected function prepareOrder(array $data)
    {
        isset($data['order_prefix']) or $data['order_prefix'] = fnGet($this->config, 'order_prefix');
        $data['payment_gateway'] = $this->gateway;
        $data['payment_method'] = $this->method;
        $order = Order::prepare($data);
        return $order;
    }

    /**
     * @param Payload $payload
     * @return Result
     */
    public function process($payload)
    {
        $action = $payload->getData('action', 'payRequest');
        $this->gateway = $payload->getGateway();
        $this->method = $payload->getMethod();
        return $this->{$action}($payload->getData('data'));
    }

    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->config = $key;
        } else {
            array_set($this->config, $key, $value);
        }
        return $this;
    }

    protected function throwCallbackException($message, $code)
    {
        $e = new CallbackException($message, $code);
        Log::exception($e);
        throw $e;
    }

    /**
     * @param Order $order
     * @param float $callbackAmount
     * @param int   $errorCode
     * @return bool
     */
    public function verifyCallbackAmount($order, $callbackAmount, $errorCode = CallbackException::INVALID_AMOUNT)
    {
        $amount = 1 * $order->getAmount();
        if (round($amount - $callbackAmount, 2) != 0) {
            $this->throwCallbackException(__('Invalid callback amount [%callbackAmount%], should be [%amount%]', [
                'amount' => $amount,
                'callbackAmount' => $callbackAmount,
            ]), $errorCode);
        }
    }

    public function verifyCallbackParameters($data, $errorCode = CallbackException::BAD_PARAMETERS)
    {
        $lackedParameters = [];
        foreach (fnGet($this->config, 'required_callback_parameters', []) as $parameter) {
            isset($data[$parameter]) or $lackedParameters[] = $parameter;
        }
        $lackedParameters and $this->throwCallbackException(__('Missing parameters: [%parameters%]', [
            'parameters' => implode('], [', $lackedParameters),
        ]), $errorCode);
    }

    /**
     * @param Order  $order
     * @param array  $callbackData
     * @param string $sign
     * @param int    $errorCode
     * @return bool
     */
    public function verifyCallbackSign($order, $callbackData, $sign, $errorCode = CallbackException::INVALID_SIGN)
    {
        $localSign = $this->createCallbackSign($order, $callbackData);
        if ($localSign != $sign) {
            $this->throwCallbackException(__('Invalid callback sign [%sign%], should be [%localSign%]', [
                'sign' => $sign,
                'localSign' => $localSign,
            ]), $errorCode);
        }
    }
}
