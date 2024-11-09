<?php

namespace App\Services;

use DateTime;
use App\Services\RedisConnection;

class Session
{
    private $redis;

    public function __construct()
    {
        $this->redis = RedisConnection::getInstance();
    }

    /**
     * Retrieve the action limit for a specific user from Redis (it used for throtlling).
     *
     *
     * @param int|string $userID The ID of the user whose action limit is being retrieved.
     * @param string $action The action for which the limit is being queried.
     * @return array|null The action limit data as an associative array, or null if not found.
     */
    public function getUserActionLimit($ip, $action)
    {
        $key = "ip:{$ip}:action:{$action}";
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Retrieve the last activity limit for a specific IP address and action from Redis (used for rate limit).
     *
     *
     * @param string $ip The IP address 
     * @param string $action The action
     * @return array|null The last activity limit data as an associative array, or null if not found.
     */
    public function getLastActivityLimit($ip, $action)
    {
        $key = "ip:{$ip}:action:{$action}";
        $data = $this->redis->get($key);

        return $data ? json_decode($data, true) : null;
    }

    /**
     * Update the session data for a specific session ID in Redis (for rate limit, throtling).
     *
     *
     * @param int $count The new count value to update in the session data.
     * @param string $id The ID of the session (key) to be updated.
     * @param string $last_action The timestamp or identifier of the last action performed in the session.
     */
    public function updateSession($count, $id, $last_action)
    {
        $key = $id;
        $sessionData = $this->redis->get($key);

        if ($sessionData) {
            $sessionData = json_decode($sessionData, true);
            $sessionData['count'] = $count;
            $sessionData['last_action'] = $last_action;

            $this->redis->set($key, json_encode($sessionData));
        }
    }

    /**
     * Add a new session to Redis with the specified details.
     *    
     * @param string $ip The IP address.
     * @param string $action The action associated with the session.
     * @param int|null $userId Optional user ID associated with the session(used in throttling).
     */

    public function addSession($ip, $action, $userId = null)
    {
        $now = new DateTime();
        $key = "ip:{$ip}:action:{$action}";

        $sessionData = [
            'id'=>$key,
            'ip' => $ip,
            'user_id' => $userId,
            'action' => $action,
            'last_action' => $now->format('Y-m-d H:i:s'),
            'count'=>1
        ];
        $this->redis->set($key, json_encode($sessionData));
        $sessionData = $this->redis->get($key);
        // Optionally, you can set a TTL (time to live) for the session
        $this->redis->expire($key, 3600); // Session expires in 1 hour
    }
}
