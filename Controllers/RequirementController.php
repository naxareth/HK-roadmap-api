<?php
require_once '../models/Admin.php';
require_once '../models/Requirement.php';
require_once '../models/Student.php'; // Include the Student model

class RequirementController {
    private $requirementModel;
    private $studentModel; // Add a property for the Student model
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->requirementModel = new Requirement($db);
        $this->studentModel = new Student($db); // Initialize the Student model
    }

    public function add() {
        // Get Authorization header
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }
        
        // Extract Bearer token
        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }
        $token = $matches[1];

        if (!isset($_POST['student_id']) || !isset($_POST['event_name']) || !isset($_POST['due_date']) || !isset($_POST['event_date'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $student_id = $_POST['student_id'];
        $event_name = $_POST['event_name'];
        $due_date = $_POST['due_date'];
        $event_date = $_POST['event_date'];
        $shared = isset($_POST['shared']) ? (int)$_POST['shared'] : 0; // 0 for specific, 1 for global
        
        // Handle file upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/requirements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $imagePath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                echo json_encode(["message" => "Failed to upload image."]);
                return;
            }
        }

    
        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);
    
        if (!$admin) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }
    
        // Check if the student ID exists regardless of whether the requirement is shared or not
        if (!$this->studentModel->studentExists($student_id)) {
            echo json_encode(["message" => "Student ID does not exist."]);
            return;
        }
    
        // Add the requirement
        if ($this->requirementModel->add($student_id, $event_name, $event_date, $due_date, $shared, $imagePath)) {

            echo json_encode(["message" => "Requirement added successfully."]);
        } else {
            echo json_encode(["message" => "Failed to add requirement."]);
        }
    }

    public function getRequirements() {
        if (!isset($_GET['token'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $token = $_GET['token'];
        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);

        if ($admin) {
            // Admin is authenticated, return all requirements
            $requirements = $this->requirementModel->getAllRequirements();
            if ($requirements) {
                echo json_encode($requirements);
            } else {
                echo json_encode(["message" => "No requirements found."]);
            }
        } else {
            // Not an admin, check for student ID
            if (!isset($_GET['student_id'])) {
                echo json_encode(["message" => "Missing student ID."]);
                return;
            }

            $student_id = $_GET['student_id'];

            // Validate student token
            if (!$this->studentModel->studentExists($student_id)) {
                echo json_encode(["message" => "Student ID does not exist."]);
                return;
            }

            // Return requirements for the specific student
            $requirements = $this->requirementModel->getRequirements($student_id);
            if ($requirements) {
                echo json_encode($requirements);
            } else {
                echo json_encode(["message" => "No requirements found for this student."]);
            }
        }
    }
}
?>