<?php

namespace App\Requests;
use Core\Validator;
class AuthRequest
{
    public static function validate($data)
    {
        $validator = new Validator();
        $rules = [
            'email' => 'required|email',
            'password'  => 'required'
        ];
        return $validator->validate($data, $rules);
    }
}
