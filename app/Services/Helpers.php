<?php
use Core\Localization;
use Config\Config;
use App\services\Response;
function trans($key) {
    return Localization::translate($key);
}

function config($key) {
    return Config::get($key); 
}

function auth($key){
   if(!isset($_SESSION['user_data'][$key])){
    return Response::jsonResponse([
        "status" => HTTP_PAGE_EXPIRED,
        "message" => trans("session_expired")
    ]);
   }
   return  $_SESSION['user_data'][$key];
}