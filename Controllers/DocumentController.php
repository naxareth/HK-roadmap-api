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

        // Specify student id
        $student_id = null;
        if ($admin) {
            } else {
            $student_id = $student['student_id'];
        }

        // Validate student_id
        if (empty($student_id)) {
            echo json_encode(["message" => "Student ID is required."]);
            return;
        }

        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document'];
            $file_name = basename($file['name']);
        $file_tmp = $file['tmp_name'];
        $target_dir = "uploads/";
            $target_path = $target_dir . $file_name;

            // Ensure uploads directory exists
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
        }

        // Validate file type and size
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(["message" => "Invalid file type. Only PDF, JPEG, and PNG files are allowed."]);
            return;
        }

        if ($file['size'] > $max_size) {
            echo json_encode(["message" => "File size exceeds the maximum limit of 5MB."]);
            return;
            }

            // Move the uploaded file to the target directory
            if (move_uploaded_file($file_tmp, $target_path)) {
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
        $documents = $this->documentModel->getDocuments($student_id);
        if ($documents) {
            echo json_encode($documents);
        } else {
            echo json_encode(["message" => "No documents found."]);
        }
    }
}
?>