<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Requests\ProfileUpdateRequest;
use App\Requests\ChangePasswordRequest;
use App\Services\Response;

class ProfileController extends Controller
{
    private $user;

    public function __construct()
    {
        parent::__construct();
        $this->user = new User();
    }

    public function Update()
    {
        $userID = auth('id');
        $request = $this->data;
        $validatedData = ProfileUpdateRequest::validate($request, $userID);
        $response = $this->user->updateInfo($userID, $validatedData);
        Response::jsonResponse($response);
    }

    public function changePassword()
    {
        $userID = auth('id');
        $validatedData = ChangePasswordRequest::validate($this->data, $userID);
        $user = $this->user->findUser($userID);
        $result = $this->user->checkPassword($user['password'], $validatedData['old_password']);
        if (!$result) {
            $response = [
                "status" => HTTP_UNAUTHORIZED,
                "message" => trans('wrong_password')
            ];
        } else {
            $response = $this->user->resetPassword($userID, $validatedData['password']);
        }
        Response::jsonResponse($response);
    }
}
