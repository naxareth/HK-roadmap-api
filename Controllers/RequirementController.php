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
        if (!$this->adminController->validateToken()) {
            echo json_encode(["message" => "Unauthorized access."]);
            return;
        }

        $admin = $this->adminController->validateToken();
        if (!$admin) {
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