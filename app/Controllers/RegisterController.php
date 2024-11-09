<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Requests\RegisterRequest;
use App\Services\NotifyUser;
use App\Services\Response;

class RegisterController extends Controller
{
    /**
     * Registers a new user.
     *
     * This method handles the registration process by validating the incoming
     * request data, creating a new user, and returning a JSON response
     * indicating the success or failure of the registration.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response with the registration status and message.
     */

    public function register()
    {
        $validatedData = RegisterRequest::validateRegistration($this->data);
        $user = new User();
        $response = $user->create($validatedData);
        NotifyUser::sendWelcomeEmail($validatedData['email'], $validatedData['full_name']);
        Response::jsonResponse($response);
    }
}
