<?php
namespace Routes;

require_once '../config/database.php';
use Controllers\AdminController;
use Controllers\DocumentController;
use Controllers\EventController;
use Controllers\RequirementController;
use Controllers\StudentController;
use Controllers\SubmissionController;

require_once __DIR__ . '/../vendor/autoload.php';

class Api {
    private $adminController;
    private $documentController;
    private $requirementController;
    private $studentController;
    private $eventController;
    private $submissionController;
    private $middleware = [];

    public function __construct($db) {
        $this->adminController = new AdminController($db);
        $this->documentController = new DocumentController($db);
        $this->requirementController = new RequirementController($db);
        $this->studentController = new StudentController($db);
        $this->eventController = new EventController($db);
        $this->submissionController = new SubmissionController($db);
    }

    public function use($middleware) {
        $this->middleware[] = $middleware;
        return $this;
    }

    private function executeMiddleware($request) {
        $middlewareStack = $this->middleware;
        
        $next = function($request) {
            return $request;
        };
        
        while ($middleware = array_pop($middlewareStack)) {
            $next = function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }
        
        return $next($request);
    }

    public function route($path, $method) {
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $_POST = $input;
            }
        }

        $endpoint = implode('/', $path);
        
        switch ($endpoint) {
            // Admin Routes
            case 'admin/register':
                return $this->adminController->register();
            case 'admin/profile':
                return $this->adminController->getAdmin();
            case 'admin/login':
                return $this->adminController->login();
            case 'admin/logout':
                return $this->adminController->logout();
            case 'admin/request-otp':
                return $this->adminController->requestOtp();
            case 'admin/verify-otp':
                return $this->adminController->verifyOTP();
            case 'admin/change-password':
                return $this->adminController->changePassword();

            // Student Routes
            case 'student/register':
                return $this->studentController->register();
            case 'student/profile':
                return $this->studentController->getStudent();
            case 'student/login':
                return $this->studentController->login();
            case 'student/logout':
                return $this->studentController->logout();
            case 'student/send-otp':
                return $this->studentController->sendOTP();
            case 'student/verify-otp':
                return $this->studentController->verifyOTP();
            case 'student/change-password':
                return $this->studentController->changePassword();

            // Document Routes
            case 'documents/admin':
                if ($method === 'GET') {
                    return $this->documentController->getAllDocumentsByAdmin();
                }
                break;

            case 'documents/student':
                if ($method === 'GET') {
                    return $this->documentController->getDocumentsByStudent();
                }
                break;

            case 'documents/upload':
                if ($method === 'POST') {
                    return $this->documentController->uploadDocument();
                }
                break;


            case 'documents/submit':
                if ($method === 'POST') {
                    return $this->documentController->submitDocument();
                }
                break;

            case 'documents/unsubmit':
                if ($method === 'POST') {
                    return $this->documentController->unsubmitDocument();
                }
                break;

            case 'documents/delete':
                if ($method === 'DELETE') {
                    return $this->documentController->deleteDocument();
                }
                break;

            case (preg_match('/^documents\/status\/(\d+)$/', $endpoint, $matches) ? $endpoint : !$endpoint):
                if ($method === 'GET') {
                    return $this->documentController->getDocumentStatus($matches[1]);
                }
                break;

            // Requirement Routes
            case 'requirements/get':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirements();
                }
                break;

            case 'requirements/add':
                if ($method === 'POST') {
                    return $this->requirementController->createRequirement();
                } elseif ($method === 'GET') {
                    return $this->requirementController->getRequirementsByEventId();
                }
                break;

            case 'requirements/delete':
                if ($method === 'DELETE') {
                    return $this->requirementController->deleteRequirement();
                }
                break;
            
            case 'requirements/edit':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirementById();
                }
                elseif ($method === 'PUT'){
                    return $this->requirementController->editRequirement();
                }
                break;

            // Event Routes
            case 'event/add':
                if ($method === 'POST') {
                    return $this->eventController->createEvent();
                }
                break;

            case 'event/get':
                if ($method === 'GET') {
                    return $this->eventController->getEvent();
                }
                break;

            case "event/edit":
                if ($method === 'PUT') {
                    return $this->eventController->editEvent();
                } elseif ($method === 'GET') {
                    return $this->eventController->getEventById();
                }
                break;

            case 'event/delete':
                if ($method === 'DELETE') {
                    return $this->eventController->deleteEvent();
                }
                break;

            // Submission Routes
            case 'submission/update':
                if ($method === 'PATCH') {
                    return $this->submissionController->updateSubmissionStatus();
                } elseif ($method === 'GET') {
                    return $this->submissionController->getAllSubmissions();
                }
                break;

            default:
                http_response_code(404);
                return json_encode([
                    "message" => "Endpoint not found",
                    "endpoint" => $endpoint,
                    "method" => $method,
                    "path" => $path
                ]);
        }
    }
}
?>