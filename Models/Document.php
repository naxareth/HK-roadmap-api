<?php

namespace Models;

use PDO;

class Document {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllDocuments() {
        $query = "SELECT * FROM document";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocumentsByEventId($eventId) {
        $query = "SELECT * FROM document WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function uploadDocument($eventId, $requirementId, $studentId, $filePath) {
        try {
            $this->db->beginTransaction();
    
            // Debug: Log the start of the transaction
            error_log("Starting transaction for uploading document.");
    
            // Insert into documents table
            $query = "INSERT INTO document (event_id, requirement_id, student_id, file_path, upload_at, status) 
                      VALUES (:event_id, :requirement_id, :student_id, :file_path, NOW(), 'pending')";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':file_path', $filePath);
    
            // Debug: Log before executing the first query
            error_log("Executing query for documents table: $query with eventId: $eventId, requirementId: $requirementId, studentId: $studentId, filePath: $filePath");
    
            $stmt->execute();
    
            // Debug: Log after executing the first query
            error_log("Document inserted into documents table.");
    
            // Insert into submissions table
            $query = "INSERT INTO submission (requirement_id, event_id, student_id, file_path, submission_date, status, approved_by) 
                      VALUES (:requirement_id, :event_id, :student_id, :file_path, NOW(), 'pending', 'not yet approved')";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':file_path', $filePath);
    
            // Debug: Log before executing the second query
            error_log("Executing query for submissions table: $query with requirementId: $requirementId, eventId: $eventId, studentId: $studentId, filePath: $filePath");
    
            $stmt->execute();
    
            // Debug: Log after executing the second query
            error_log("Document inserted into submissions table.");
    
            $this->db->commit();
    
            // Debug: Log successful transaction commit
            error_log("Transaction committed successfully.");
    
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
    
            // Debug: Log exception message
            error_log("Exception occurred: " . $e->getMessage());
    
            return false;
        }
    }
}
?>
