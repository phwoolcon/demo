<?php
namespace Phwoolcon\Payment;

use Phalcon\Di;
use Phwoolcon\Config;
use Phwoolcon\Payment\Exception\GeneralException;
use Phwoolcon\Payment\Process\Payload;

class Processor
{
    protected $config;
    /**
     * @var Di
     */
    protected static $di;
    /**
     * @var static
     */
    protected static $instance;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getPaymentMethod($gateway, $method = null)
    {
        if (!$config = fnGet(static::$instance->config, 'gateways.' . $gateway)) {
            throw new GeneralException(__('Undefined payment gateway: [%gateway%]', [
                'gateway' => $gateway,
            ]), GeneralException::UNDEFINED_PAYMENT_GATEWAY);
        }
        $class = fnGet($config, 'methods.' . $method . '.class');
        $class or $class = fnGet($config, 'class');
        if (!$class || !class_exists($class)) {
            throw new GeneralException(__('Undefined payment method: [%gateway%.%method%]', [
                'gateway' => $gateway,
                'method' => $method,
            ]), GeneralException::UNDEFINED_PAYMENT_METHOD);
        }
        /* @var MethodTrait $paymentMethod */
        $paymentMethod = new $class($config);
        if (!$paymentMethod instanceof MethodInterface) {
            throw new GeneralException(__('Invalid payment method class: [%class%]', [
                'class' => $class,
            ]), GeneralException::INVALID_PAYMENT_METHOD);
        }
        return $paymentMethod;
    }

    public static function register(Di $di)
    {
        static::$di = $di;
        $di->remove('payment');
        static::$instance = null;
        $di->setShared('payment', function () {
            return new static(Config::get('payment'));
        });
    }

    /**
     * @param array|Payload $payload
     * @return Payload
     * @throws GeneralException
     */
    public static function run($payload)
    {
        static::$instance === null and static::$instance = static::$di->getShared('payment');
        $payload instanceof Payload or $payload = Payload::create($payload);

        $paymentMethod = static::$instance->getPaymentMethod($payload->getGateway(), $payload->getMethod());
        $payload->setResult($paymentMethod->process($payload));
        return $payload;
    }
}
