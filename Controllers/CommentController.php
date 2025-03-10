<?php
namespace Controllers;

use Models\Comment;
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

    private function getBearerToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    private function authenticateUser() {
        $token = $this->getBearerToken();
        if (!$token) {
            return null;
        }

        // Try admin authentication
        $adminData = $this->adminModel->validateToken($token);
        if ($adminData) {
            $query = "SELECT * FROM admin WHERE admin_id = :admin_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':admin_id', $adminData['admin_id']);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
            $query = "SELECT * FROM student WHERE student_id = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentData['student_id']);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
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

    public function addComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if(!$data || !isset($data->document_id) || !isset($data->requirement_id) || !isset($data->body)) {
            http_response_code(400);
            echo json_encode(array("message" => "Missing required fields"));
            return;
        }

        $this->comment->document_id = $data->document_id;
        $this->comment->requirement_id = $data->requirement_id;
        $this->comment->body = $data->body;
        $this->comment->user_type = $user['user_type'];
        $this->comment->user_id = $user['user_id'];
        $this->comment->user_name = $user['user_name'];

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
    }

    public function getComments() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(["message" => "Unauthorized"]);
                return;
            }

            $document_id = isset($_GET['document_id']) ? (int)$_GET['document_id'] : null;
            if (!$document_id) {
                http_response_code(400);
                echo json_encode(["message" => "Missing document ID"]);
                return;
            }

            // For students, verify document ownership
            if ($user['user_type'] === 'student') {
                $query = "SELECT student_id FROM document WHERE document_id = :document_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
                $stmt->execute();
                $document = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$document) {
                    http_response_code(404);
                    echo json_encode(["message" => "Document not found"]);
                    return;
                }

                if ((int)$document['student_id'] !== (int)$user['user_id']) {
                    http_response_code(403);
                    echo json_encode(["message" => "Access denied to this document's comments"]);
                    return;
                }
            }

            $result = $this->comment->getCommentsByDocument($document_id);
            if ($result === false) {
                http_response_code(500);
                echo json_encode(["message" => "Error fetching comments"]);
                return;
            }

            $comments_arr = [];
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $is_owner = (
                    $row['user_type'] === $user['user_type'] && 
                    (string)$row['user_id'] === (string)$user['user_id']
                );

                $comment_item = [
                    'comment_id' => (int)$row['comment_id'],
                    'document_id' => (int)$row['document_id'],
                    'requirement_id' => (int)$row['requirement_id'],
                    'user_type' => $row['user_type'],
                    'user_name' => $row['user_name'],
                    'body' => $row['body'],
                    'created_at' => $row['created_at'],
                    'is_owner' => $is_owner
                ];
                array_push($comments_arr, $comment_item);
            }

            http_response_code(200);
            echo json_encode($comments_arr);

        } catch (Exception $e) {
            error_log("Error in getComments: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "An error occurred while fetching comments"]);
        }
    }

    public function updateComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['comment_id']) || !isset($data['body'])) {
                http_response_code(400);
                echo json_encode(array("message" => "Missing required fields: comment_id and body"));
                return;
            }

            $existingComment = $this->comment->getCommentById($data['comment_id']);
            if (!$existingComment) {
                http_response_code(404);
                echo json_encode(array("message" => "Comment not found"));
                return;
            }

            if ($user['user_type'] === 'student') {
                if ($existingComment['user_type'] !== $user['user_type'] ||
                    (string)$existingComment['user_id'] !== (string)$user['user_id']) {
                    http_response_code(403);
                    echo json_encode(array("message" => "You can only edit your own comments"));
                    return;
                }
            }

            $this->comment->comment_id = $data['comment_id'];
            $this->comment->body = $data['body'];
            $this->comment->user_type = $user['user_type'];
            $this->comment->user_id = $user['user_id'];

            if ($this->comment->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Comment updated successfully"));
                return;
            }

            http_response_code(500);
            echo json_encode(array("message" => "Failed to update comment"));
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "An error occurred while updating the comment"));
        }
    }

    public function deleteComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(array("message" => "Unauthorized"));
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['comment_id'])) {
                http_response_code(400);
                echo json_encode(array("message" => "Missing comment_id"));
                return;
            }

            $existingComment = $this->comment->getCommentById($data['comment_id']);
            if (!$existingComment) {
                http_response_code(404);
                echo json_encode(array("message" => "Comment not found"));
                return;
            }

            $this->comment->comment_id = $data['comment_id'];

            if ($user['user_type'] === 'admin') {
                if ($this->comment->deleteByAdmin()) {
                    http_response_code(200);
                    echo json_encode(array(
                        "message" => "Comment deleted successfully",
                        "deleted_by" => "admin"
                    ));
                    return;
                }
            } else {
                $this->comment->user_type = $user['user_type'];
                $this->comment->user_id = $user['user_id'];
                
                if ($existingComment['user_type'] === $user['user_type'] &&
                    (string)$existingComment['user_id'] === (string)$user['user_id']) {
                    if ($this->comment->delete()) {
                        http_response_code(200);
                        echo json_encode(array(
                            "message" => "Comment deleted successfully",
                            "deleted_by" => "student"
                        ));
                        return;
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(array("message" => "You can only delete your own comments"));
                    return;
                }
            }

            http_response_code(500);
            echo json_encode(array("message" => "Failed to delete comment"));
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to delete comment"));
        }
    }
}
?>