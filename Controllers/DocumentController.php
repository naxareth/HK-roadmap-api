<?php
namespace Controllers;

use Models\Document;
use Models\Student;

require_once '../models/Admin.php';
require_once '../models/Document.php';
require_once '../models/Student.php';

class DocumentController {
    private $documentModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->documentModel = new Document($db);
    }

    public function upload() {
        $headers = getallheaders();
        if (!isset($headers['Authorization']) || !isset($_FILES['document'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid authorization header."]);
            return;
        }

        $token = substr($authHeader, 7);

        $studentModel = new Student($this->db);
        $student = $studentModel->validateToken($token);

        if (!$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $student_id = $student['student_id'];
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

        if (move_uploaded_file($file_tmp, $target_path) && $this->documentModel->upload($student_id, $target_path)) {
            echo json_encode(["message" => "Document uploaded successfully."]);
        } else {
            echo json_encode(["message" => "Failed to upload document."]);
        }
    }

    public function getDocuments() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Missing authorization header."]);
            return;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid authorization header."]);
            return;
        }

        $token = substr($authHeader, 7);

        $studentModel = new Student($this->db);
        $student = $studentModel->validateToken($token);

        if (!$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        $student_id = $student['student_id'];

        $documents = $this->documentModel->getDocuments($student_id);
        if ($documents) {
            echo json_encode($documents);
        } else {
            echo json_encode(["message" => "No documents found."]);
        }
    }

    public function deleteDocument() {
        $headers = getallheaders();
        if (!isset($headers['Authorization']) || !isset($_POST['document_id'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid authorization header."]);
            return;
        }

        $token = substr($authHeader, 7);
        $document_id = $_POST['document_id'];

        $studentModel = new Student($this->db);
        $student = $studentModel->validateToken($token);

        if (!$student) {
            echo json_encode(["message" => "Unauthorized access. Invalid token."]);
            return;
        }

        // Get document info
        $document = $this->documentModel->getDocumentById($document_id);
        if (!$document || $document['student_id'] !== $student['student_id']) {
            echo json_encode(["message" => "Document not found or unauthorized."]);
            return;
        }

        // Delete file from storage
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }

        // Delete from database
        if ($this->documentModel->delete($document_id)) {
            echo json_encode(["message" => "Document deleted successfully."]);
        } else {
            echo json_encode(["message" => "Failed to delete document."]);
        }
    }
}
?>
