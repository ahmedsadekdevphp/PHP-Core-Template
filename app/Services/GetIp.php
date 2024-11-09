<?php
 namespace App\Services;

class GetIp
{
   public static function getClientIp()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // Check if IP is from shared internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if IP is passed from a proxy
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            // Get the remote IP address
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
