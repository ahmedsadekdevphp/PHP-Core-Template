<?php

namespace App\Requests;
use Core\Validator;

class ChangeRoleRequest
{
    public static function validate($data)
    {
        $validator = new Validator();
        $rules = [
            'role' => 'required'
                ];
        return $validator->validate($data, $rules);
    }
}
