<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['lang'] ?? false) {

} else {
    $_SESSION['lang'] = 'it';
}

date_default_timezone_set('Europe/Rome');

spl_autoload_register(function ($class_name) {
    foreach (['classes', 'models'] as $path) {
        $filepath = __DIR__ . "/$path/$class_name.php";
        if (file_exists($filepath)) {
            include $filepath;
        }
    }
});
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['SERVER_PROTOCOL'] ?? false) {
    $server_protocol_arr = explode("/", $_SERVER['SERVER_PROTOCOL']);
    $server_protocol = ucfirst(strtolower($server_protocol_arr[0]));
    
    if (!defined('PROTOCOL')) define("PROTOCOL", sprintf("%s://%s", $server_protocol, $_SERVER['HTTP_HOST']));
}
