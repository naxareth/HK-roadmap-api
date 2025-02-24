<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Routes/api.php';

include_once __DIR__ . '/../Models/Admin.php';
include_once __DIR__ . '/../Models/Student.php';
include_once __DIR__ . '/../Models/Document.php';
include_once __DIR__ . '/../Models/Requirement.php';
include_once __DIR__ . '/../Models/Event.php';
include_once __DIR__ . '/../Models/Submission.php';

include_once __DIR__ . '/../Controllers/AdminController.php';
include_once __DIR__ . '/../Controllers/StudentController.php';
include_once __DIR__ . '/../Controllers/DocumentController.php';
include_once __DIR__ . '/../Controllers/RequirementController.php';
include_once __DIR__ . '/../Controllers/EventController.php';
include_once __DIR__ . '/../Controllers/SubmissionController.php';

require_once __DIR__ . '/../Middleware/Middleware.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/LoggingMiddleware.php';

use Routes\Api;
use Middleware\AuthMiddleware;
use Middleware\LoggingMiddleware;

// Handle asset requests first
// Handle HTML files separately
if (preg_match('/\.html$/', $_SERVER['REQUEST_URI'])) {
    $filePath = __DIR__ . str_replace('/hk-roadmap', '', $_SERVER['REQUEST_URI']);
    if (file_exists($filePath)) {
        header('Content-Type: text/html');
        readfile($filePath);
        return;
    }
}

// Handle other assets (CSS, JS, images)
// Handle CSS and JS files
if (preg_match('/\.(css|js)$/', $_SERVER['REQUEST_URI'])) {
    $filePath = __DIR__ . str_replace('/hk-roadmap', '', $_SERVER['REQUEST_URI']);
    if (file_exists($filePath)) {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript'
        ];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($mimeTypes[$extension] ?? 'text/plain'));
        readfile($filePath);
        return;
    }
}

// Handle image files (jpg, png)
if (preg_match('/\.(jpg|png)$/', $_SERVER['REQUEST_URI'])) {
    $filePath = __DIR__ . str_replace('/hk-roadmap', '', $_SERVER['REQUEST_URI']);

    if (file_exists($filePath)) {
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg'
        ];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($mimeTypes[$extension] ?? 'text/plain'));
        readfile($filePath);
        return;
    }

}


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
            "admin_otp" => "POST admin/request-otp",
            "admin_verify" => "POST /admin/verify",
            "admin_change" => "POST /admin/change-password",
            "admin_profile" => "GET /admin/profile",
            "student_register" => "POST /student/register",
            "student_login" => "POST /student/login",
            "student_profile" => "GET /student/profile",
            "send_otp" => "POST /student/send-otp",
            "verify_otp" => "POST /student/verify-otp",
            "student_change" => "POST /student/change-password",
            "document_upload" => "POST /documents/upload",
            "get_documents" => "GET /documents/upload",
            "add_requirement" => "POST /requirements/add",
            "get_requirements" => "GET /requirements",
            "event_upload" => "POST /event/add",
            "get_event" => "GET /event",
            "update_submission" => "PATCH /submission/update",
            "get_submissions" => "GET /submission/update"
        ]
    ]);
    return;
}

$router->route($path, $method);
?>
