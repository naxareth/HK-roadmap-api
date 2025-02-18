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

// Require 'hk-roadmap' as the first path segment
if ($path[0] !== 'hk-roadmap') {
    http_response_code(404);
    echo json_encode(["message" => "Invalid base URL. Use /hk-roadmap/"]);
    return;
}
array_shift($path); // Remove 'hk-roadmap' prefix for internal routing

$method = $_SERVER['REQUEST_METHOD'];

// Get dynamic base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host/hk-roadmap/";

$db = getDatabaseConnection();
$router = new Api($db);

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
