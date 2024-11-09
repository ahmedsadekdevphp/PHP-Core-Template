<?php

namespace App\Requests;
use Core\Validator;

class ProfileUpdateRequest
{
    public static function validate($data, $id)
    {
        $validator = new Validator();
        $rules = [
            'full_name' => 'required',
            'email' => 'required|email|unique:users',
        ];
        return $validator->validate($data, $rules, $id);
    }
}
