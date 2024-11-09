<?php

namespace App\Middlewares;

use App\Services\JwtService;
use App\Services\Response;

class AuthMiddleware
{
    /**
     * Handles the JWT authentication.
     * 
     * @return void
     */
    public function handle()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::jsonResponse(["status" => HTTP_BAD_REQUEST, "message" => trans('missing_authrization')]);
        }
        $jwt = $matches[1];
        $decoded = JwtService::validateToken($jwt);
        if (!$decoded) {
            Response::jsonResponse(["status" => HTTP_UNAUTHORIZED, "message" => trans('invalid_token')]);
        } 
        $userData = [
            'id' => $decoded['id'],
            'email' => $decoded['email'],
            'role' => $decoded['role']
        ];
        $_SESSION['user_data'] = $userData;
        return true;
    }
}
