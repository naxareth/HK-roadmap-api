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
        $action = $_GET['action'] ?? null;

        // Parse JSON input for POST requests
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input) {
                $_POST = $input;
            }
        }

        // Handle the request
        switch ($method) {
            case 'POST':
                if ($action === 'admin_register') {
                    $this->adminController->register();
                } elseif ($action === 'admin_login') {
                    $this->adminController->login();
                } elseif ($action === 'upload_document') {
                    $this->documentController->upload();
                } elseif ($action === 'add_requirement') {
                    $this->requirementController->add();
                } elseif ($action === 'student_register') {
                    $this->studentController->register();
                } elseif ($action === 'student_login') {
                    $this->studentController->login();
                } elseif ($action === 'admin_logout') {
                    $this->adminController->logout();
                } elseif ($action === 'student_logout') {
                    $this->studentController->logout();
                } elseif ($action === 'send_otp') {
                    $this->studentController->sendOTP();
                } elseif ($action === 'verify_otp') {
                    $this->studentController->verifyOTP();
                } elseif ($action === 'change_password') {
                    $this->studentController->changePassword();
                } elseif ($action === 'request_otp') {
                    $this->adminController->requestOtp();
                } elseif ($action === 'admin_password_change') {
                    $this->adminController->requestOtp();
                } elseif ($action === 'admin_verify_otp') {
                    $this->adminController->verifyOTP();
                } elseif ($action === 'admin_change_password') {
                    $this->adminController->changePassword();
                } else {
                    echo json_encode(["message" => "Invalid action for POST request."]);
                }
                break;

            case 'GET':
                if ($action === 'get_documents') {
                    $this->documentController->getDocuments();
                } elseif ($action === 'get_requirements') {
                    $this->requirementController->getRequirements();
                } else {
                    echo json_encode(["message" => "Invalid action for GET request."]);
                }
                break;

            case 'DELETE':
                if ($action === 'delete_document') {
                    $this->documentController->deleteDocument();
                } else {
                    echo json_encode(["message" => "Invalid action for DELETE request."]);
                }
                break;

            default:
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }
}
?>