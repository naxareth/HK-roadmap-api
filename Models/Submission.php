<?php

namespace Models;

use PDO;
use PDOException;

class Submission {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllSubmissions() {
        try {
            $query = "SELECT 
                        e.event_name,
                        r.requirement_name,
                        st.name AS student_name,
                        MAX(s.status) AS status,  -- Use MAX() to resolve grouped status
                        MAX(s.submission_date) AS submission_date,  -- Latest submission date
                        GROUP_CONCAT(s.submission_id) AS submission_ids
                     FROM submission s
                     JOIN event e ON s.event_id = e.event_id
                     JOIN requirement r ON s.requirement_id = r.requirement_id
                     JOIN student st ON s.student_id = st.student_id
                     GROUP BY e.event_id, r.requirement_id, st.student_id
                     ORDER BY s.submission_date DESC";
    
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return $submissions;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Server error: " . $e->getMessage()]);
        }
    }

    public function getSubmissionsByEventId($eventId) {
        $query = "SELECT * FROM submission WHERE event_id = :event_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubmissionsBySubId($submissionId) {
        $query = "SELECT 
                    s.*, 
                    d.file_path,
                    d.link_url,
                    d.document_type,
                    e.event_name,
                    r.requirement_name,
                    st.name AS student_name
                  FROM submission s
                  JOIN document d ON s.document_id = d.document_id
                  JOIN event e ON s.event_id = e.event_id
                  JOIN requirement r ON s.requirement_id = r.requirement_id
                  JOIN student st ON s.student_id = st.student_id
                  WHERE s.submission_id = :submission_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':submission_id', $submissionId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateSubmissionStatus($submissionId, $status, $approvedBy) {
        try {
            $this->db->beginTransaction();

            $query = "UPDATE submission 
                      SET status = :status, 
                          approved_by = :approved_by 
                      WHERE submission_id = :submission_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':approved_by', $approvedBy);
            $stmt->bindParam(':submission_id', $submissionId);
            $stmt->execute();

            error_log("Rows affected in submission table: " . $stmt->rowCount());

            $query = "SELECT s.student_id, s.requirement_id, s.event_id, s.document_id 
                      FROM submission s 
                      WHERE s.submission_id = :submission_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':submission_id', $submissionId);
            $stmt->execute();
            $submissionData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($submissionData) {
                // Update corresponding document status
                $query = "UPDATE document 
                          SET status = :status 
                          WHERE document_id = :document_id 
                          AND student_id = :student_id 
                          AND requirement_id = :requirement_id 
                          AND event_id = :event_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':document_id', $submissionData['document_id']);
                $stmt->bindParam(':student_id', $submissionData['student_id']);
                $stmt->bindParam(':requirement_id', $submissionData['requirement_id']);
                $stmt->bindParam(':event_id', $submissionData['event_id']);
                $stmt->execute();
                
                // Log the number of affected rows in the document table
                error_log("Rows affected in document table: " . $stmt->rowCount());
            }

            // Commit the transaction
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback the transaction on error
            $this->db->rollBack();
            error_log("Failed to update submission and document status: " . $e->getMessage());
            return false;
        }
    }



    public function createSubmission($documentId, $studentId, $eventId, $requirementId, $filePath = null, $linkUrl = null) {
        try {
            $documentType = $linkUrl ? 'link' : 'file';
            
            $query = "INSERT INTO submission (
                student_id,
                event_id,
                requirement_id,
                file_path,
                document_type,
                link_url,
                submission_date,
                status,
                approved_by,
                document_id
            ) VALUES (
                :student_id,
                :event_id,
                :requirement_id,
                :file_path,
                :document_type,
                :link_url,
                NOW(),
                'pending',
                'not yet approved',
                :document_id
            )";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->bindParam(':file_path', $filePath);
            $stmt->bindParam(':document_type', $documentType);
            $stmt->bindParam(':link_url', $linkUrl);
            $stmt->bindParam(':document_id', $documentId);
            
        } catch (\Exception $e) {
            error_log("Failed to create submission: " . $e->getMessage());
            return false;
        }
    }
}
?>