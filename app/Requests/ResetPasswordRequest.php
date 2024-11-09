<?php

namespace App\Requests;
use Core\Validator;

class ResetPasswordRequest
{
    public static function validate($data)
    {
        $validator = new Validator();
        $rules = [
            'password'  => 'required|password',
            'confirm_password' => 'required|confirm_password'
        ];
        return $validator->validate($data, $rules);
    }
}
