<?php
if (defined('PHWOOLCON_MODELS_TRAIT_LOADED')) {
    return;
}
define('PHWOOLCON_MODELS_TRAIT_LOADED', true);

trait ConfigModelTrait
{
    // protected $_table = 'config';

    public $key;
    public $value;

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param $key
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByKey($key)
    {
        return static::findSimple([
            'key' => $key,
        ]);
    }

    /**
     * @param $key
     * @return static|false
     */
    public static function findFirstByKey($key)
    {
        return static::findFirstSimple([
            'key' => $key,
        ]);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}

trait MigrationsModelTrait
{
    // protected $_table = 'migrations';

    public $file;
    public $run_at;

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param $file
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByFile($file)
    {
        return static::findSimple([
            'file' => $file,
        ]);
    }

    /**
     * @param $file
     * @return static|false
     */
    public static function findFirstByFile($file)
    {
        return static::findFirstSimple([
            'file' => $file,
        ]);
    }

    public function getRunAt()
    {
        return $this->run_at;
    }

    public function setRunAt($runAt)
    {
        $this->run_at = $runAt;
        return $this;
    }
}

trait OrderDataModelTrait
{
    // protected $_table = 'order_data';

    public $order_id;
    public $request_data;
    public $data;
    public $status_history;

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;
        return $this;
    }

    /**
     * @param $orderId
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByOrderId($orderId)
    {
        return static::findSimple([
            'order_id' => $orderId,
        ]);
    }

    /**
     * @param $orderId
     * @return static|false
     */
    public static function findFirstByOrderId($orderId)
    {
        return static::findFirstSimple([
            'order_id' => $orderId,
        ]);
    }

    public function getRequestData()
    {
        return $this->request_data;
    }

    public function setRequestData($requestData)
    {
        $this->request_data = $requestData;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getStatusHistory()
    {
        return $this->status_history;
    }

    public function setStatusHistory($statusHistory)
    {
        $this->status_history = $statusHistory;
        return $this;
    }
}

trait OrdersModelTrait
{
    // protected $_table = 'orders';

    public $id;
    public $prefixed_order_id;
    public $trade_id;
    public $txn_id;
    public $product_name;
    public $user_identifier;
    public $username;
    public $client_id;
    public $payment_gateway;
    public $payment_method;
    public $amount;
    public $discount_amount;
    public $cash_to_pay;
    public $cash_paid;
    public $currency;
    public $amount_in_currency;
    public $status;
    public $created_at;
    public $completed_at;
    public $canceled_at;
    public $failed_at;
    public $callback_url;
    public $callback_status;
    public $callback_next_retry;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $id
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findById($id)
    {
        return static::findSimple([
            'id' => $id,
        ]);
    }

    /**
     * @param $id
     * @return static|false
     */
    public static function findFirstById($id)
    {
        return static::findFirstSimple([
            'id' => $id,
        ]);
    }

    public function getPrefixedOrderId()
    {
        return $this->prefixed_order_id;
    }

    public function setPrefixedOrderId($prefixedOrderId)
    {
        $this->prefixed_order_id = $prefixedOrderId;
        return $this;
    }

    /**
     * @param $prefixedOrderId
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByPrefixedOrderId($prefixedOrderId)
    {
        return static::findSimple([
            'prefixed_order_id' => $prefixedOrderId,
        ]);
    }

    /**
     * @param $prefixedOrderId
     * @return static|false
     */
    public static function findFirstByPrefixedOrderId($prefixedOrderId)
    {
        return static::findFirstSimple([
            'prefixed_order_id' => $prefixedOrderId,
        ]);
    }

    public function getTradeId()
    {
        return $this->trade_id;
    }

    public function setTradeId($tradeId)
    {
        $this->trade_id = $tradeId;
        return $this;
    }

    /**
     * @param $tradeId
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByTradeId($tradeId)
    {
        return static::findSimple([
            'trade_id' => $tradeId,
        ]);
    }

    /**
     * @param $tradeId
     * @return static|false
     */
    public static function findFirstByTradeId($tradeId)
    {
        return static::findFirstSimple([
            'trade_id' => $tradeId,
        ]);
    }

    public function getTxnId()
    {
        return $this->txn_id;
    }

    public function setTxnId($txnId)
    {
        $this->txn_id = $txnId;
        return $this;
    }

    public function getProductName()
    {
        return $this->product_name;
    }

    public function setProductName($productName)
    {
        $this->product_name = $productName;
        return $this;
    }

    public function getUserIdentifier()
    {
        return $this->user_identifier;
    }

    public function setUserIdentifier($userIdentifier)
    {
        $this->user_identifier = $userIdentifier;
        return $this;
    }

    /**
     * @param $userIdentifier
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByUserIdentifier($userIdentifier)
    {
        return static::findSimple([
            'user_identifier' => $userIdentifier,
        ]);
    }

    /**
     * @param $userIdentifier
     * @return static|false
     */
    public static function findFirstByUserIdentifier($userIdentifier)
    {
        return static::findFirstSimple([
            'user_identifier' => $userIdentifier,
        ]);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getClientId()
    {
        return $this->client_id;
    }

    public function setClientId($clientId)
    {
        $this->client_id = $clientId;
        return $this;
    }

    public function getPaymentGateway()
    {
        return $this->payment_gateway;
    }

    public function setPaymentGateway($paymentGateway)
    {
        $this->payment_gateway = $paymentGateway;
        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    public function setPaymentMethod($paymentMethod)
    {
        $this->payment_method = $paymentMethod;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDiscountAmount()
    {
        return $this->discount_amount;
    }

    public function setDiscountAmount($discountAmount)
    {
        $this->discount_amount = $discountAmount;
        return $this;
    }

    public function getCashToPay()
    {
        return $this->cash_to_pay;
    }

    public function setCashToPay($cashToPay)
    {
        $this->cash_to_pay = $cashToPay;
        return $this;
    }

    public function getCashPaid()
    {
        return $this->cash_paid;
    }

    public function setCashPaid($cashPaid)
    {
        $this->cash_paid = $cashPaid;
        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function getAmountInCurrency()
    {
        return $this->amount_in_currency;
    }

    public function setAmountInCurrency($amountInCurrency)
    {
        $this->amount_in_currency = $amountInCurrency;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getCompletedAt()
    {
        return $this->completed_at;
    }

    public function setCompletedAt($completedAt)
    {
        $this->completed_at = $completedAt;
        return $this;
    }

    public function getCanceledAt()
    {
        return $this->canceled_at;
    }

    public function setCanceledAt($canceledAt)
    {
        $this->canceled_at = $canceledAt;
        return $this;
    }

    public function getFailedAt()
    {
        return $this->failed_at;
    }

    public function setFailedAt($failedAt)
    {
        $this->failed_at = $failedAt;
        return $this;
    }

    public function getCallbackUrl()
    {
        return $this->callback_url;
    }

    public function setCallbackUrl($callbackUrl)
    {
        $this->callback_url = $callbackUrl;
        return $this;
    }

    public function getCallbackStatus()
    {
        return $this->callback_status;
    }

    public function setCallbackStatus($callbackStatus)
    {
        $this->callback_status = $callbackStatus;
        return $this;
    }

    public function getCallbackNextRetry()
    {
        return $this->callback_next_retry;
    }

    public function setCallbackNextRetry($callbackNextRetry)
    {
        $this->callback_next_retry = $callbackNextRetry;
        return $this;
    }
}

trait SsoSitesModelTrait
{
    // protected $_table = 'sso_sites';

    public $id;
    public $site_name;
    public $site_url;
    public $site_secret;
    public $created_at;
    public $updated_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $id
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findById($id)
    {
        return static::findSimple([
            'id' => $id,
        ]);
    }

    /**
     * @param $id
     * @return static|false
     */
    public static function findFirstById($id)
    {
        return static::findFirstSimple([
            'id' => $id,
        ]);
    }

    public function getSiteName()
    {
        return $this->site_name;
    }

    public function setSiteName($siteName)
    {
        $this->site_name = $siteName;
        return $this;
    }

    public function getSiteUrl()
    {
        return $this->site_url;
    }

    public function setSiteUrl($siteUrl)
    {
        $this->site_url = $siteUrl;
        return $this;
    }

    public function getSiteSecret()
    {
        return $this->site_secret;
    }

    public function setSiteSecret($siteSecret)
    {
        $this->site_secret = $siteSecret;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}

trait UserProfileModelTrait
{
    // protected $_table = 'user_profile';

    public $user_id;
    public $real_name;
    public $avatar;
    public $remember_token;
    public $extra_data;

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * @param $userId
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByUserId($userId)
    {
        return static::findSimple([
            'user_id' => $userId,
        ]);
    }

    /**
     * @param $userId
     * @return static|false
     */
    public static function findFirstByUserId($userId)
    {
        return static::findFirstSimple([
            'user_id' => $userId,
        ]);
    }

    public function getRealName()
    {
        return $this->real_name;
    }

    public function setRealName($realName)
    {
        $this->real_name = $realName;
        return $this;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($rememberToken)
    {
        $this->remember_token = $rememberToken;
        return $this;
    }

    public function getExtraData()
    {
        return $this->extra_data;
    }

    public function setExtraData($extraData)
    {
        $this->extra_data = $extraData;
        return $this;
    }
}

trait UsersModelTrait
{
    // protected $_table = 'users';

    public $id;
    public $username;
    public $email;
    public $mobile;
    public $password;
    public $confirmed;
    public $created_at;
    public $updated_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $id
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findById($id)
    {
        return static::findSimple([
            'id' => $id,
        ]);
    }

    /**
     * @param $id
     * @return static|false
     */
    public static function findFirstById($id)
    {
        return static::findFirstSimple([
            'id' => $id,
        ]);
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param $username
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByUsername($username)
    {
        return static::findSimple([
            'username' => $username,
        ]);
    }

    /**
     * @param $username
     * @return static|false
     */
    public static function findFirstByUsername($username)
    {
        return static::findFirstSimple([
            'username' => $username,
        ]);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param $email
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByEmail($email)
    {
        return static::findSimple([
            'email' => $email,
        ]);
    }

    /**
     * @param $email
     * @return static|false
     */
    public static function findFirstByEmail($email)
    {
        return static::findFirstSimple([
            'email' => $email,
        ]);
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * @param $mobile
     * @return static[]|\Phalcon\Mvc\Model\Resultset\Simple
     */
    public static function findByMobile($mobile)
    {
        return static::findSimple([
            'mobile' => $mobile,
        ]);
    }

    /**
     * @param $mobile
     * @return static|false
     */
    public static function findFirstByMobile($mobile)
    {
        return static::findFirstSimple([
            'mobile' => $mobile,
        ]);
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getConfirmed()
    {
        return $this->confirmed;
    }

    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}
