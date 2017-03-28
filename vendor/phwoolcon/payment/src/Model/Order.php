<?php
namespace Phwoolcon\Payment\Model;

use Phwoolcon\Model;

/**
 * Class Order
 * @package Phwoolcon\Payment\Model
 *
 * @property string $status
 * @method float getAmount()
 * @method float getCashPaid()
 * @method float getCashToPay()
 * @method int getCreatedAt()
 * @method string getProductName()
 * @method string getStatus()
 * @method string getTradeId()
 * @method Order setAmount(float $amount)
 * @method Order setStatus(string $status)
 * @method Order setPrefixedOrderId(string $orderId)
 */
class Order extends Model
{
    use OrderFsmTrait;
    const PREFIXED_ORDER_ID_FIELD = 'prefixed_order_id';

    const STATUS_CANCELED = 'canceled';
    const STATUS_CANCELING = 'canceling';
    const STATUS_COMPLETE = 'complete';
    const STATUS_FAILED = 'failed';
    const STATUS_FAILING = 'failing';
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';

    const CALLBACK_STATUS_DONE = 'done';
    const CALLBACK_STATUS_FAILED = 'failed';
    const CALLBACK_STATUS_TRIED = 'tried';

    protected $_table = 'orders';
    protected $keyFields = [
        'product_name',
        'user_id',
        'amount',
    ];

    protected $protectedFieldsOnPreparation = [
        'txn_id',
        'created_at',
        'completed_at',
        'status',
    ];

    protected $requiredFieldsOnPreparation = [
        'trade_id',
        'client_id',
        'user_identifier',
        'product_name',
        'payment_gateway',
        'payment_method',
        'amount',
    ];

    protected $orderData;

    public function generateOrderId($prefix = '')
    {
        $orderId = $prefix . (date('Y') - 2004) . date('md') . substr(time(), -5) . substr(microtime(), 2, 3) .
            static::$_distributedOptions['node_id'] . mt_rand(100, 999);
        $hasOrderId = $this->findFirst([
            static::PREFIXED_ORDER_ID_FIELD . ' = :id:',
            'bind' => [
                'id' => $orderId,
            ],
        ]);
        // @codeCoverageIgnoreStart
        if ($hasOrderId) {
            return $this->generateOrderId($prefix);
        }
        // @codeCoverageIgnoreEnd
        $this->setPrefixedOrderId($orderId);
        return $this;
    }

    public static function getByPrefixedOrderId($orderId)
    {
        return static::findFirstSimple([
            static::PREFIXED_ORDER_ID_FIELD => $orderId,
        ]);
    }

    public static function getByTradeId($tradeId, $clientId)
    {
        return static::findFirstSimple([
            'trade_id' => $tradeId,
            'client_id' => $clientId,
        ]);
    }

    public function getKeyFields()
    {
        $fields = array_values($this->keyFields);
        return array_combine($fields, $fields);
    }

    /**
     * @param string $key
     * @return mixed|OrderData
     */
    public function getOrderData($key = null)
    {
        if ($this->orderData === null) {
            if ($existingOrderData = $this->__get('order_data')) {
                $this->orderData = $existingOrderData;
            } else {
                $this->orderData = $this->_dependencyInjector->get(OrderData::class);
                $this->__set('order_data', $this->orderData);
            }
        }
        $data = $this->orderData->getData('data');
        return $key ? fnGet($data, $key) : $this->orderData;
    }

    public function getOrderId()
    {
        return $this->getData(static::PREFIXED_ORDER_ID_FIELD);
    }

    public function getPaymentGatewayUrl()
    {
        return $this->getOrderData('payment_gateway_url');
    }

    /**
     * Alias of $this->getOrderId()
     * @codeCoverageIgnore
     * @return string
     */
    public function getPrefixedOrderId()
    {
        return $this->getData(static::PREFIXED_ORDER_ID_FIELD);
    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getProtectedFieldsOnPreparation()
    {
        return $this->protectedFieldsOnPreparation;
    }

    public function initialize()
    {
        $class = OrderData::class;
        $this->hasOne('id', $class, 'order_id', ['alias' => 'order_data']);
        parent::initialize();
    }

    public function setOrderData($key, $value = null)
    {
        $orderData = $this->getOrderData();
        $data = $orderData->getData('data') ?: [];
        if (is_array($key)) {
            $data = $key;
        } else {
            if (is_scalar($key)) {
                if ($value === null) {
                    unset($data[$key]);
                } else {
                    $data[$key] = $value;
                }
            }
        }

        $orderData->setData('data', $data);
        return $this;
    }

    /**
     * @param array $keyFields
     * @return $this
     * @codeCoverageIgnore
     */
    public function setKeyFields($keyFields)
    {
        $this->keyFields = $keyFields;
        return $this;
    }

    public function setPaymentGatewayUrl($url)
    {
        return $this->setOrderData('payment_gateway_url', $url);
    }

    /**
     * @param array $protectedFieldsOnPreparation
     * @return $this
     * @codeCoverageIgnore
     */
    public function setProtectedFieldsOnPreparation($protectedFieldsOnPreparation)
    {
        $this->protectedFieldsOnPreparation = $protectedFieldsOnPreparation;
        return $this;
    }

    public function updateStatus($status, $comment)
    {
        $this->setStatus($status);
        $orderData = $this->getOrderData();
        $statusHistory = $orderData->getData('status_history') ?: [];
        $time = explode(' ', microtime());
        $statusHistory[$time[1] . substr($time[0], 1)] = [
            'status' => $status,
            'comment' => $comment,
        ];
        $orderData->setData('status_history', $statusHistory);
        return $this;
    }
}
