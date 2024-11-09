<?php

namespace Config;

class Config
{
    private static $config = null;

    private static function loadConfig()
    {
        if (self::$config === null) {
            self::$config = require 'env.php'; // Load config file
        }
    }

    public static function get($key)
    {

        $result = self::loadConfig(); // Ensure the config is loaded
        return self::$config[$key] ?? null;
    }
}
