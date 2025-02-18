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

require_once __DIR__ . '/../Middleware/Middleware.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/LoggingMiddleware.php';

use Routes\Api;
use Middleware\AuthMiddleware;
use Middleware\LoggingMiddleware;


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');
$path = explode('/', $path);

// Initialize middleware
$authMiddleware = new AuthMiddleware();
$loggingMiddleware = new LoggingMiddleware();


// Require 'hk-roadmap' as the first path segment
if ($path[0] !== 'hk-roadmap') {
    http_response_code(404);
    echo json_encode(["message" => "Invalid base URL. Use /hk-roadmap/"]);
    return;
}
array_shift($path); 

$method = $_SERVER['REQUEST_METHOD'];

//dynamic base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host/hk-roadmap/";

$db = getDatabaseConnection();
$router = new Api($db);

// Register middleware
$router->use($loggingMiddleware);
$router->use($authMiddleware);


// Execute middleware and handle request
$request = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $path,
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input')
];

// Handle base URL request
if (empty($path[0])) {

    echo json_encode([
        "message" => "Welcome to the HK Roadmap API",
        "version" => "1.0",
        "base_url" => $base_url,
        "endpoints" => [
            "admin_register" => "POST /admin/register",
            "admin_login" => "POST /admin/login",
            "student_register" => "POST /student/register",
            "student_login" => "POST /student/login",
            "document_upload" => "POST /documents",
            "get_documents" => "GET /documents",
            "add_requirement" => "POST /requirements",
            "get_requirements" => "GET /requirements"
        ]
    ]);
    return;
}

$router->route($path, $method);

?>
