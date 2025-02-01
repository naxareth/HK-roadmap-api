<?php
require_once '../models/Admin.php';
require_once '../models/Requirement.php';

class RequirementController {
    private $requirementModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->requirementModel = new Requirement($db);
    }

    public function add() {
        if (!isset($_POST['token']) || !isset($_POST['student_id']) || !isset($_POST['event_name']) || !isset($_POST['due_date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $token = $_POST['token'];
        $student_id = $_POST['student_id'];
        $event_name = $_POST['event_name'];
        $due_date = $_POST['due_date'];
        $shared = isset($_POST['shared']) ? (int)$_POST['shared'] : 0;

        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);

        if (!$admin) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        if ($this->requirementModel->add($student_id, $event_name, $due_date, $shared)) {
            echo json_encode(["message" => "Requirement added successfully."]);
        } else {
            echo json_encode(["message" => "Failed to add requirement."]);
        }
    }

    public function getRequirements() {
        if (!isset($_GET['token']) || !isset($_GET['student_id'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $token = $_GET['token'];
        $student_id = $_GET['student_id'];

        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);

        if (!$admin) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $requirements = $this->requirementModel->getRequirements($student_id);
        if ($requirements) {
            echo json_encode($requirements);
        } else {
            echo json_encode(["message" => "No requirements found."]);
        }
    }
}
?>