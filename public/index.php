<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1); 
ini_set('error_log', dirname(__DIR__). '/logs/error.log'); 
require dirname(__DIR__).'/vendor/autoload.php'; 
require_once dirname(__DIR__).'/core/ResponseCode.php'; 
require_once dirname(__DIR__).'/routes/api.php';
require_once  dirname(__DIR__).'/app/services/Helpers.php';



$router->dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
