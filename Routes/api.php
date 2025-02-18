<?php
namespace Routes;

require_once '../config/database.php';
use Controllers\AdminController;
use Controllers\DocumentController;
use Controllers\RequirementController;
use Controllers\StudentController;

require_once __DIR__ . '/../vendor/autoload.php';

class Api {
    private $adminController;
    private $documentController;
    private $requirementController;
    private $studentController;

    public function __construct($db) {
        $this->adminController = new AdminController($db);
        $this->documentController = new DocumentController($db);
        $this->requirementController = new RequirementController($db);
        $this->studentController = new StudentController($db);
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
                    return $this->documentController->upload();
                } elseif ($method === 'GET') {
                    return $this->documentController->getDocuments();
                }
                break;
            case 'requirements/add':


                if ($method === 'POST') {
                    return $this->requirementController->add();
                } elseif ($method === 'GET') {
                    return $this->requirementController->getRequirements();
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
