<?php
namespace App\Services;
class RedisConnection
{
    private static $instance = null;
    private $redis;

    private function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        // Create a new Redis instance
        $this->redis = new \Redis();

        try {
            // Connect to Redis server
            $this->redis->connect(config('REDIS_HOST'), config('REDIS_PORT'));
            // Authenticate using password
            $this->redis->auth(config('REDIS_PASSWORD'));
        } catch (\RedisException $e) {
            throw new \Exception('Redis connection failed: ' . $e->getMessage());
        }
    }

    //singleton instance 
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->redis;
    }
}
