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

        // Parse JSON input for POST/PUT requests
        if (in_array($method, ['POST', 'PUT'])) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $_POST = $input;
            }
        }

        // Handle the request based on path and method
        $endpoint = implode('/', $path);
        
        switch ($endpoint) {
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
            case 'documents/upload':
                if ($method === 'POST') {
                    return $this->documentController->uploadDocument();
                } elseif ($method === 'GET') {
                    return $this->documentController->getAllDocuments();
                }
                break;
            case 'requirements/get':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirements();
                }
            case 'requirements/add':

                if ($method === 'POST') {
                    return $this->requirementController->createRequirement();
                } elseif ($method === 'GET') {
                    return $this->requirementController->getRequirementById();
                }
                break;
            case 'requirements/get':
                if ($method === 'GET') {
                    return $this->requirementController->getRequirementsByEventId();
                }
                break;
            case "requirements/edit":
                if ($method === 'PUT') {
                    return $this->requirementController->editRequirement();
                }
                break;
            case 'event/get':
                if ($method === 'POST') {
                    return $this->eventController->createEvent();
                } elseif ($method === 'GET') {
                    return $this->eventController->getEvents();
                }
                break;
            case "event/edit":
                if ($method === 'PUT') {
                    return $this->eventController->editEvent();
                } elseif ($method == 'GET') {
                    return $this->eventController->getEventById();
                }
                break;
            case 'submission/update':
                if ($method === 'PATCH') {
                    return $this->submissionController->updateSubmissionStatus();
                } elseif ($method === 'GET') {
                    return $this->submissionController->getAllSubmissions();
                }
                break;
            default:
                http_response_code(404);
                return json_encode(["message" => "Endpoint not found"]);
                break;
        }



    }
}
?>
