<?php

namespace Controllers;

use Models\Requirement;
use Controllers\AdminController;
use Controllers\StudentController;

require_once '../models/Requirement.php';
require_once 'AdminController.php';
require_once 'StudentController.php';

class RequirementController {
    private $requirementModel;
    private $adminController;
    private $studentController;

    public function __construct($db) {
        $this->requirementModel = new Requirement($db);
        $this->adminController = new AdminController($db);
        $this->studentController = new StudentController($db);
    }

    public function getRequirementsByEventId() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token) && !$this->studentController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }

        if (!isset($_GET['event_id'])) {
            echo json_encode(["message" => "Event ID is required."]);
            return;
        }

        $eventId = $_GET['event_id'];
        $requirements = $this->requirementModel->getRequirementsByEventId($eventId);

        if ($requirements) {
            echo json_encode($requirements);
        } else {
            echo json_encode(["message" => "No requirements found for this event."]);
        }
    }

    public function getRequirementById() {
        $headers = apache_request_headers(); 
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }
    
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token) && !$this->studentController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }
    
        if (!isset($_GET['requirement_id'])) {
            echo json_encode(["message" => "Requirement ID is required."]);
            return;
        }
    
        $requirementId = $_GET['requirement_id'];
        $requirement = $this->requirementModel->getRequirementById($requirementId);
    
        if ($requirement) {
            echo json_encode([$requirement]);
        } else {
            echo json_encode(["message" => "No requirement found for this ID."]);
        }
    }

    public function editRequirement() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return;
        }
    
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }
    
        $putData = json_decode(file_get_contents("php://input"), true);;
    
        if (!isset($putData['requirement_id'], $putData['requirement_name'], $putData['due_date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }
    
        $requirementId = $putData['requirement_id'];
        $requirementName = $putData['requirement_name'];
        $dueDate = $putData['due_date'];
    
        if ($this->requirementModel->updateRequirement($requirementId, $requirementName, $dueDate)) {
            echo json_encode(["message" => "Requirement updated successfully."]);
        } else {
            echo json_encode(["message" => "Failed to update requirement."]);
        }
    }


    public function getRequirements() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Unauthorized access."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token) && !$this->studentController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }

        $requirements = $this->requirementModel->getAllRequirements();
        echo json_encode($requirements);
    }

    public function createRequirement() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Unauthorized access."]);
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$this->adminController->validateToken($token)) {
            echo json_encode(["message" => "Invalid token."]);
            return;
        }
        if (!isset($_POST['event_id'], $_POST['requirement_name'], $_POST['due_date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $eventId = $_POST['event_id'];
        $requirementName = $_POST['requirement_name'];
        $dueDate = $_POST['due_date'];

        if ($this->requirementModel->createRequirement($eventId, $requirementName, $dueDate)) {
            echo json_encode(["message" => "Requirement created successfully."]);
        } else {
            echo json_encode(["message" => "Failed to create requirement."]);
        }
    }
}
?>
