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
    public $user_type;
    public $user_id;
    public $user_name;
    public $body;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            $query = "INSERT INTO " . $this->table . "
                    (document_id, requirement_id, user_type, user_id, user_name, body)
                    VALUES
                    (:document_id, :requirement_id, :user_type, :user_id, :user_name, :body)";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->document_id = htmlspecialchars(strip_tags($this->document_id));
            $this->requirement_id = htmlspecialchars(strip_tags($this->requirement_id));
            $this->user_type = htmlspecialchars(strip_tags($this->user_type));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->user_name = htmlspecialchars(strip_tags($this->user_name));
            $this->body = htmlspecialchars(strip_tags($this->body));

            // Important: Keep explicit type binding
            $stmt->bindParam(':document_id', $this->document_id, PDO::PARAM_INT);
            $stmt->bindParam(':requirement_id', $this->requirement_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_type', $this->user_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_name', $this->user_name, PDO::PARAM_STR);
            $stmt->bindParam(':body', $this->body, PDO::PARAM_STR);

            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getCommentsByDocument($document_id) {
        try {
            if (!is_numeric($document_id)) {
                return false;
            }

            $query = "SELECT 
                        c.*,
                        DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                        DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.document_id = :document_id
                    ORDER BY c.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getCommentsByRequirement($requirement_id, $event_id = null) {
        try {
            if (!is_numeric($requirement_id) || ($event_id !== null && !is_numeric($event_id))) {
                return false;
            }

            $query = "SELECT 
                        c.*,
                        DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                        DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    JOIN documents d ON c.document_id = d.document_id
                    WHERE c.requirement_id = :requirement_id";

            if ($event_id) {
                $query .= " AND d.event_id = :event_id";
            }

            $query .= " ORDER BY c.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':requirement_id', $requirement_id, PDO::PARAM_INT);
            
            if ($event_id) {
                $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
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

            // Important: Keep explicit type binding
            $stmt->bindParam(':comment_id', $this->comment_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_type', $this->user_type, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);

            $result = $stmt->execute();
            return $result && $stmt->rowCount() > 0;
        } catch(PDOException $e) {
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
            return false;
        }
    }
}
?>