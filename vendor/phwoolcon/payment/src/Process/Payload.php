<?php
namespace Phwoolcon\Payment\Process;

use Phwoolcon\Payload as PhwoolconPayload;

/**
 * Class Payload
 * @package Phwoolcon\Payment\Process
 *
 * @method string getGateway()
 * @method string getMethod()
 * @method Result getResult()
 */
class Payload extends PhwoolconPayload
{

    public function setResult(Result $result)
    {
        return $this->setData('result', $result);
    }
}
