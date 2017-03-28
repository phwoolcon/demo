<?php
namespace Phwoolcon\Payment\Model;

use Phalcon\Di;
use Phwoolcon\Events;
use Phwoolcon\Payment\Exception\OrderException;
use Phwoolcon\Fsm\StateMachine;

/**
 * Class OrderFsmTrait
 * @package Phwoolcon\Payment\Model
 *
 * @method Order cancel(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method bool canCancel()
 * @method bool canComplete()
 * @method bool canConfirm()
 * @method bool canConfirmCancel()
 * @method bool canConfirmFail()
 * @method bool canFail()
 * @method bool canPrepare()
 * @method Order complete(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method Order confirm(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method Order confirmCancel(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method Order confirmFail(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method Order fail(string $comment = null)
 * @see OrderFsmTrait::takeAction()
 * @method Order updateStatus(string $status, string $comment)
 */
trait OrderFsmTrait
{
    /**
     * @var StateMachine
     */
    protected $fsm;

    /**
     * Defines FSM transitions here
     * Structure:
     *  'current_status' => [
     *      'actionName' => 'to_status'
     *  ]
     *
     * NOTICE:
     * actionName is in camelCase to maintain the consistency with method names used in static::__call()
     *
     * @var array
     */
    protected $fsmTransitions = [
        Order::STATUS_PENDING => [
            'prepare' => Order::STATUS_PENDING,
            'confirm' => Order::STATUS_PROCESSING,
            'complete' => Order::STATUS_COMPLETE,
            'cancel' => Order::STATUS_CANCELING,
            'fail' => Order::STATUS_FAILING,
        ],
        Order::STATUS_PROCESSING => [
            'complete' => Order::STATUS_COMPLETE,
            'cancel' => Order::STATUS_CANCELING,
            'fail' => Order::STATUS_FAILING,
        ],
        Order::STATUS_CANCELING => [
            'complete' => Order::STATUS_COMPLETE,
            'confirmCancel' => Order::STATUS_CANCELED,
        ],
        Order::STATUS_FAILING => [
            'complete' => Order::STATUS_COMPLETE,
            'confirmFail' => Order::STATUS_FAILED,
        ],
    ];

    /**
     * Defines FSM actions here
     * Structure:
     *  'actionName' => [
     *      'default_comment' => 'The comment will be saved in order history',
     *      'error_code' => $number,        // An error will be thrown if action is not allowed
     *      'error_message' => $message,    // Specify error code and message here
     *  ]
     *
     * @var array
     */
    protected $fsmActions = [
        'cancel' => [
            'default_comment' => 'Order canceling',
            'error_code' => OrderException::ERROR_CODE_ORDER_CANNOT_BE_CANCELED,
            'error_message' => 'Can not mark a %status% order as canceling',
        ],
        'complete' => [
            'default_comment' => 'Order complete',
            'error_code' => OrderException::ERROR_CODE_ORDER_COMPLETED,
            'error_message' => 'Can not complete a %status% order',
        ],
        'confirm' => [
            'default_comment' => 'Order processing',
            'error_code' => OrderException::ERROR_CODE_ORDER_PROCESSING,
            'error_message' => 'Can not confirm a %status% order',
        ],
        'confirmCancel' => [
            'default_comment' => 'Order canceled',
            'error_code' => OrderException::ERROR_CODE_ORDER_CANNOT_BE_CANCELED,
            'error_message' => 'Can not cancel a %status% order',
        ],
        'confirmFail' => [
            'default_comment' => 'Order failed',
            'error_code' => OrderException::ERROR_CODE_ORDER_CANNOT_BE_FAILED,
            'error_message' => 'Can not fail a %status% order',
        ],
        'fail' => [
            'default_comment' => 'Order failing',
            'error_code' => OrderException::ERROR_CODE_ORDER_CANNOT_BE_FAILED,
            'error_message' => 'Can not mark a %status% order as failing',
        ],
    ];

    public function __call($method, $arguments)
    {
        if (isset($this->fsmActions[$method])) {
            $this->takeAction($method, $arguments);
            return $this;
        }
        if (($prefix = substr($method, 0, 3)) == 'can') {
            $action = lcfirst(substr($method, 3));
            return $this->getFsm()->canDoAction($action);
        }
        return parent::__call($method, $arguments);
    }

    protected function afterComplete()
    {
        $this->resetCallbackStatus()
            ->setData('completed_at', time())
            ->setData('cash_paid', $this->getData('cash_to_pay'))
            ->setData('cash_to_pay', 0);
    }

    protected function afterConfirmCancel()
    {
        $this->resetCallbackStatus()
            ->setData('canceled_at', time());
    }

    protected function afterConfirmFail()
    {
        $this->resetCallbackStatus()
            ->setData('failed_at', time());
    }

    /**
     * @return StateMachine
     */
    public function getFsm()
    {
        if (!$this->fsm) {
            $this->fsm = StateMachine::create($this->fsmTransitions, $this->getFsmHistory());
        }
        return $this->fsm;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getFsmActions()
    {
        return $this->fsmActions;
    }

    public function getFsmHistory()
    {
        return $this->getOrderData('fsm_history') ?: [];
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getFsmTransitions()
    {
        return $this->fsmTransitions;
    }

    public static function prepare($data)
    {
        /* @var Order $order */
        $order = Di::getDefault()->get(Order::class);

        // Detect required fields
        foreach ($order->requiredFieldsOnPreparation as $field) {
            if (empty($data[$field])) {
                throw new OrderException(__('Missing required field %field%', [
                    'field' => $field,
                ]), OrderException::ERROR_CODE_BAD_PARAMETERS);
            }
        }
        // Load existing order if any
        if ($existingOrder = $order::getByTradeId($data['trade_id'], $data['client_id'])) {
            $order = $existingOrder;
            if (!$order->canPrepare()) {
                throw new OrderException(__('Order "%trade_id%" is %status%, please do not submit repeatedly', [
                    'trade_id' => $data['trade_id'],
                    'status' => $order->getStatus(),
                ]), OrderException::ERROR_CODE_ORDER_PROCESSING);
            }
        }
        $order->getOrderData()->setData('request_data', $data);

        // Fire before_prepare_order_data event
        $data = Events::fire('order:before_prepare_order_data', $order, $data) ?: $data;

        // Filter protected fields
        foreach ($order->protectedFieldsOnPreparation as $field) {
            unset($data[$field]);
        }
        unset($data[static::PREFIXED_ORDER_ID_FIELD]);

        // Remove objects in $data
        foreach ($data as $k => $v) {
            // @codeCoverageIgnoreStart
            if (is_object($v)) {
                unset($data[$k]);
            };
            // @codeCoverageIgnoreEnd
        }

        // Verify order data
        $amount = $data['amount_in_currency'] = fnGet($data, 'amount') * 1;
        // TODO process currency exchange rate
        $data['currency'] = fnGet($data, 'currency', 'CNY');
        $data['amount'] = $amount;

        if ($amount <= 0) {
            throw new OrderException(__('Invalid order amount'), OrderException::ERROR_CODE_BAD_PARAMETERS);
        }
        $cashToPay = fnGet($data, 'cash_to_pay', $amount);
        if ($cashToPay < 0) {
            throw new OrderException(__('Invalid order cash to pay'), OrderException::ERROR_CODE_BAD_PARAMETERS);
        }
        $data['cash_to_pay'] = $cashToPay;

        // Set order attributes
        $keyFields = $order->getKeyFields();
        foreach ($order->toArray() as $attribute => $oldValue) {
            $newValue = fnGet($data, $attribute);
            if (isset($keyFields[$attribute]) && $oldValue && $oldValue != $newValue) {
                throw new OrderException(
                    __('Order crucial attribute [%attribute%] changed', compact('attribute')),
                    OrderException::ERROR_CODE_KEY_PARAMETERS_CHANGED
                );
            }
            $newValue === null or $order->setData($attribute, $newValue);
        }

        // Fire after_prepare_order_data event
        $data = Events::fire('order:after_prepare_order_data', $order, $data) ?: $data;
        // Generate order id
        $order->generateOrderId(fnGet($data, 'order_prefix'));
        unset($data['order_prefix']);
        $order->setOrderData($data)
            ->updateStatus($order->getFsm()->getCurrentState(), __('Order initialized'))
            ->refreshFsmHistory();
        return $order;
    }

    public function refreshFsmHistory()
    {
        $this->setOrderData('fsm_history', $this->getFsm()->getHistory());
        return $this;
    }

    /**
     * @return Order
     */
    public function resetCallbackStatus()
    {
        $this->setData('callback_status', '');
        return $this;
    }

    /**
     * @param array $actions
     * @return $this
     * @codeCoverageIgnore
     */
    public function setFsmActions(array $actions)
    {
        $this->fsmActions = $actions;
        return $this;
    }

    /**
     * @param array $fsmTransitions
     * @return $this
     * @codeCoverageIgnore
     */
    public function setFsmTransitions(array $fsmTransitions)
    {
        $this->fsmTransitions = $fsmTransitions;
        return $this;
    }

    /**
     * Take order actions such as cancel, confirm, complete, fail, and so on
     * You can extend your custom action by modifying property $this->fsmActions and $this->fsmTransitions
     *
     * @param string $method
     * @param array  $arguments
     */
    protected function takeAction($method, $arguments)
    {
        $action = lcfirst($method);
        $options = $this->fsmActions[$method];
        $comment = empty($arguments[0]) ? __($options['default_comment']) : $arguments[0];
        if (!$this->getFsm()->canDoAction($action)) {
            throw new OrderException(__($options['error_message'], [
                'status' => $this->getStatus(),
            ]), $options['error_code']);
        }
        /**
         * Fire before action events
         *
         * @see OrderFsmTrait::beforeCancel();          Event type: "order:before_cancel"
         * @see OrderFsmTrait::beforeComplete();        Event type: "order:before_complete"
         * @see OrderFsmTrait::beforeConfirm();         Event type: "order:before_confirm"
         * @see OrderFsmTrait::beforeConfirmCancel();   Event type: "order:before_confirm_cancel"
         * @see OrderFsmTrait::beforeConfirmFail();     Event type: "order:before_confirm_fail"
         * @see OrderFsmTrait::beforeFail();            Event type: "order:before_fail"
         */
        Events::fire('order:before_' . $action, $this);
        method_exists($this, $beforeMethod = 'before' . $method) and $this->{$beforeMethod}();

        $status = $this->getFsm()->doAction($action);
        $this->updateStatus($status, $comment)
            ->refreshFsmHistory();

        /**
         * Fire after action events
         *
         * @see OrderFsmTrait::afterCancel();           Event type: "order:after_cancel"
         * @see OrderFsmTrait::afterComplete();         Event type: "order:after_complete"
         * @see OrderFsmTrait::afterConfirm();          Event type: "order:after_confirm"
         * @see OrderFsmTrait::afterConfirmCancel();    Event type: "order:after_confirm_cancel"
         * @see OrderFsmTrait::afterConfirmFail();      Event type: "order:after_confirm_fail"
         * @see OrderFsmTrait::afterFail();             Event type: "order:after_fail"
         */
        method_exists($this, $afterMethod = 'after' . $method) and $this->{$afterMethod}();
        Events::fire('order:after_' . $action, $this);
    }
}
