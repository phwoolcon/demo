<?php

namespace Phwoolcon\Auth;

use Phalcon\Di;
use Phalcon\Events\Event;
use Phalcon\Security;
use Phwoolcon\Auth\Adapter\Exception;
use Phwoolcon\Auth\Adapter\Generic;
use Phwoolcon\Config;
use Phwoolcon\Events;

class Auth
{
    /**
     * @var Di
     */
    protected static $di;
    protected static $config = [];
    /**
     * @var AdapterInterface|Generic
     */
    protected static $instance;

    protected static function addPhwoolconJsOptions()
    {
        Events::attach('view:generatePhwoolconJsOptions', function (Event $event) {
            $config = static::$config;
            $options = $event->getData() ?: [];
            $options['isSsoServer'] = true;
            $event->setData($options = array_merge($options, $config['phwoolcon_js_options']));
            return $options;
        });
    }

    public static function getInstance()
    {
        static::$instance or static::$instance = static::$di->getShared('auth');
        return static::$instance;
    }

    public static function getOption($key)
    {
        static::$instance or static::$instance = static::$di->getShared('auth');
        return static::$instance->getOption($key);
    }

    /**
     * @return false|\Phwoolcon\Model\User
     */
    public static function getUser()
    {
        static::$instance or static::$instance = static::$di->getShared('auth');
        return static::$instance->getUser();
    }

    public static function register(Di $di)
    {
        static::$di = $di;
        static::$config = Config::get('auth');
        $di->setShared('auth', function () {
            $di = static::$di;
            $config = static::$config;
            $class = $config['adapter'];
            $options = $config['options'];
            strpos($class, '\\') === false and $class = 'Phwoolcon\\Auth\\Adapter\\' . $class;
            if ($di->has($class)) {
                $class = $di->getRaw($class);
            }
            if (!class_exists($class)) {
                throw new Exception('Admin auth adapter class should implement ' . AdapterInterface::class);
            }
            /* @var Security $hasher */
            $hasher = static::$di->getShared('security');
            $hasher->setDefaultHash($options['security']['default_hash']);
            $hasher->setWorkFactor($options['security']['work_factor']);
            $adapter = new $class($options, $hasher, $di);
            if (!$adapter instanceof AdapterInterface) {
                throw new Exception('Auth adapter class should implement ' . AdapterInterface::class);
            }
            return $adapter;
        });
        static::addPhwoolconJsOptions();
    }
}
