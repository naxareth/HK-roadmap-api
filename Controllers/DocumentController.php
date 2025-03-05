<?php

namespace Controllers;

use Exception;
use Models\Document;
use Models\Student;
use Controllers\AdminController;

class DocumentController {
    private $documentModel;
    private $adminController;
    private $studentModel;

    public function __construct($db) {
        $this->documentModel = new Document($db);
        $this->studentModel = new Student($db);
        $this->adminController = new AdminController($db);
    }

    private function validateAuthHeader() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid authorization format"]);
            return null;
        }

        return substr($authHeader, 7);
    }

    public function getAllDocumentsByAdmin() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        $adminData = $this->adminController->validateToken($token);
        if ($adminData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or unauthorized access"]);
            return;
        }
    
        // Update overdue documents before getting the list
        $this->documentModel->updateOverdueDocuments();
    
        $documents = $this->documentModel->getAllDocuments();
        if ($documents) {
            echo json_encode([
                "documents" => $documents
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No documents found"]);
        }
    }
    
    public function getDocumentsByStudent() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }
    
        // Update overdue documents before getting the list
        $this->documentModel->updateOverdueDocuments();
    
        $documents = $this->documentModel->getDocumentsByStudentId($studentData['student_id']);
        if ($documents) {
            echo json_encode([
                "documents" => $documents
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No documents found"]);
        }
    }

    public function uploadDocument() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        if (!isset($_POST['event_id'], $_POST['requirement_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }
    
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }
    
        $eventId = $_POST['event_id'];
        $requirementId = $_POST['requirement_id'];
        $studentId = $studentData['student_id'];
    
        // Check if requirement is overdue
        if ($this->documentModel->isRequirementOverdue($eventId, $requirementId)) {
            http_response_code(400);
            echo json_encode(["message" => "Cannot upload document. Requirement due date has passed."]);
            return;
        }
    
        // Check if there's already a pending or approved document
        if ($this->documentModel->checkDocumentExists($eventId, $requirementId, $studentId)) {
            http_response_code(409);
            echo json_encode(["message" => "An approved or pending document already exists for this requirement"]);
            return;
        }
    
        $uploadedDocuments = [];
        $hasErrors = false;
    
        // Handle link upload
        if (isset($_POST['link_url'])) {
            $linkUrl = filter_var($_POST['link_url'], FILTER_VALIDATE_URL);
            if (!$linkUrl) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid URL provided"]);
                return;
            }
    
            $documentId = $this->documentModel->uploadLinkDocument(
                $eventId,
                $requirementId,
                $studentId,
                $linkUrl
            );
    
            if ($documentId) {
                $uploadedDocuments[] = [
                    "document_id" => $documentId,
                    "status" => "draft",
                    "type" => "link",
                    "url" => $linkUrl
                ];
            } else {
                $hasErrors = true;
            }
        }
    
        // Handle file uploads
        if (isset($_FILES['documents'])) {
            $files = $_FILES['documents'];
            // Ensure we have an array of files
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                    'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                    'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                    'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                    'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
                ];
    
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = $this->handleFileUpload($file, $eventId, $requirementId, $studentId);
                    if ($uploadResult['success']) {
                        $uploadedDocuments[] = [
                            "document_id" => $uploadResult['document_id'],
                            "status" => "draft",
                            "type" => "file"
                        ];
                    } else {
                        $hasErrors = true;
                    }
                } else {
                    $this->handleFileUploadError($file['error']);
                    $hasErrors = true;
                }
            }
        }
    
        if (empty($uploadedDocuments)) {
            http_response_code(400);
            echo json_encode(["message" => "No files or links were successfully uploaded"]);
            return;
        }
    
        echo json_encode([
            "message" => $hasErrors ? "Some uploads failed" : "All uploads successful",
            "documents" => $uploadedDocuments
        ]);
    }
    
    public function submitDocument() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }
    
        $input = json_decode(file_get_contents('php://input'), true);
        $documentId = $input['document_id'] ?? null;
    
        if (!$documentId) {
            http_response_code(400);
            echo json_encode(["message" => "Document ID is required"]);
            return;
        }
    
        $document = $this->documentModel->getDocumentById($documentId);
        if (!$document) {
            http_response_code(404);
            echo json_encode(["message" => "Document not found"]);
            return;
        }
    
        // Check if requirement is overdue
        if ($this->documentModel->isRequirementOverdue($document['event_id'], $document['requirement_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Cannot submit document. Requirement due date has passed."]);
            return;
        }
    
        if ($document['student_id'] != $studentData['student_id']) {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized access"]);
            return;
        }
    
        if ($document['status'] !== 'draft') {
            http_response_code(400);
            echo json_encode(["message" => "Document must be in draft status to submit"]);
            return;
        }
    
        if ($this->documentModel->submitDocument($documentId, $studentData['student_id'])) {
            echo json_encode([
                "document_id" => (int)$documentId,
                "status" => "pending"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to submit document"]);
        }
    }
    
    public function submitMultiple() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }
    
        $input = json_decode(file_get_contents('php://input'), true);
        $documentIds = $input['document_ids'] ?? null;
    
        if (!$documentIds || !is_array($documentIds)) {
            http_response_code(400);
            echo json_encode(["message" => "Document IDs array is required"]);
            return;
        }
    
        // Verify all documents and check due dates
        foreach ($documentIds as $documentId) {
            $document = $this->documentModel->getDocumentById($documentId);
            if (!$document || $document['student_id'] != $studentData['student_id']) {
                http_response_code(403);
                echo json_encode(["message" => "Unauthorized access to one or more documents"]);
                return;
            }
    
            if ($document['status'] !== 'draft') {
                http_response_code(400);
                echo json_encode(["message" => "All documents must be in draft status to submit"]);
                return;
            }
    
            // Check if requirement is overdue
            if ($this->documentModel->isRequirementOverdue($document['event_id'], $document['requirement_id'])) {
                http_response_code(400);
                echo json_encode([
                    "message" => "Cannot submit document(s). One or more requirements' due dates have passed.",
                    "document_id" => $documentId
                ]);
                return;
            }
        }
    
        if ($this->documentModel->submitMultipleDocuments($documentIds, $studentData['student_id'])) {
            echo json_encode([
                "message" => "All documents submitted successfully",
                "document_ids" => $documentIds,
                "status" => "pending"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to submit documents"]);
        }
    }
    

    public function unsubmitDocument() {
        $token = $this->validateAuthHeader();
        if (!$token) return;

        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $documentId = $input['document_id'] ?? null;

        if (!$documentId) {
            http_response_code(400);
            echo json_encode(["message" => "Document ID is required"]);
            return;
        }

        $document = $this->documentModel->getDocumentById($documentId);
        if (!$document) {
            http_response_code(404);
            echo json_encode(["message" => "Document not found"]);
            return;
        }

        if ($document['student_id'] != $studentData['student_id']) {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized access"]);
            return;
        }

        if ($document['status'] !== 'pending') {
            http_response_code(400);
            echo json_encode(["message" => "Document must be in pending status to unsubmit"]);
            return;
        }

        if ($this->documentModel->unsubmitDocument($documentId, $studentData['student_id'])) {
            echo json_encode([
                "document_id" => (int)$documentId,
                "status" => "draft"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to unsubmit document"]);
        }
    }

    public function unsubmitMultiple() {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }
    
        $input = json_decode(file_get_contents('php://input'), true);
        $documentIds = $input['document_ids'] ?? null;
    
        if (!$documentIds || !is_array($documentIds)) {
            http_response_code(400);
            echo json_encode(["message" => "Document IDs array is required"]);
            return;
        }
    
        // Verify all documents belong to the student and are in pending status
        foreach ($documentIds as $documentId) {
            $document = $this->documentModel->getDocumentById($documentId);
            if (!$document || $document['student_id'] != $studentData['student_id']) {
                http_response_code(403);
                echo json_encode(["message" => "Unauthorized access to one or more documents"]);
                return;
            }
            if ($document['status'] !== 'pending') {
                http_response_code(400);
                echo json_encode(["message" => "All documents must be in pending status to unsubmit"]);
                return;
            }
        }
    
        if ($this->documentModel->unsubmitMultipleDocuments($documentIds, $studentData['student_id'])) {
            echo json_encode([
                "message" => "All documents unsubmitted successfully",
                "document_ids" => $documentIds,
                "status" => "draft"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to unsubmit documents"]);
        }
    }

    public function getDocumentStatus($documentId = null) {
        $token = $this->validateAuthHeader();
        if (!$token) return;
    
        if (!$documentId) {
            http_response_code(400);
            echo json_encode(["message" => "Document ID is required"]);
            return;
        }
    
        $studentData = $this->studentModel->validateToken($token);
        $adminData = $this->adminController->validateToken($token);
    
        if ($studentData === false && $adminData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or unauthorized access"]);
            return;
        }
    
        // Check and update document status if overdue
        $this->documentModel->checkAndUpdateDocumentStatus($documentId);
    
        $document = $this->documentModel->getDocumentById($documentId);
        if (!$document) {
            http_response_code(404);
            echo json_encode(["message" => "Document not found"]);
            return;
        }
    
        // If it's a student token, verify document ownership
        if ($studentData !== false) {
            if ($document['student_id'] !== $studentData['student_id']) {
                http_response_code(403);
                echo json_encode(["message" => "Unauthorized access to this document"]);
                return;
            }
        }
        // If it's not a student token and not an admin token, deny access
        else if ($adminData === false) {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized access to this document"]);
            return;
        }
    
        $status = $this->documentModel->getDocumentStatus($documentId);
        if ($status) {
            echo json_encode([
                "document_id" => $status['document_id'],
                "status" => $status['status']
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error retrieving document status"]);
        }
    }

    
    public function deleteDocument() {
        $token = $this->validateAuthHeader();
        if (!$token) return;

        $studentData = $this->studentModel->validateToken($token);
        if ($studentData === false) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid token or student ID not found"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $documentId = $input['document_id'] ?? null;

        if (!$documentId) {
            http_response_code(400);
            echo json_encode(["message" => "Document ID is required"]);
            return;
        }

        $document = $this->documentModel->getDocumentById($documentId);
        
        if (!$document) {
            http_response_code(404);
            echo json_encode(["message" => "Document not found"]);
            return;
        }

        if ($document['student_id'] != $studentData['student_id']) {
            http_response_code(403);
            echo json_encode(["message" => "Unauthorized access"]);
            return;
        }

        if ($document['status'] !== 'draft') {
            http_response_code(400);
            echo json_encode(["message" => "Can only delete documents in draft status"]);
            return;
        }

        if ($this->documentModel->deleteDocument($documentId, $studentData['student_id'])) {
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            echo json_encode([
                "document_id" => $documentId,
                "status" => "deleted"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete document"]);
        }
    }

    private function handleFileUpload($file, $eventId, $requirementId, $studentId) {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return [
                'success' => false,
                'message' => "Invalid file type. Allowed types: PDF, JPEG, PNG"
            ];
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return [
                'success' => false,
                'message' => "File too large. Maximum size: 5MB"
            ];
        }

        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'message' => "Failed to save file"
            ];
        }

        $documentId = $this->documentModel->uploadDocument(
            $eventId,
            $requirementId,
            $studentId,
            $filePath
        );

        if ($documentId) {
            return [
                'success' => true,
                'document_id' => $documentId,
                'message' => "Document uploaded successfully"
            ];
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return [
                'success' => false,
                'message' => "Failed to save document in database"
            ];
        }
    }
    private function handleFileUploadError($errorCode) {
        $message = match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "File is too large",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension",
            default => "Unknown upload error"
        };
        
        http_response_code(400);
        echo json_encode(["message" => $message]);
    }
}
?>