<?php

namespace Controllers;

use Models\Document;
use Models\Student; // Include the Student model
use Controllers\AdminController;

require_once '../models/Document.php';
require_once '../models/Student.php'; // Require the Student model
require_once 'AdminController.php';

class DocumentController {
    private $documentModel;
    private $adminController;
    private $studentModel; // Add a property for the Student model

    public function __construct($db) {
        $this->documentModel = new Document($db);
        $this->studentModel = new Student($db); // Initialize the Student model
        $this->adminController = new AdminController($db);
    }

    public function getAllDocumentsByAdmin() {
        // Extract admin_id from the bearer token
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }
        
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }
        
        $token = substr($authHeader, 7);
        // Validate the token and check if it's an admin token
        $adminData = $this->adminController->validateToken($token);
        if ($adminData === false) {
            echo json_encode(["message" => "Invalid token or admin ID not found."]);
            return;
        }

        $documents = $this->documentModel->getAllDocuments();
        echo json_encode($documents);
        return $documents;
    }

    public function getDocumentsByStudent() {
        // Extract student_id from the bearer token
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }
        
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }
        
        $token = substr($authHeader, 7);
        // Validate the token and get the student_id
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            echo json_encode(["message" => "Invalid token or student ID not found."]);
            return;
        }
        $studentId = $studentData['student_id']; // Retrieve student ID from validated token

        $documents = $this->documentModel->getDocumentsByStudentId($studentId); // Fetch documents for the student
        echo json_encode($documents);
        return $documents;
    }

    public function getDocumentById($documentId) {
        return $this->documentModel->getDocumentById($documentId);
    }

    public function getDocumentsByEventId($eventId) {
        return $this->documentModel->getDocumentsByEventId($eventId);
    }
    
    public function uploadDocument() {
        if (!isset($_POST['event_id'], $_POST['requirement_id'], $_FILES['documents']) || !is_array($_FILES['documents']['name'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }
    
        $eventId = $_POST['event_id'];
        $requirementId = $_POST['requirement_id'];
        // Extract student_id from the bearer token
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }
        
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }
        
        $token = substr($authHeader, 7);
        // Validate the token and get the student_id
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            echo json_encode(["message" => "Invalid token or student ID not found."]);
            return;
        }
        $studentId = $studentData['student_id']; // Retrieve student ID from validated token

        $files = $_FILES['documents'];
    
        // Check for file upload errors
        foreach ($files['name'] as $key => $file_name) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) {
                switch ($files['error'][$key]) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        echo json_encode(["message" => "File is too large."]);
                        error_log("Uploaded file size: " . $files['size'][$key]);
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        echo json_encode(["message" => "File was only partially uploaded."]);
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        echo json_encode(["message" => "No file was uploaded."]);
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        echo json_encode(["message" => "Missing a temporary folder."]);
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        echo json_encode(["message" => "Failed to write file to disk."]);
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        echo json_encode(["message" => "File upload stopped by extension."]);
                        break;
                    default:
                        echo json_encode(["message" => "Unknown upload error."]);
                        break;
                }
                return;
            }
            $file_tmp = $files['tmp_name'][$key];
            $target_path = "uploads/" . basename($file_name);
            // Ensure uploads directory exists
            if (!is_dir("uploads/")) {
                mkdir("uploads/", 0755, true);
            }
            // Move the uploaded file
            if (!move_uploaded_file($file_tmp, $target_path)) {
                error_log("Failed to move uploaded file from $file_tmp to $target_path");
                echo json_encode(["message" => "Failed to move uploaded file."]);
                return;
            }
            // Insert document into database
            if (!$this->documentModel->uploadDocument($eventId, $requirementId, $studentId, $target_path)) {
                error_log("Failed to insert document into database with eventId: $eventId, requirementId: $requirementId, studentId: $studentId, path: $target_path"); 
                echo json_encode(["message" => "Failed to record document in database."]);
                return;
            }
        }
    }

    public function deleteDocument() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }

        $token = substr($authHeader, 7);
        // Validate the token and get the student_id
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            echo json_encode(["message" => "Invalid token or student ID not found."]);
            return;
        }
        $studentId = $studentData['student_id'];

        // Get parameters from the request body
        $input = json_decode(file_get_contents('php://input'), true);
        $eventId = $input['event_id'] ?? null;
        $requirementId = $input['requirement_id'] ?? null;
        $documentId = $input['document_id'] ?? null; // Changed to singular

        if (empty($eventId) || empty($requirementId) || empty($documentId)) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        // Logic to delete the document
        $this->documentModel->deleteDocument($documentId);

        echo json_encode(["message" => "Document deleted successfully."]);
    }

    private function decodeTokenAndGetStudentId($token) {
        // Check if the token is valid and structured correctly
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null; // Invalid token structure
        }
        
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-','+', $parts[1]))), true);
        return $decoded['student_id'] ?? null; // Adjust according to your token structure
    }
}
?>
