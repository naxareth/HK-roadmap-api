<?php

namespace Models;

use PDO;

class Document {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllDocuments() {
        try {
            $query = "SELECT 
                        d.*,
                        e.event_name as event_title,
                        r.requirement_name as requirement_title,
                        r.due_date as requirement_due_date
                     FROM document d
                     LEFT JOIN event e ON d.event_id = e.event_id
                     LEFT JOIN requirement r ON d.requirement_id = r.requirement_id
                     ORDER BY d.upload_at DESC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function uploadDocument($eventId, $requirementId, $studentId, $filePath) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO document (
                event_id, 
                requirement_id, 
                student_id, 
                file_path, 
                upload_at, 
                status,
                is_submitted,
                submitted_at
            ) VALUES (
                :event_id, 
                :requirement_id, 
                :student_id, 
                :file_path, 
                NOW(), 
                'draft',
                FALSE,
                NULL
            )";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':file_path', $filePath);
            
            $stmt->execute();
            $documentId = $this->db->lastInsertId();

            $this->db->commit();
            return $documentId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function submitDocument($documentId, $studentId) {
        try {
            $this->db->beginTransaction();
    
            // First get the document details
            $query = "SELECT event_id, requirement_id, file_path FROM document 
                     WHERE document_id = :document_id 
                     AND student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document) {
                $this->db->rollBack();
                return false;
            }
    
            // Update document status
            $updateQuery = "UPDATE document 
                           SET status = 'pending', 
                               is_submitted = TRUE,
                               submitted_at = NOW()
                           WHERE document_id = :document_id 
                           AND student_id = :student_id
                           AND status = 'draft'";
        
            $stmt = $this->db->prepare($updateQuery);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            
            $result = $stmt->execute();
    
            if (!$result || $stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }
    
            // Create submission record
            $submissionQuery = "INSERT INTO submission (
                student_id,
                event_id,
                requirement_id,
                file_path,
                submission_date,
                status,
                approved_by
            ) VALUES (
                :student_id,
                :event_id,
                :requirement_id,
                :file_path,
                NOW(),
                'pending',
                'not yet approved'
            )";
    
            $stmt = $this->db->prepare($submissionQuery);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':event_id', $document['event_id']);
            $stmt->bindParam(':requirement_id', $document['requirement_id']);
            $stmt->bindParam(':file_path', $document['file_path']);
            
            $result = $stmt->execute();
    
            if (!$result) {
                $this->db->rollBack();
                return false;
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    public function unsubmitDocument($documentId, $studentId) {
        try {
            $this->db->beginTransaction();
    
            // First get the document details
            $query = "SELECT event_id, requirement_id FROM document 
                     WHERE document_id = :document_id 
                     AND student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document) {
                $this->db->rollBack();
                return false;
            }
    
            // Update document status
            $updateQuery = "UPDATE document 
                         SET status = 'draft', 
                             is_submitted = FALSE,
                             submitted_at = NULL
                         WHERE document_id = :document_id 
                         AND student_id = :student_id
                         AND status = 'pending'";
            
            $stmt = $this->db->prepare($updateQuery);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            
            $result = $stmt->execute();
            
            if (!$result || $stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }
    
            // Delete corresponding submission record
            $deleteQuery = "DELETE FROM submission 
                           WHERE document_id = :document_id 
                           AND student_id = :student_id 
                           AND event_id = :event_id 
                           AND requirement_id = :requirement_id";
            
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':event_id', $document['event_id']);
            $stmt->bindParam(':requirement_id', $document['requirement_id']);
            
            $result = $stmt->execute();
    
            if (!$result) {
                $this->db->rollBack();
                return false;
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    public function getDocumentStatus($documentId) {
        try {
            $query = "SELECT 
                        document_id,
                        status
                     FROM document 
                     WHERE document_id = :document_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'document_id' => $result['document_id'],
                    'status' => $result['status']
                ];
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkMissingStatus($eventId, $requirementId, $studentId) {
        try {
            // First check if requirement is overdue
            $query = "SELECT r.due_date < NOW() as is_overdue
                     FROM requirement r
                     WHERE r.requirement_id = :requirement_id
                     AND r.event_id = :event_id";
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['is_overdue']) {
                // If requirement is overdue, mark as missing
                $insertQuery = "INSERT INTO document (
                    event_id,
                    requirement_id,
                    student_id,
                    status,
                    upload_at
                ) VALUES (
                    :event_id,
                    :requirement_id,
                    :student_id,
                    'missing',
                    NOW()
                )";
    
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->bindParam(':event_id', $eventId);
                $insertStmt->bindParam(':requirement_id', $requirementId);
                $insertStmt->bindParam(':student_id', $studentId);
                return $insertStmt->execute();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDocumentById($documentId) {
        try {
            $query = "SELECT * FROM document WHERE document_id = :document_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getDocumentsByStudentId($studentId) {
        try {
            $query = "SELECT 
                        d.*,
                        e.event_name as event_title,
                        r.requirement_name as requirement_title,
                        r.due_date as requirement_due_date
                     FROM document d
                     LEFT JOIN event e ON d.event_id = e.event_id
                     LEFT JOIN requirement r ON d.requirement_id = r.requirement_id
                     WHERE d.student_id = :student_id
                     ORDER BY d.upload_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteDocument($documentId, $studentId) {
        try {
            $query = "DELETE FROM document 
                     WHERE document_id = :document_id 
                     AND student_id = :student_id
                     AND status = 'draft'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            
            $result = $stmt->execute();
            return ($result && $stmt->rowCount() > 0);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkDocumentExists($eventId, $requirementId, $studentId) {
        try {
            // First check if the requirement is overdue
            if ($this->checkMissingStatus($eventId, $requirementId, $studentId)) {
                return true; // Document will be marked as missing
            }
    
            $query = "SELECT COUNT(*) FROM document 
                     WHERE event_id = :event_id 
                     AND requirement_id = :requirement_id 
                     AND student_id = :student_id
                     AND status != 'rejected'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
?>