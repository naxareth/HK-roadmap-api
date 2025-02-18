<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Routes/api.php';

use Routes\Api;


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$path = explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

$db = getDatabaseConnection();
$router = new Api($db);

$router->route($path, $method);
?>
