<?php
namespace App\Middlewares;
use App\Services\Response;
use App\Services\GetIp;
use App\Services\Session;
use DateTime;
use DateInterval;

class RateLimitMiddleware
{
    private $session;
    private $limit; // Number of allowed requests
    private $timeFrame; // Time frame in seconds

    public function __construct()
    {
        $this->limit = config('RATE_LIMIT');
        $this->timeFrame = config('TIME_FRAME_IN_SECONDS');
        $this->session = new Session();
    }
    /**
     * Handles session management by checking for an existing session based on the client's IP address and requested action.
     * If no session exists, a new session is created; otherwise, the existing session is processed.
     */
    public function handle()
    {
        $ip = GetIp::getClientIp();
        $action = $_SERVER['REQUEST_URI'];

        $data = $this->session->getLastActivityLimit($ip, $action);

        if (!$data) {
            return $this->createNewSession($ip, $action);
        }
        //there is session exist before
        return $this->processExistingSession($data, $ip, $action);
    }

    /**
     * Creates a new session for the given IP address and action by adding it to db.
     * @param string $ip The client's IP address.
     * @param string $action The action being performed by the client.
     * @return bool Returns true upon successful creation of the new session.
     */
    private function createNewSession($ip, $action)
    {
        $this->session->addSession($ip,$action);
        return true;
    }

    /**
     * Processes an existing session by checking for expiration and request limits.
     * 
     * If the session's time limit has expired, it resets the session. 
     * If the request limit has been reached, it returns a JSON response indicating the limit has been reached.
     * Otherwise, it updates the session count.
     * 
     * @param array $data The existing session data.
     * @param string $ip The client's IP address.
     * @param string $action The action being performed by the client.
     * @return mixed Returns the result of resetting the session, a JSON response, or the updated session count.
     */
    private function processExistingSession($data, $ip, $action)
    {
        if ($this->hasTimeLimitExpired($data['last_action'])) {
            return $this->resetSession($data['id']);
        }

        if ($this->hasLimitBeenReached($data['count'])) {
            return Response::jsonResponse([
                "status" => HTTP_TOO_MANY_REQUESTS,
                "message" => trans('limit_reached')
            ]);
        }

        return $this->updateSessionCount($data);
    }

    private function hasTimeLimitExpired($lastAction)
    {
        $now = new DateTime();
        $timeLimit = $now->sub(new DateInterval('PT' . $this->timeFrame . 'S'));
        return new DateTime($lastAction) < $timeLimit;
    }

    private function resetSession($sessionId)
    {
        $now = new DateTime();
        $this->session->updateSession(1, $sessionId, $now->format('Y-m-d H:i:s'));
        return true;
    }

    private function hasLimitBeenReached($count)
    {
        return $count >= $this->limit;
    }

    private function updateSessionCount($data)
    {
        $this->session->updateSession($data['count'] + 1, $data['id'], $data['last_action']);
        return true;
    }
}
