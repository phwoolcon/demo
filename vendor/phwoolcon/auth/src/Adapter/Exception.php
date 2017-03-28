<?php

namespace Phwoolcon\Auth\Adapter;

use RuntimeException;

class Exception extends RuntimeException
{
    protected $realMessage;

    public function __construct($message, $code = 0, $realMessage = null)
    {
        $this->realMessage = $realMessage;
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        if ($this->realMessage) {
            $message = $this->message;
            $this->message .= ': ' . $this->realMessage;
            $string = parent::__toString();
            $this->message = $message;
            return $string;
        }
        return parent::__toString();
    }
}
