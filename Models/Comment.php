<?php

namespace Models;

use PDOException;
use PDO;

class Comment {
    private $conn;
    private $table = 'comments';

    // Properties
    public $comment_id;
    public $document_id;
    public $requirement_id;
    public $student_id;  // Added for chat room functionality
    public $user_type;
    public $user_id;
    public $user_name;
    public $body;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    //admin get, no student id

    public function getCommentsByRequirementAdmin($requirement_id) {
        try {
            $query = "SELECT 
                    c.*,
                    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.requirement_id = :requirement_id
                    ORDER BY c.created_at ASC";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':requirement_id', $requirement_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getCommentsByRequirementAdmin: " . $e->getMessage());
            return false;
        }
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (document_id, requirement_id, student_id, user_type, user_id, user_name, body)
                    VALUES
                    (:document_id, :requirement_id, :student_id, :user_type, :user_id, :user_name, :body)";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->document_id = $this->document_id ? htmlspecialchars(strip_tags($this->document_id)) : null;
            $this->requirement_id = htmlspecialchars(strip_tags($this->requirement_id));
            $this->student_id = htmlspecialchars(strip_tags($this->student_id));
            $this->user_type = htmlspecialchars(strip_tags($this->user_type));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->user_name = htmlspecialchars(strip_tags($this->user_name));
            $this->body = htmlspecialchars(strip_tags($this->body));

            // Important: Keep explicit type binding
            $stmt->bindParam(':document_id', $this->document_id, PDO::PARAM_INT);
            $stmt->bindParam(':requirement_id', $this->requirement_id, PDO::PARAM_INT);
            $stmt->bindParam(':student_id', $this->student_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_type', $this->user_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_name', $this->user_name, PDO::PARAM_STR);
            $stmt->bindParam(':body', $this->body, PDO::PARAM_STR);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error in create comment: " . $e->getMessage());
            return false;
        }
    }

    public function getConversation($requirement_id, $student_id) {
        try {
            $query = "SELECT 
                    c.*,
                    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.requirement_id = :requirement_id 
                    AND c.student_id = :student_id
                    ORDER BY c.created_at ASC";  // Chronological order for chat

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':requirement_id', $requirement_id, PDO::PARAM_INT);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getConversation: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentsByDocument($document_id) {
        try {
            $query = "SELECT 
                    c.*,
                    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.document_id = :document_id
                    ORDER BY c.created_at ASC";  // Changed to ASC for chat-like flow

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getCommentsByDocument: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentsByRequirement($requirement_id, $student_id = null) {
        try {
            $query = "SELECT 
                    c.*,
                    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.requirement_id = :requirement_id";

            if ($student_id) {
                $query .= " AND c.student_id = :student_id";
            }

            $query .= " ORDER BY c.created_at ASC";  // Changed to ASC for chat-like flow

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':requirement_id', $requirement_id, PDO::PARAM_INT);
            
            if ($student_id) {
                $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getCommentsByRequirement: " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        try {
            if (!is_numeric($this->comment_id) || !is_string($this->user_type) || !is_numeric($this->user_id)) {
                return false;
            }

            $query = "UPDATE " . $this->table . "
                    SET body = :body,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE comment_id = :comment_id
                    AND user_type = :user_type
                    AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->body = htmlspecialchars(strip_tags($this->body));
            $this->comment_id = htmlspecialchars(strip_tags($this->comment_id));

            // Important: Keep explicit type binding
            $stmt->bindParam(':body', $this->body, PDO::PARAM_STR);
            $stmt->bindParam(':comment_id', $this->comment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_type', $this->user_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

            $result = $stmt->execute();
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Error in update comment: " . $e->getMessage());
            return false;
        }
    }

    public function updateByAdmin() {
        try {
            if (!is_numeric($this->comment_id)) {
                return false;
            }
    
            $query = "UPDATE " . $this->table . "
                    SET body = :body,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE comment_id = :comment_id";
    
            $stmt = $this->conn->prepare($query);
    
            // Sanitize input
            $this->body = htmlspecialchars(strip_tags($this->body));
            $this->comment_id = htmlspecialchars(strip_tags($this->comment_id));
    
            $stmt->bindParam(':body', $this->body, PDO::PARAM_STR);
            $stmt->bindParam(':comment_id', $this->comment_id, PDO::PARAM_INT);
    
            $result = $stmt->execute();
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Error in updateByAdmin: " . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        try {
            if (!is_numeric($this->comment_id) || !is_string($this->user_type) || !is_numeric($this->user_id)) {
                return false;
            }

            $query = "DELETE FROM " . $this->table . "
                    WHERE comment_id = :comment_id
                    AND user_type = :user_type
                    AND user_id = :user_id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(':comment_id', $this->comment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_type', $this->user_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

            $result = $stmt->execute();
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Error in delete comment: " . $e->getMessage());
            return false;
        }
    }

    public function deleteByAdmin() {
        try {
            if (!is_numeric($this->comment_id)) {
                return false;
            }

            $query = "DELETE FROM " . $this->table . "
                    WHERE comment_id = :comment_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $this->comment_id, PDO::PARAM_INT);

            $result = $stmt->execute();
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
            error_log("Error in deleteByAdmin: " . $e->getMessage());
            return false;
        }
    }

    public function getCommentById($comment_id) {
        try {
            if (!is_numeric($comment_id)) {
                return false;
            }

            $query = "SELECT 
                    c.*,
                    DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                    DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.comment_id = :comment_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error in getCommentById: " . $e->getMessage());
            return false;
        }
    }
}
?>