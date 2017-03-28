<?php
namespace Phwoolcon\Payment\Exception;

use RuntimeException;

class CallbackException extends RuntimeException
{
    const BAD_PARAMETERS = 4101;
    const ORDER_NOT_FOUND = 4102;
    const INVALID_AMOUNT = 4103;
    const INVALID_SIGN = 4104;
}
