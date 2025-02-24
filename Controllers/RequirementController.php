<?php

namespace Controllers;

use Models\Requirement;
use Controllers\AdminController;

require_once '../models/Requirement.php';
require_once 'AdminController.php';

class RequirementController {
    private $requirementModel;
    private $adminController;

    public function __construct($db) {
        $this->requirementModel = new Requirement($db);
        $this->adminController = new AdminController($db);
    }

    public function getRequirementsByEventId() {
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


    public function getRequirements() {
        return $this->requirementModel->getAllRequirements();
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


        // Removed redundant token validation

        // The rest of the method remains unchanged


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
