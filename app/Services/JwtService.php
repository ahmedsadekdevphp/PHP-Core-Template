<?php

namespace App\Services;

use \Firebase\JWT\JWT;
use Exception;
use App\Models\User;

class JwtService
{
    public static function generateToken($user_data)
    {
        $token_version = random_int(1, 100);
        $payload = [
            "iss" => "airport",
            "iat" => time(),
            "exp" => time() + (60 * 60),
            'token_version' => $token_version,
            "data" => $user_data
        ];
        $user = new User();
        $user = $user->updateTokenVersion($user_data['id'], $token_version);

        return JWT::encode($payload, config('APP_SECRET_KEY'), 'HS256');
    }

    public static function validateToken($jwt)
    {
        try {
            $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key(config('APP_SECRET_KEY'), 'HS256'));
            //check version to validate it not loged out
            $userData = (array) $decoded->data;
            $user = new User();
            $user = $user->findUser($userData['id']);
            if ($user['token_version'] !== $decoded->token_version) {
                Response::jsonResponse(["status" => HTTP_UNAUTHORIZED, "message" => trans('invalid_token')]);
            }

            return $userData;
        } catch (Exception $e) {
            return false;
        }
    }
}
