<?php
require_once '../config/database.php';
require_once '../controllers/AdminController.php';
require_once '../controllers/DocumentController.php';
require_once '../controllers/RequirementController.php';
require_once '../controllers/StudentController.php';

// Get database connection
$db = getDatabaseConnection();

// Initialize controllers
$adminController = new AdminController($db);
$documentController = new DocumentController($db);
$requirementController = new RequirementController($db);
$studentController = new StudentController($db);

$requestMethod = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($requestMethod) {
    case 'POST':
        if ($action === 'admin_register') {
            $adminController->register();
        } elseif ($action === 'admin_login') {
            $adminController->login();
        } elseif ($action === 'upload_document') {
            $documentController->upload();
        } elseif ($action === 'add_requirement') {
            $requirementController->add();
        } elseif ($action === 'student_register') {
            $studentController->register();
        } elseif ($action === 'student_login') {
            $studentController->login();
        }
        break;

    case 'GET':
        if ($action === 'get_documents') {
            $documentController->getDocuments();
        } elseif ($action === 'get_requirements') {
            $requirementController->getRequirements();
        }
        break;

    default:
        echo json_encode(["message" => "Method not allowed."]);
        break;
}
?>