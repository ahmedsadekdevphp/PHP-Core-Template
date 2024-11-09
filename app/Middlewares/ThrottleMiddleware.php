<?php

namespace App\Middlewares;
use App\Services\Session;
use App\Services\Response;
use App\Services\GetIp;
use DateTime;
use DateInterval;

class ThrottleMiddleware
{
    private $session;
    private $limits;

    public function __construct()
    {
        $this->limits = config('throttle');
        $this->session = new Session();
    }

    /**
     * Main method that handles throttling by identifying the user and action.
     * 
     * @return mixed
     */
    public function handle()
    {
        $ip = GetIp::getClientIp();
        // Get the current HTTP method (e.g., POST, PUT, DELETE)
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $action = $this->getActionFromMethod($httpMethod);
        if (!$action || !isset($this->limits[$action])) {
            return true; // Allow request to proceed
        }
        $userId = $_SESSION['user_data']['id'] ?? null;
        if (!$userId) {
            return true;
        }
        // Check the user action limits
        $actionData = $this->session->getUserActionLimit($ip, $action);
        if (!$actionData) {
            return $this->createUserActionSession($ip, $action, $userId);
        }

        return $this->processUserActionSession($actionData, $action);
    }

    /**
     * Maps the HTTP method to the respective action (create, update, delete).
     * 
     * @param string $method The HTTP method (POST, PUT, DELETE)
     * @return string|null The corresponding action or null if no action matches
     */
    private function getActionFromMethod($method)
    {
        switch ($method) {
            case 'POST':
                return 'create';
            case 'PUT':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return null; // No action for GET or other methods
        }
    }

    private function createUserActionSession($ip, $action, $userId)
    {
        $this->session->addSession($ip, $action, $userId);
        return true; // Allow the action
    }

    private function processUserActionSession($data, $action)
    {
        //reset seesion if time frame expired
        if ($this->hasUserActionTimeLimitExpired($data['last_action'], $action)) {
            return $this->resetUserActionSession($data['id']);
        }
        //suspend request is limit reached
        if ($this->hasUserActionLimitBeenReached($data['count'], $action)) {
            return Response::jsonResponse([
                "status" => HTTP_TOO_MANY_REQUESTS,
                "message" => trans("action_limit_reached_for") . $action
            ]);
        }
        //update user session has remaining limit 
        return $this->updateUserActionSessionCount($data);
    }

    /**
     * Checks if the time frame for the specific action has expired.
     */

    private function hasUserActionTimeLimitExpired($lastAction, $action)
    {
        $timFrame = $this->limits[$action]['time_frame'];
        $now = new DateTime();
        $timeLimit = $now->sub(new DateInterval('PT' . $timFrame . 'S'));
        return new DateTime($lastAction) < $timeLimit;
    }
    /**
     * Resets the session count after the time limit expires.
     */
    private function resetUserActionSession($sessionId)
    {
        $this->session->updateSession(1, $sessionId, date('Y-m-d H:i:s'));
        return true; // Allow the action
    }

    /**
     * Checks if the user has reached the action-specific limit.
     */

    private function hasUserActionLimitBeenReached($count, $action)
    {
        return $count >= $this->limits[$action]['count'];
    }



    private function updateUserActionSessionCount($data)
    {
        $this->session->updateSession($data['count'] + 1, $data['id'], $data['last_action']);
        return true; // Allow the action
    }
}
