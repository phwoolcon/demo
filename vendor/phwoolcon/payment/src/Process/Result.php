<?php
namespace Phwoolcon\Payment\Process;

use Exception;
use Phwoolcon\Payment\Model\Order;
use Phwoolcon\Payload as PhwoolconPayload;
use Phwoolcon\Payment\Exception\ResultException;

/**
 * Class Result
 * @package Phwoolcon\Payment\Process
 *
 * @method array|Exception getError()
 * @method Order getOrder()
 * @method string|array getResponse()
 */
class Result extends PhwoolconPayload
{

    public function __construct(array $data)
    {
        if (!isset($data['error']) && !isset($data['order'])) {
            throw new ResultException(
                __('Either error or order is required in a payment result'),
                ResultException::INVALID_RESULT_FORMAT
            );
        }
        parent::__construct($data);
    }
}
