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
include_once __DIR__ . '/../Models/Comment.php';
include_once __DIR__ . '/../Models/Notification.php';

include_once __DIR__ . '/../Controllers/AdminController.php';
include_once __DIR__ . '/../Controllers/StudentController.php';
include_once __DIR__ . '/../Controllers/DocumentController.php';
include_once __DIR__ . '/../Controllers/RequirementController.php';
include_once __DIR__ . '/../Controllers/EventController.php';
include_once __DIR__ . '/../Controllers/SubmissionController.php';
include_once __DIR__ . '/../Controllers/NotificationController.php';
include_once __DIR__ . '/../Controllers/MailController.php';
include_once __DIR__ . '/../Controllers/CommentController.php';

require_once __DIR__ . '/../Middleware/Middleware.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/LoggingMiddleware.php';

use Routes\Api;
use Middleware\AuthMiddleware;
use Middleware\LoggingMiddleware;

// Handle asset requests first
if (preg_match('/\.(html|css|js|jpg|png)$/', $_SERVER['REQUEST_URI'])) {
    $filePath = __DIR__ . str_replace('/hk-roadmap', '', $_SERVER['REQUEST_URI']);
    if (file_exists($filePath)) {
        $mimeTypes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'png' => 'image/png'
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
            // Admin endpoints
            "admin" => [
                "register" => "POST /admin/register",
                "login" => "POST /admin/login",
                "profile" => "GET /admin/profile",
                "logout" => "POST /admin/logout",
                "request_otp" => "POST /admin/request-otp",
                "verify_otp" => "POST /admin/verify-otp",
                "change_password" => "POST /admin/change-password"
            ],
            // Student endpoints
            "student" => [
                "email" => "GET /student/emails",
                "register" => "POST /student/register",
                "login" => "POST /student/login",
                "profile" => "GET /student/profile",
                "all_students" => "GET /student/all-students",
                "logout" => "POST /student/logout",
                "send_otp" => "POST /student/send-otp",
                "verify_otp" => "POST /student/verify-otp",
                "change_password" => "POST /student/change-password"
            ],
           // Document endpoints
            "documents" => [
                "upload" => [
                    "method" => "POST",
                    "url" => "/documents/upload",
                    "description" => "Upload file or link document",
                    "params" => [
                        "event_id" => "integer (required)",
                        "requirement_id" => "integer (required)",
                        "documents" => "file[] (optional)",
                        "link_url" => "string (optional)"
                    ]
                ],
                "submit_multiple" => [
                    "method" => "POST",
                    "url" => "/documents/submit-multiple",
                    "description" => "Submit multiple documents at once",
                    "body" => [
                        "document_ids" => "array of integers (required)"
                    ]
                ],

                "unsubmit_multiple" => [
                    "method" => "POST",
                    "url" => "/documents/unsubmit-multiple",
                    "description" => "Unsubmit multiple documents at once",
                    "body" => [
                        "document_ids" => "array of integers (required)"
                   ]
                ],
                
                "submit" => "POST /documents/submit",
                "unsubmit" => "POST /documents/unsubmit",
                "delete" => "DELETE /documents/delete",
                "get_admin" => "GET /documents/admin",
                "get_student" => "GET /documents/student",
                "get_status" => "GET /documents/status/{id}"
            ],

            // Comment endpoints
            "comments" => [
                "add" => [
                    "method" => "POST",
                    "url" => "/comments/add",
                    "description" => "Add a new comment to a conversation",
                    "body" => [
                        "requirement_id" => "integer (required)",
                        "student_id" => "integer (required)",
                        "body" => "string (required)"
                    ],
                    "authentication" => "Bearer token required"
                ],
                "get" => [
                    "method" => "GET",
                    "url" => "/comments/get",
                    "description" => "Get all comments in a conversation between admin and student",
                    "params" => [
                        "requirement_id" => "integer (required)",
                        "student_id" => "integer (required)"
                    ],
                    "response" => [
                        "success" => [
                            "status" => 200,
                            "data" => [
                                [
                                    "comment_id" => "integer",
                                    "requirement_id" => "integer",
                                    "student_id" => "integer",
                                    "user_type" => "string (admin|student)",
                                    "user_name" => "string",
                                    "body" => "string",
                                    "created_at" => "timestamp",
                                    "updated_at" => "timestamp",
                                    "is_owner" => "boolean"
                                ]
                            ]
                        ],
                        "error" => [
                            "status" => 400,
                            "message" => "string"
                        ]
                    ],
                    "authentication" => "Bearer token required"
                ],
                "update" => [
                    "method" => "PUT",
                    "url" => "/comments/update",
                    "description" => "Update an existing comment",
                    "body" => [
                        "comment_id" => "integer (required)",
                        "body" => "string (required)"
                    ],
                    "authentication" => "Bearer token required"
                ],
                "delete" => [
                    "method" => "DELETE",
                    "url" => "/comments/delete",
                    "description" => "Delete a comment",
                    "body" => [
                        "comment_id" => "integer (required)"
                    ],
                    "authentication" => "Bearer token required"
                ]
            ],
            // Requirement endpoints
            "requirements" => [
                "get" => "GET /requirements/get",
                "add" => "POST /requirements/add",
                "get_by_id" => "GET /requirements/add", 
                "update" => "PUT /requirements/edit",
                "delete" => "DELETE /requirements/delete"
            ],
            // Event endpoints
            "events" => [
                "add" => "POST /event/add",
                "get" => "GET /event/get",
                "get_by_id" => "GET /event/edit",
                "update" => "PUT /event/edit",
                "delete" => "DELETE /event/delete"
            ],
            // Submission endpoints
            "submissions" => [
                "update_status" => "PUT /submission/update",
                "get_all" => "GET /submission/update"
            ],
            // Notifs endpoints
            "notifications" => [
                "get" => "GET /notification/get",
                "update" => "PUT /notification/edit"
            ],
            // Email endpoint
            "mails" => [
                "send" => "POST /mail/send"
            ]
        ],
        "documentation" => [
            "description" => "API for HK Roadmap application",
            "authentication" => "Bearer token required for most endpoints",
            "errors" => [
                "400" => "Bad Request - Invalid input parameters",
                "401" => "Unauthorized - Authentication required",
                "403" => "Forbidden - Insufficient permissions",
                "404" => "Not Found - Resource not found",
                "500" => "Internal Server Error"
            ]
        ]
    ]);
    return;
}

// Handle CORS preflight requests
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    http_response_code(200);
    return;
}

// Set CORS headers for all responses
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Route the request
$router->route($path, $method);
?>