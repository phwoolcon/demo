<?php
namespace Phwoolcon\Payment\Tests\Helper;

use Exception;
use Phwoolcon\Log;
use Phwoolcon\Payment\Exception\CallbackException;
use Phwoolcon\Payment\MethodInterface;
use Phwoolcon\Payment\MethodTrait;
use Phwoolcon\Payment\Process\Result;

class TestPaymentMethod implements MethodInterface
{
    use MethodTrait;

    public function callback($data)
    {
        try {
            $this->verifyCallbackParameters($data);
            $order = $this->getCallbackOrder(fnGet($data, 'order_id'));
            $this->verifyCallbackSign($order, $data, fnGet($data, 'sign'));
            $status = fnGet($data, 'status');
            if ($status == 'complete') {
                if ($order->canComplete()) {
                    $this->verifyCallbackAmount($order, fnGet($data, 'amount'));
                    $order->complete('Test Callback');
                    $order->save();
                }
                return Result::create([
                    'order' => $order,
                    'response' => 'OK',
                ]);
            }
            $failureMessage = fnGet($data, 'failure_message');
            if ($order->canFail()) {
                $order->fail($failureMessage);
                $order->save();
            }
            return Result::create([
                'error' => $failureMessage,
                'response' => 'OK',
            ]);
        } catch (CallbackException $e) {
            return Result::create([
                'error' => $e,
                'response' => 'FAILED',
            ]);
        } catch (Exception $e) {
            Log::exception($e);
            return Result::create([
                'error' => $e,
                'response' => 'FAILED',
            ]);
        }
    }

    public function createCallbackSign($order, $callbackData)
    {
        return fnGet($callbackData, 'sign');
    }

    public function payRequest($data)
    {
        $order = $this->prepareOrder($data);
        $order->save();
        return Result::create([
            'order' => $order,
        ]);
    }
}
