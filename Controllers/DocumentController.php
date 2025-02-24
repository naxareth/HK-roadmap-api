<?php

namespace Controllers;

use Models\Document;
use Controllers\AdminController;

require_once '../models/Document.php';
require_once 'AdminController.php';

class DocumentController {
    private $documentModel;
    private $adminController;

    public function __construct($db) {
        $this->documentModel = new Document($db);
        $this->adminController = new AdminController($db);
    }

    public function getAllDocuments() {
        $documents = $this->documentModel->getAllDocuments();
        echo json_encode($documents);
        return $documents;
    }

    public function getDocumentsByEventId($eventId) {
        return $this->documentModel->getDocumentsByEventId($eventId);
    }
    
    public function uploadDocument() {
        if (!isset($_POST['event_id'], $_POST['requirement_id'], $_POST['student_id'], $_FILES['document'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }
    
        $eventId = $_POST['event_id'];
        $requirementId = $_POST['requirement_id'];
        $studentId = $_POST['student_id'];
        $file = $_FILES['document'];
    
        // Check for file upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo json_encode(["message" => "File is too large."]);
                    error_log("Uploaded file size: " . $file['size']);
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
    
        if (!move_uploaded_file($file_tmp, $target_path)) {
            error_log("Failed to move uploaded file from $file_tmp to $target_path");
            echo json_encode(["message" => "Failed to move uploaded file."]);
            return;
        }
    
        // Insert document into database
        if (!$this->documentModel->uploadDocument($eventId, $requirementId, $studentId, $target_path)) {
            error_log("Failed to insert document into database with eventId: $eventId, requirementId: $requirementId, studentId: $studentId, path: $target_path"); 
            echo json_encode(["success" => false, "message" => "Failed to record document in database."]);
            return;
        }
    
        // Send a JSON response indicating success
        echo json_encode(["success" => true, "message" => "Document uploaded successfully."]);
    }
}
?>
