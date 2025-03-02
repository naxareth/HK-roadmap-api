<?php

namespace Controllers;

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

        if (!isset($_POST['event_id'], $_POST['requirement_id'], $_FILES['documents'])) {
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
        $file = $_FILES['documents'];

        if ($this->documentModel->checkDocumentExists($eventId, $requirementId, $studentId)) {
            http_response_code(409);
            echo json_encode(["message" => "A document for this requirement has already been submitted"]);
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->handleFileUploadError($file['error']);
            return;
        }

        $uploadResult = $this->handleFileUpload($file, $eventId, $requirementId, $studentId);
        if (!$uploadResult['success']) {
            http_response_code(400);
            echo json_encode(["message" => $uploadResult['message']]);
            return;
        }

        echo json_encode([
            "document_id" => $uploadResult['document_id'],
            "status" => "draft"
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

        $document = $this->documentModel->getDocumentById($documentId);

        if (!$document) {
            http_response_code(404);
            echo json_encode(["message" => "Document not found"]);
            return;
        }

        if ($studentData !== false && $document['student_id'] !== $studentData['student_id']) {
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