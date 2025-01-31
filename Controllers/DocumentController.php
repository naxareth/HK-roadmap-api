<?php
require_once '../models/Admin.php';
require_once '../models/Document.php';

class DocumentController {
    private $documentModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->documentModel = new Document($db);
    }

    public function upload() {
        $token = $_POST['token'];
        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);

        if (!$admin) {
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
            echo json_encode(["message" => "Failed to upload document."]);
        }
    }

    public function getDocuments() {
        $token = $_GET['token'];
        $adminModel = new Admin($this->db);
        $admin = $adminModel->validateToken($token);

        if (!$admin) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $student_id = $_GET['student_id'];
        $documents = $this->documentModel->getDocuments($student_id);
        echo json_encode($documents);
    }
}
?>