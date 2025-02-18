<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Routes/api.php';


include_once __DIR__ . '/../Models/Admin.php';
include_once __DIR__ . '/../Models/Student.php';
include_once __DIR__ . '/../Models/Document.php';
include_once __DIR__ . '/../Models/Requirement.php';


include_once __DIR__ . '/../Controllers/AdminController.php';
include_once __DIR__ . '/../Controllers/StudentController.php';
include_once __DIR__ . '/../Controllers/DocumentController.php';
include_once __DIR__ . '/../Controllers/RequirementController.php';


use Routes\Api;


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$path = explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

$db = getDatabaseConnection();
$router = new Api($db);

$router->route($path, $method);
?>
