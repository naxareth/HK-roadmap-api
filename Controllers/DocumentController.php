<?php
require_once '../models/Admin.php';
require_once '../models/Document.php';

class DocumentController {
    private $documentModel;

    public function __construct($db) {
        $this->db = $db;
        $this->documentModel = new Document($db);
    }

    public function upload() {
        $token = $_POST['token'];
        $adminModel = new Admin($this->db);
        $studentModel = new Student($this->db);

        // Validate tokens to ensure they are logged in
        $admin = $adminModel->validateToken($token);
        $student = $studentModel->validateToken($token);

        if (!$admin && !$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        // Specify student id
        $student_id = null;
        if ($admin) {
            $student_id = $_POST['student_id'];
        } else {
            $student_id = $student['student_id'];
        }

        // Validate student_id
        if (empty($student_id)) {
            echo json_encode(["message" => "Student ID is required."]);
            return;
        }

        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $file_path = $_FILES['document']['tmp_name'];
            $file_name = $_FILES['document']['name'];
            $target_path = "uploads/" . basename($file_name);

            // Ensure uploads directory exists
            if (!is_dir("uploads")) {
                mkdir("uploads", 0755, true);
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($file_path, $target_path)) {
                // Save the document record in the database
                if ($this->documentModel->upload($student_id, $target_path)) {
                    echo json_encode(["message" => "Document uploaded successfully."]);
                } else {
                    echo json_encode(["message" => "Failed to save document record."]);
                }
            } else {
                echo json_encode(["message" => "Failed to move uploaded file."]);
            }
        } else {
            echo json_encode(["message" => "No file uploaded or file upload error."]);
        }
    }

    public function getDocuments() {
        $token = $_GET['token'];
        $adminModel = new Admin($this->db);
        $studentModel = new Student($this->db);

        $admin = $adminModel->validateToken($token);
        $student = $studentModel->validateToken($token);

        if (!$admin && !$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        if ($admin) {
            // Admin can specify a student_id to fetch documents for that student
            $student_id = $_GET['student_id'] ?? null;
            if ($student_id) {
                // Fetch documents for the specified student
                $documents = $this->documentModel->getDocuments($student_id);
            } else {
                // If no student_id is provided, fetch all documents
                $documents = $this->documentModel->getAllDocuments();
            }
        } else {
            // Student can only view their own documents
            $student_id = $student['student_id'];
            $documents = $this->documentModel->getDocuments($student_id);
        }

        echo json_encode($documents);
    }
}
?>