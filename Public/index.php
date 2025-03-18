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
include_once __DIR__ . '/../Models/Profile.php';

include_once __DIR__ . '/../Controllers/AdminController.php';
include_once __DIR__ . '/../Controllers/StudentController.php';
include_once __DIR__ . '/../Controllers/DocumentController.php';
include_once __DIR__ . '/../Controllers/RequirementController.php';
include_once __DIR__ . '/../Controllers/EventController.php';
include_once __DIR__ . '/../Controllers/SubmissionController.php';
include_once __DIR__ . '/../Controllers/NotificationController.php';
include_once __DIR__ . '/../Controllers/MailController.php';
include_once __DIR__ . '/../Controllers/CommentController.php';
include_once __DIR__ . '/../Controllers/ProfileController.php';
include_once __DIR__ . '/../Controllers/ProfileController.php';

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
                "all_students" => "GET /student/all-students",
                "logout" => "POST /student/logout",
                "send_otp" => "POST /student/send-otp",
                "verify_otp" => "POST /student/verify-otp",
                "change_password" => "POST /student/change-password"
            ],

            // Profile endpoints

            "profile" => [
                "get" => [
                    "method" => "GET",
                    "url" => "/profile/get",
                    "description" => "Get user profile",
                    "authentication" => "Bearer token required",
                    "response" => [
                        "success" => [
                            "status" => 200,
                            "data" => [
                                "user_id" => "integer",
                                "user_type" => "string (admin|student)",
                                "name" => "string",
                                "email" => "string",
                                "department" => "string",
                                "department_others" => "string",
                                "contact_number" => "string",
                                "profile_picture_url" => "string",
                                // Student specific fields
                                "student_number" => "string (if student)",
                                "college_program" => "string (if student)",
                                "year_level" => "string (if student)",
                                "scholarship_type" => "string (if student)",
                                // Admin specific fields
                                "position" => "string (if admin)"
                            ]
                        ]
                    ]
                ],
                "update" => [
                    "method" => "POST",
                    "url" => "/profile/update",
                    "description" => "Update user profile",
                    "authentication" => "Bearer token required",
                    "body" => [
                        "name" => "string (optional)",
                        "email" => "string (optional)",
                        "department" => "string (optional)",
                        "department_others" => "string (required if department is 'Others')",
                        "contact_number" => "string (optional)",
                        "profile_picture" => "file (optional)",
                        // Student specific fields
                        "student_number" => "string (if student)",
                        "college_program" => "string (if student)",
                        "year_level" => "string (if student)",
                        "scholarship_type" => "string (if student)",
                        // Admin specific fields
                        "position" => "string (if admin)"
                    ]
                ],
                "departments" => [
                    "method" => "GET",
                    "url" => "/profile/departments",
                    "description" => "Get list of departments",
                    "authentication" => "Not required"
                ],
                "programs" => [
                    "method" => "GET",
                    "url" => "/profile/programs",
                    "description" => "Get list of college programs",
                    "authentication" => "Not required"
                ]
            ],
            "staff" => [
                "register" => "POST /staff/register",
                "login" => "POST /staff/login",
                "profile" => "GET /staff/profile",
                "logout" => "POST /staff/logout",
                "send_otp" => "POST /staff/send-otp",
                "verify_otp" => "POST /staff/verify-otp",
                "change_password" => "POST /staff/change-password"
            ],

            // Profile endpoints

            "profile" => [
                "get" => [
                    "method" => "GET",
                    "url" => "/profile/get",
                    "description" => "Get user profile",
                    "authentication" => "Bearer token required",
                    "response" => [
                        "success" => [
                            "status" => 200,
                            "data" => [
                                "user_id" => "integer",
                                "user_type" => "string (admin|student|staff)",
                                "name" => "string",
                                "email" => "string",
                                "department" => "string",
                                "department_others" => "string",
                                "contact_number" => "string",
                                "profile_picture_url" => "string",
                                // Student specific fields
                                "student_number" => "string (if student)",
                                "college_program" => "string (if student)",
                                "year_level" => "string (if student)",
                                "scholarship_type" => "string (if student)",
                                // Admin specific fields
                                "position" => "string (if admin)"
                            ]
                        ]
                    ]
                ],
                "update" => [
                    "method" => "POST",
                    "url" => "/profile/update",
                    "description" => "Update user profile",
                    "authentication" => "Bearer token required",
                    "body" => [
                        "name" => "string (optional)",
                        "email" => "string (optional)",
                        "department" => "string (optional)",
                        "department_others" => "string (required if department is 'Others')",
                        "contact_number" => "string (optional)",
                        "profile_picture" => "file (optional)",
                        // Student specific fields
                        "student_number" => "string (if student)",
                        "college_program" => "string (if student)",
                        "year_level" => "string (if student)",
                        "scholarship_type" => "string (if student)",
                        // Admin specific fields
                        "position" => "string (if admin)"
                    ]
                ],
                "departments" => [
                    "method" => "GET",
                    "url" => "/profile/departments",
                    "description" => "Get list of departments",
                    "authentication" => "Not required"
                ],
                "programs" => [
                    "method" => "GET",
                    "url" => "/profile/programs",
                    "description" => "Get list of college programs",
                    "authentication" => "Not required"
                ],
                "all" => [
                    "method" => "GET",
                    "url" => "/profile/all",
                    "description" => "Get multiple user profile on a type",
                    "authentication" => "Bearer token required",
                    "response" => [
                        "success" => [
                            "status" => 200,
                            "data" => [
                                "user_id" => "integer",
                                "user_type" => "string (admin|student|staff)",
                                "name" => "string",
                                "email" => "string",
                                "department" => "string",
                                "department_others" => "string",
                                "contact_number" => "string",
                                "profile_picture_url" => "string",
                                // Student specific fields
                                "student_number" => "string (if student|staff)",
                                "college_program" => "string (if student|staff)",
                                "year_level" => "string (if student|staff)",
                                "scholarship_type" => "string (if student|staff)",
                                // Admin specific fields
                                "position" => "string (if admin|staff)"
                            ]
                        ]
                    ]
                ],
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
                "get_status" => "GET /documents/status/{id}",
                "get_staff" => "GET /documents/staff"
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
                "id" => [
                    "method" => "GET",
                    "url" => "/comments/id",
                    "description" => "Get comment by id",
                    "params" => [
                        "comment_id" => "integer (required)",
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
                "admin" => [
                    "method" => "GET",
                    "url" => "/comments/admin",
                    "description" => "Get all comments in a requirement for admin",
                    "params" => [
                        "requirement_id" => "integer (required)",
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
                "update_admin" => [
                    "method" => "PUT",
                    "url" => "/comments/update-admin",
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
                ],
                "all" => [
                    "method" => "GET",
                    "url" => "/comments/all",
                    "description" => "Specifically made for staff",
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
                "get_all" => "GET /submission/update",
                "sub_id" => "GET /submission/detail"
            ],
            // Notifs endpoints
            "notifications" => [
                "get" => "GET /notification/get",
                "update" => "PUT /notification/edit",
                "mark" => "PUT /notification/mark",
                "count" => "GET /notification/count",
                "student" => "GET /notification/student",
                "mark_student" => "PUT /notification/mark-student",
                "edit_student" => "PUT /notification/edit-student",
                "count_student" => "GET /notification/count-student",
                "staff" => "GET /notification/staff",
                "edit_staff" => "PUT /notification/edit-staff",
                "staff_unread" => "GET /notification/staff/unread",
                "mark_staff" => "PUT /notification/staff/mark-all"
            ],
            // Email endpoint
            "mails" => [
                "send" => "POST /mail/send"
            ],
            //announcement endpoint
            "announcements" => [
                "add" => "POST /announcements/add",
                "update" => "PUT /announcements/update",
                "delete" => "DELETE /announcements/delete",
                "get" => "GET /announcements/get"
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