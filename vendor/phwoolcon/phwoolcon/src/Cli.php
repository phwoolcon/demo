<?php

namespace Phwoolcon;

use Phalcon\Di;
use Symfony\Component\Console\Application;

class Cli
{
    protected static $consoleWidth;

    public static function register(Di $di)
    {
        $app = new Application(Config::get('app.name'), Config::get('app.version'));
        foreach (Config::get('commands') as $name => $class) {
            $app->add(new $class($name, $di));
        }
        return $app;
    }

    public static function getConsoleWidth()
    {
        if (!static::$consoleWidth) {
            static::$consoleWidth = (int)`tput cols`;
            static::$consoleWidth > 30 or static::$consoleWidth = 80;
        }
        return static::$consoleWidth;
    }
}
