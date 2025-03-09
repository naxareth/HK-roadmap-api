<?php

namespace Controllers;

use Models\Comment;
use Controllers\AdminController;
use Controllers\StudentController;
use Models\Admin;
use Models\Student;
use Exception;
use PDO;

class CommentController {
    private $db;
    private $comment;
    private $adminModel;
    private $studentModel;

    public function __construct($db) {
        $this->db = $db;
        $this->comment = new Comment($db);
        $this->adminModel = new Admin($db);
        $this->studentModel = new Student($db);
    }

    // Helper function to get authorization token
    private function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // Helper function to authenticate user
    private function authenticateUser() {
        $token = $this->getBearerToken();
        if (!$token) {
            return null;
        }

        // Try admin authentication
        $adminData = $this->adminModel->validateToken($token);
        if ($adminData) {
            // Get admin details
            $query = "SELECT * FROM admin WHERE admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':admin_id', $adminData['admin_id']);
            $stmt->execute();
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin) {
                return [
                    'user_type' => 'admin',
                    'user_id' => $admin['admin_id'],
                    'user_name' => $admin['name']
                ];
            }
        }

        // Try student authentication
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData) {
            // Get student details
            $query = "SELECT * FROM student WHERE student_id = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentData['student_id']);
            $stmt->execute();
            $student = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($student) {
                return [
                    'user_type' => 'student',
                    'user_id' => $student['student_id'],
                    'user_name' => $student['name']
                ];
            }
        }

        return null;
    }

    // Add new comment
    public function addComment() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
    
        // Get posted data
        $data = json_decode(file_get_contents("php://input"));
        
        // Debug line to check incoming data
        error_log('Received data: ' . print_r($data, true));
    
        if(!$data || !isset($data->document_id) || !isset($data->requirement_id) || !isset($data->body)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
            return;
        }
    
        // Set comment properties
        $this->comment->document_id = $data->document_id;
        $this->comment->requirement_id = $data->requirement_id;
        $this->comment->body = $data->body;
        $this->comment->user_type = $user['user_type'];
        $this->comment->user_id = $user['user_id'];
        $this->comment->user_name = $user['user_name'];
    
        // Create comment
        if($this->comment->create()) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "Comment created successfully",
                "status" => "success"
            ));
            return;
        }
    
        http_response_code(500);
        echo json_encode(array(
            "message" => "Unable to create comment",
            "status" => "error"
        ));
        return;
    }

    // Get comments for a document
    public function getComments() {
        // Debug: Log the incoming request
        error_log("Getting comments with params: " . json_encode($_GET));
    
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            error_log("Authentication failed");
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }
    
        // Get document_id from request
        $document_id = isset($_GET['document_id']) ? $_GET['document_id'] : null;
        if(!$document_id) {
            error_log("No document_id provided");
            http_response_code(400);
            echo json_encode(array("message" => "Missing document ID"));
            return;
        }
    
        // Debug: Log the document ID
        error_log("Fetching comments for document_id: " . $document_id);
    
        $result = $this->comment->getCommentsByDocument($document_id);
        if($result === false) {
            error_log("Error fetching comments from database");
            http_response_code(500);
            echo json_encode(array("message" => "Error fetching comments"));
            return;
        }
    
        $comments_arr = array();
        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $comment_item = array(
                'comment_id' => $row['comment_id'],
                'document_id' => $row['document_id'],
                'requirement_id' => $row['requirement_id'],
                'user_type' => $row['user_type'],
                'user_name' => $row['user_name'],
                'body' => $row['body'],
                'created_at' => $row['created_at'],
                'is_owner' => ($row['user_type'] === $user['user_type'] && 
                              $row['user_id'] === $user['user_id'])
            );
            array_push($comments_arr, $comment_item);
        }
    
        // Debug: Log the number of comments found
        error_log("Found " . count($comments_arr) . " comments");
    
        http_response_code(200);
        echo json_encode($comments_arr);
        return;
    }

    // Update comment
    // Update comment
public function updateComment() {
    // Authenticate user
    $user = $this->authenticateUser();
    if (!$user) {
        http_response_code(401);
        return json_encode(array(
            "message" => "Unauthorized"
        ));
    }

    try {
        // Get PUT data
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (!isset($data['comment_id']) || !isset($data['body'])) {
            http_response_code(400);
            return json_encode(array(
                "message" => "Missing required fields: comment_id and body"
            ));
        }

        // First verify if comment exists
        $existingComment = $this->comment->getCommentById($data['comment_id']);
        if (!$existingComment) {
            http_response_code(404);
            return json_encode(array(
                "message" => "Comment not found"
            ));
        }

        // Check permissions:
        // 1. Admin can edit any comment
        // 2. Student can only edit their own comments
        if ($user['user_type'] === 'student' &&
            ($existingComment['user_type'] !== $user['user_type'] ||
             $existingComment['user_id'] !== $user['user_id'])) {
            http_response_code(403);
            return json_encode(array(
                "message" => "You can only edit your own comments"
            ));
        }

        // Set up comment object
        $this->comment->comment_id = $data['comment_id'];
        $this->comment->body = $data['body'];
        $this->comment->user_type = $existingComment['user_type'];
        $this->comment->user_id = $existingComment['user_id'];

        // Update the comment
        if ($this->comment->update()) {
            return json_encode(array(
                "message" => "Comment updated successfully"
            ));
        }

        http_response_code(500);
        return json_encode(array(
            "message" => "Failed to update comment"
        ));

    } catch (Exception $e) {
        error_log("Error updating comment: " . $e->getMessage());
        http_response_code(500);
        return json_encode(array(
            "message" => "An error occurred while updating the comment"
        ));
    }
}

// Delete comment
// Delete comment
public function deleteComment() {
    // Authenticate user
    $user = $this->authenticateUser();
    if (!$user) {
        error_log("DELETE COMMENT: Authentication failed");
        http_response_code(401);
        return json_encode(array(
            "message" => "Unauthorized"
        ));
    }

    error_log("DELETE COMMENT: Authenticated user - Type: " . $user['user_type'] . ", ID: " . $user['user_id']);

    try {
        // Get DELETE data
        $data = json_decode(file_get_contents("php://input"), true);
        error_log("DELETE COMMENT: Received data - " . json_encode($data));

        // Validate comment_id
        if (!isset($data['comment_id'])) {
            error_log("DELETE COMMENT: Missing comment_id");
            http_response_code(400);
            return json_encode(array(
                "message" => "Missing required field: comment_id"
            ));
        }

        // First verify if comment exists
        $existingComment = $this->comment->getCommentById($data['comment_id']);
        error_log("DELETE COMMENT: Existing comment data - " . json_encode($existingComment));

        if (!$existingComment) {
            error_log("DELETE COMMENT: Comment not found");
            http_response_code(404);
            return json_encode(array(
                "message" => "Comment not found"
            ));
        }

        // Log permission check details
        error_log("DELETE COMMENT: Permission Check - User Type: " . $user['user_type']);
        error_log("DELETE COMMENT: Comment Owner - Type: " . $existingComment['user_type'] . ", ID: " . $existingComment['user_id']);

        // Check permissions
        if ($user['user_type'] === 'student') {
            if ($existingComment['user_type'] !== $user['user_type'] || 
                $existingComment['user_id'] !== $user['user_id']) {
                error_log("DELETE COMMENT: Permission denied - Student trying to delete another user's comment");
                http_response_code(403);
                return json_encode(array(
                    "message" => "You can only delete your own comments"
                ));
            }
        }

        // Set up comment object
        $this->comment->comment_id = $data['comment_id'];
        
        // For both admin and student, use the original comment's user info
        $this->comment->user_type = $existingComment['user_type'];
        $this->comment->user_id = $existingComment['user_id'];

        error_log("DELETE COMMENT: Attempting delete with - Comment ID: " . $this->comment->comment_id . 
                 ", User Type: " . $this->comment->user_type . 
                 ", User ID: " . $this->comment->user_id);

        // Delete the comment
        if ($this->comment->delete()) {
            error_log("DELETE COMMENT: Successfully deleted");
            return json_encode(array(
                "message" => "Comment deleted successfully",
                "deleted_by" => $user['user_type']
            ));
        }

        error_log("DELETE COMMENT: Failed to delete");
        http_response_code(500);
        return json_encode(array(
            "message" => "Failed to delete comment"
        ));

    } catch (Exception $e) {
        error_log("DELETE COMMENT: Exception occurred - " . $e->getMessage());
        http_response_code(500);
        return json_encode(array(
            "message" => "An error occurred while deleting the comment"
        ));
    }
}
}
?>