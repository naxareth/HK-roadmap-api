<?php
require_once '../models/Admin.php';
require_once '../models/Student.php';
require_once '../models/Document.php';

class DocumentController {
    private $db;
    private $documentModel;

    public function __construct($db) {
        $this->db = $db;
        $this->documentModel = new Document($db);
    }

    public function upload() {
        if (!isset($_POST['token']) || !isset($_POST['student_id']) || !isset($_FILES['document'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $token = $_POST['token'];
        $student_id = $_POST['student_id'];

        $adminModel = new Admin($this->db);
        $studentModel = new Student($this->db);

        // Validate tokens to ensure they are logged in
        $admin = $adminModel->validateToken($token);
        $student = $studentModel->validateToken($token);

        if (!$admin && !$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $student_id = $_POST['student_id'];
        $file_path = $_FILES['document']['tmp_name'];
        $file_name = $_FILES['document']['name'];
        $target_path = "uploads/" . basename($file_name);

        // Ensure uploads directory exists
        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }

        if (move_uploaded_file($file_path, $target_path) && $this->documentModel->upload($student_id, $target_path)) {
            echo json_encode(["message" => "Document uploaded successfully."]);
        } else {
            echo json_encode(["message" => "No file uploaded or file upload error."]);
        }
    }

    public function getDocuments() {
        if (!isset($_GET['token']) || !isset($_GET['student_id'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $token = $_GET['token'];
        $student_id = $_GET['student_id'];

        $adminModel = new Admin($this->db);
        $studentModel = new Student($this->db);

        $admin = $adminModel->validateToken($token);
        $student = $studentModel->validateToken($token);

        if (!$admin && !$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $student_id = $_GET['student_id'];
        $documents = $this->documentModel->getDocuments($student_id);
        echo json_encode($documents);
    }
}
?>