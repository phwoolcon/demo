<?php
namespace Phwoolcon\Payment;

interface MethodInterface
{

    public function callback($data);

    public function getConfig($key, $default = null);

    public function payRequest($data);

    public function process($payload);

    public function setConfig($key, $value = null);
}
