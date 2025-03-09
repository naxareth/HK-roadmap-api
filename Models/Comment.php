<?php
namespace Models;

use PDOException;

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

    // Create new comment
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

            // Bind parameters
            $stmt->bindParam(':document_id', $this->document_id);
            $stmt->bindParam(':requirement_id', $this->requirement_id);
            $stmt->bindParam(':user_type', $this->user_type);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':user_name', $this->user_name);
            $stmt->bindParam(':body', $this->body);

            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Error creating comment: " . $e->getMessage());
            return false;
        }
    }

    // Get comments by document ID
    public function getCommentsByDocument($document_id) {
        try {
            // Debug: Log the query execution
            error_log("Executing getCommentsByDocument for document_id: " . $document_id);

            $query = "SELECT 
                        c.*,
                        DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                        DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.document_id = :document_id
                    ORDER BY c.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':document_id', $document_id);
            $stmt->execute();

            // Debug: Log the number of rows found
            error_log("Found " . $stmt->rowCount() . " rows");

            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getCommentsByDocument: " . $e->getMessage());
            return false;
        }
    }

    // Get comments by requirement ID
    public function getCommentsByRequirement($requirement_id, $event_id = null) {
        try {
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
            $stmt->bindParam(':requirement_id', $requirement_id);
            
            if ($event_id) {
                $stmt->bindParam(':event_id', $event_id);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getCommentsByRequirement: " . $e->getMessage());
            return false;
        }
    }

    // Update comment
    // Update comment
public function update() {
    try {
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

        // Bind parameters
        $stmt->bindParam(':body', $this->body);
        $stmt->bindParam(':comment_id', $this->comment_id);
        $stmt->bindParam(':user_type', $this->user_type);
        $stmt->bindParam(':user_id', $this->user_id);

        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Error updating comment: " . $e->getMessage());
        return false;
    }
}

// Delete comment
public function delete() {
    try {
        error_log("COMMENT MODEL - Delete Method");
        error_log("Attempting to delete comment with:");
        error_log("Comment ID: " . $this->comment_id);
        error_log("User Type: " . $this->user_type);
        error_log("User ID: " . $this->user_id);

        $query = "DELETE FROM " . $this->table . "
                WHERE comment_id = :comment_id";

        error_log("SQL Query: " . $query);

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->comment_id = htmlspecialchars(strip_tags($this->comment_id));

        // Bind parameters
        $stmt->bindParam(':comment_id', $this->comment_id);

        $result = $stmt->execute();
        error_log("Delete execution result: " . ($result ? "true" : "false"));
        
        if (!$result) {
            error_log("SQL Error Info: " . json_encode($stmt->errorInfo()));
        }

        return $result;
    } catch(PDOException $e) {
        error_log("Error deleting comment: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        return false;
    }
}

    // Get single comment by ID
    public function getCommentById($comment_id) {
        try {
            $query = "SELECT 
                        c.*,
                        DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                        DATE_FORMAT(c.updated_at, '%Y-%m-%d %H:%i:%s') as updated_at
                    FROM " . $this->table . " c
                    WHERE c.comment_id = :comment_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':comment_id', $comment_id);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting comment by ID: " . $e->getMessage());
            return false;
        }
    }
}
?>