<?php
namespace Phwoolcon\Payment\Exception;

use RuntimeException;

class GeneralException extends RuntimeException
{
    const UNDEFINED_PAYMENT_GATEWAY = 10;
    const UNDEFINED_PAYMENT_METHOD = 20;
    const INVALID_PAYMENT_METHOD = 30;
}
