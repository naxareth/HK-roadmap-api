<?php

namespace Models;

use Exception;
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

    public function uploadLinkDocument($eventId, $requirementId, $studentId, $linkUrl) {
        try {
            $this->db->beginTransaction();

            $query = "INSERT INTO document (
                event_id,
                requirement_id,
                student_id,
                file_path,
                document_type,
                link_url,
                upload_at,
                status,
                is_submitted,
                submitted_at
            ) VALUES (
                :event_id,
                :requirement_id,
                :student_id,
                '', -- Empty file_path for link documents
                'link',
                :link_url,
                NOW(),
                'draft',
                0,
                NULL
            )";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':link_url', $linkUrl);

            $stmt->execute();
            $documentId = $this->db->lastInsertId();

            $this->db->commit();
            return $documentId;
        } catch (\Exception $e) {
            $this->db->rollBack();
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
                document_type,
                link_url,
                upload_at,
                status,
                is_submitted,
                submitted_at
            ) VALUES (
                :event_id,
                :requirement_id,
                :student_id,
                :file_path,
                'file',
                NULL,
                NOW(),
                'draft',
                0,
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

    public function submitMultipleDocuments($documentIds, $studentId) {
        try {
            $this->db->beginTransaction();
    
            foreach ($documentIds as $documentId) {
                // Get document details
                $query = "SELECT event_id, requirement_id, file_path, link_url, document_type, status 
                         FROM document
                         WHERE document_id = :document_id
                         AND student_id = :student_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':document_id', $documentId);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->execute();
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if (!$document || $document['status'] !== 'draft') {
                    $this->db->rollBack();
                    return false;
                }
    
                // Update document status
                $updateQuery = "UPDATE document
                               SET status = 'pending',
                                   is_submitted = 1,
                                   submitted_at = NOW()
                               WHERE document_id = :document_id
                               AND student_id = :student_id
                               AND status = 'draft'";
                
                $stmt = $this->db->prepare($updateQuery);
                $stmt->bindParam(':document_id', $documentId);
                $stmt->bindParam(':student_id', $studentId);
                
                if (!$stmt->execute() || $stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return false;
                }
    
                // Create submission record
                $submissionQuery = "INSERT INTO submission (
                    document_id,
                    student_id,
                    event_id,
                    requirement_id,
                    file_path,
                    document_type,
                    link_url,
                    submission_date,
                    status,
                    approved_by
                ) VALUES (
                    :document_id,
                    :student_id,
                    :event_id,
                    :requirement_id,
                    :file_path,
                    :document_type,
                    :link_url,
                    NOW(),
                    'pending',
                    'not_yet_approved'
                )";
    
                $stmt = $this->db->prepare($submissionQuery);
                $stmt->bindParam(':document_id', $documentId);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->bindParam(':event_id', $document['event_id']);
                $stmt->bindParam(':requirement_id', $document['requirement_id']);
                
                // Handle file_path based on document_type
                if ($document['document_type'] === 'link') {
                    $filePath = ''; // Empty string for links since file_path can't be NULL
                } else {
                    $filePath = $document['file_path'];
                }
                $stmt->bindParam(':file_path', $filePath);
                
                $stmt->bindParam(':document_type', $document['document_type']);
                $stmt->bindParam(':link_url', $document['link_url']);
                
                
                if (!$stmt->execute()) {
                    $this->db->rollBack();
                    return false;
                }
                //new function for notif
                $this->createNotification($studentId, $document['event_id'], $document['requirement_id']);
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function submitDocument($documentId, $studentId) {
        try {
            $this->db->beginTransaction();
            
            // Convert student ID to string
            $studentId = (string)$studentId;
    
            // Get document details
            $query = "SELECT event_id, requirement_id, file_path, link_url, document_type, status 
                     FROM document 
                     WHERE document_id = :document_id 
                     AND student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document || $document['status'] !== 'draft') {
                throw new Exception("Invalid document or status");
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
            
            if (!$stmt->execute() || $stmt->rowCount() === 0) {
                throw new Exception("Status update failed");
            }
    
            // Create submission record
            $submissionQuery = "INSERT INTO submission (
                document_id,
                student_id,
                event_id,
                requirement_id,
                file_path,
                document_type,
                link_url,
                submission_date,
                status,
                approved_by
            ) VALUES (
                :document_id,
                :student_id,
                :event_id,
                :requirement_id,
                :file_path,
                :document_type,
                :link_url,
                NOW(),
                'pending',
                'not_yet_approved'
            )";
    
            $stmt = $this->db->prepare($submissionQuery);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':event_id', $document['event_id']);
            $stmt->bindParam(':requirement_id', $document['requirement_id']);
            
            // Handle file_path
            $filePath = ($document['document_type'] === 'link') ? '' : $document['file_path'];
            $stmt->bindParam(':file_path', $filePath);
            
            $stmt->bindParam(':document_type', $document['document_type']);
            $stmt->bindParam(':link_url', $document['link_url']);
            
            if (!$stmt->execute()) {
                throw new Exception("Submission insert failed");
            }
    
            // Create notification
            $this->createNotification($studentId, $document['event_id'], $document['requirement_id']);
    
            $this->db->commit();
            return true;
    
        } catch (\Exception $e) {
            error_log("Submit Error [Doc $documentId]: " . $e->getMessage());
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function unsubmitDocument($documentId, $studentId) {
        try {
            $this->db->beginTransaction();

            // Verify document exists and get details
            $verifyQuery = "SELECT d.*, s.submission_id 
                          FROM document d
                          LEFT JOIN submission s ON d.document_id = s.document_id
                          WHERE d.document_id = :document_id 
                          AND d.student_id = :student_id";
            
            $stmt = $this->db->prepare($verifyQuery);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            $document = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$document || $document['status'] !== 'pending') {
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
            
            if (!$stmt->execute() || $stmt->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            // Delete submission if exists
            if (isset($document['submission_id'])) {
                $deleteQuery = "DELETE FROM submission 
                              WHERE document_id = :document_id 
                              AND student_id = :student_id";
                
                $stmt = $this->db->prepare($deleteQuery);
                $stmt->bindParam(':document_id', $documentId);
                $stmt->bindParam(':student_id', $studentId);
                
                if (!$stmt->execute()) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function unsubmitMultipleDocuments($documentIds, $studentId) {
        try {
            $this->db->beginTransaction();
    
            foreach ($documentIds as $documentId) {
                // Verify document exists and get details
                $verifyQuery = "SELECT d.*, s.submission_id 
                              FROM document d
                              LEFT JOIN submission s ON d.document_id = s.document_id
                              WHERE d.document_id = :document_id 
                              AND d.student_id = :student_id";
                
                $stmt = $this->db->prepare($verifyQuery);
                $stmt->bindParam(':document_id', $documentId);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->execute();
                
                $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if (!$document || $document['status'] !== 'pending') {
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
                
                if (!$stmt->execute() || $stmt->rowCount() === 0) {
                    $this->db->rollBack();
                    return false;
                }
    
                // Delete submission if exists
                if (isset($document['submission_id'])) {
                    $deleteQuery = "DELETE FROM submission 
                                  WHERE document_id = :document_id 
                                  AND student_id = :student_id";
                    
                    $stmt = $this->db->prepare($deleteQuery);
                    $stmt->bindParam(':document_id', $documentId);
                    $stmt->bindParam(':student_id', $studentId);
                    
                    if (!$stmt->execute()) {
                        $this->db->rollBack();
                        return false;
                    }
                }
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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

    public function checkAndCreateMissingDocuments($studentId) {
        try {
            $this->db->beginTransaction();
    
            $insertQuery = "INSERT INTO document (
                event_id,
                requirement_id,
                student_id,
                file_path,
                document_type,
                link_url,
                upload_at,
                status,
                is_submitted,
                submitted_at
            )
            SELECT 
                r.event_id,
                r.requirement_id,
                :student_id,
                NULL,
                NULL,
                NULL,
                NOW(),
                'missing',
                0,
                NULL
            FROM requirement r
            WHERE NOT EXISTS (
                SELECT 1 
                FROM document d 
                WHERE d.requirement_id = r.requirement_id 
                AND d.student_id = :student_id
            )";
    
            $stmt = $this->db->prepare($insertQuery);
            $stmt->bindParam(':student_id', $studentId);
            $result = $stmt->execute();
    
            $this->db->commit();
            return true;
    
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
    public function isRequirementOverdue($eventId, $requirementId) {
        try {
            $query = "SELECT due_date < NOW() as is_overdue
                     FROM requirement 
                     WHERE requirement_id = :requirement_id 
                     AND event_id = :event_id";
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['is_overdue'];
        } catch (\Exception $e) {
            return true; // Return true (overdue) on error to be safe
        }
    }
    
    public function updateOverdueDocuments() {
        try {
            $this->db->beginTransaction();
    
            // Find all draft documents with overdue requirements
            $query = "UPDATE document d
                     INNER JOIN requirement r 
                        ON d.requirement_id = r.requirement_id 
                        AND d.event_id = r.event_id
                     SET d.status = 'missing'
                     WHERE d.status = 'draft' 
                     AND r.due_date < NOW()";
    
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute();
    
            if (!$result) {
                $this->db->rollBack();
                return false;
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
    
    public function checkAndUpdateDocumentStatus($documentId) {
        try {
            $this->db->beginTransaction();
    
            // Check if the document's requirement is overdue
            $query = "SELECT d.status, r.due_date < NOW() as is_overdue
                     FROM document d
                     INNER JOIN requirement r 
                        ON d.requirement_id = r.requirement_id 
                        AND d.event_id = r.event_id
                     WHERE d.document_id = :document_id
                     AND d.status = 'draft'";
    
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':document_id', $documentId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result && $result['is_overdue']) {
                // Update document status to missing
                $updateQuery = "UPDATE document 
                              SET status = 'missing'
                              WHERE document_id = :document_id";
                
                $stmt = $this->db->prepare($updateQuery);
                $stmt->bindParam(':document_id', $documentId);
                
                if (!$stmt->execute()) {
                    $this->db->rollBack();
                    return false;
                }
            }
    
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
                     AND status IN ('pending', 'approved')";  // Only check for pending or approved
            
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

    private function createNotification($studentId, $eventId, $requirementId) {
        $studentName = $this->getStudentName($studentId);
        $eventName = $this->getEventName($eventId);
        $requirementName = $this->getRequirementName($requirementId);
    
        // Create notification body
        $notificationBody = "{$studentName} has submitted a document for {$eventName} in regards to {$requirementName}";
    
        // Insert into notification table
        $query = "INSERT INTO notification (notification_body, read_notif) VALUES (:notification_body, false)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':notification_body', $notificationBody);
        $stmt->execute();
    }

    private function getStudentName($studentId) {
        $query = "SELECT name FROM student WHERE student_id = :student_id"; 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ? $student['name'] : 'Unknown Student';
    }
    
    private function getEventName($eventId) {
        $query = "SELECT event_name FROM event WHERE event_id = :event_id"; 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        return $event ? $event['event_name'] : 'Unknown Event';
    }
    
    private function getRequirementName($requirementId) {
        $query = "SELECT requirement_name FROM requirement WHERE requirement_id = :requirement_id"; 
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':requirement_id', $requirementId);
        $stmt->execute();
        $requirement = $stmt->fetch(PDO::FETCH_ASSOC);
        return $requirement ? $requirement['requirement_name'] : 'Unknown Requirement';
    }
}
?>