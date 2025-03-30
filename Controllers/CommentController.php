<?php

namespace Controllers;

use Models\Document;
use Models\Comment;
use Models\Admin;
use Models\Student;
use Models\Staff;
use PhpMailer\MailService;
use Exception;
use PDOException;
use PDO;

class CommentController {
    private $db;
    private $comment;
    private $adminModel;
    private $studentModel;
    private $staffModel;
    private $mailService;

    public function __construct($db) {
        $this->db = $db;
        $this->comment = new Comment($db);
        $this->adminModel = new Admin($db);
        $this->studentModel = new Student($db);
        $this->staffModel = new Staff($db);
        $this->mailService = new MailService();
    }

    public function getAllComments() {
        try {
            // Get staff token from headers
            $token = $this->getBearerToken();
            
            // Validate staff token
            $staff = $this->staffModel->validateToken($token);
            if (!$staff) {
                http_response_code(401);
                echo json_encode(["message" => "Unauthorized: Invalid or missing staff token"]);
                return;
            }
    
            // Get all comments from the model
            $result = $this->comment->getAllComments();
            
            if ($result === false) {
                http_response_code(500);
                echo json_encode(["message" => "Failed to retrieve comments"]);
                return;
            }
    
            // Format and return comments
            $comments = $result->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200);

            return json_encode($comments);
    
        } catch (PDOException $e) {
            error_log("Error in getAllComments: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Server error while retrieving comments"
            ]);
        }
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

    public function getComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            return json_encode(["message" => "Unauthorized"]);
        }
    
        try {
            $comment_id = $_GET['comment_id'] ?? null;
            if (!$comment_id) {
                http_response_code(400);
                return json_encode(["message" => "Missing comment_id"]);
            }
    
            $comment = $this->comment->getCommentById($comment_id);
            if (!$comment) {
                http_response_code(404);
                return json_encode(["message" => "Comment not found"]);
            }
    
            // Authorization check
            if ($user['user_type'] === 'student' && 
                (int)$comment['user_id'] !== (int)$user['user_id'] &&
                (int)$comment['student_id'] !== (int)$user['user_id']) {
                http_response_code(403);
                return json_encode(["message" => "Access denied"]);
            }
    
            return json_encode($comment);
    
        } catch (Exception $e) {
            error_log("Error in getComment: " . $e->getMessage());
            http_response_code(500);
            return json_encode(["message" => "Server error"]);
        }
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

        $staffData = $this->staffModel->validateToken($token);
        if ($staffData) {
            $query = "SELECT * FROM staff WHERE staff_id = :staff_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':staff_id', $staffData['staff_id']);
            $stmt->execute();
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff) {
                return [
                    'user_type' => 'staff',
                    'user_id' => $staff['staff_id'],
                    'user_name' => $staff['name']
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

    public function getCommentsForAdmin() {
        $user = $this->authenticateUser();
        if (!$user || $user['user_type'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Admin access required"]);
            return;
        }
    
        $requirement_id = $_GET['requirement_id'] ?? null;
        
        if (!$requirement_id) {
            http_response_code(400);
            echo json_encode(["message" => "Missing requirement_id"]);
            return;
        }
    
        $result = $this->comment->getCommentsByRequirementAdmin($requirement_id);
        
        if ($result === false) {
            http_response_code(500);
            echo json_encode(["message" => "Error fetching comments"]);
            return;
        }
    
        $comments = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = $row;
        }
    
        echo json_encode($comments);
    }

    public function addComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }
    
        $data = json_decode(file_get_contents("php://input"));
        if (!$data || !isset($data->requirement_id) || !isset($data->body)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }
    
        try {
            if ($user['user_type'] === 'student') {
                $student_id = $user['user_id'];
            } 
            // For admin/staff: validate student exists
            else if ($user['user_type'] === 'admin' || $user['user_type'] === 'staff') {
                if (!isset($data->student_id)) {
                    $requiredRole = $user['user_type'] === 'admin' ? 'Admin' : 'Staff';
                    http_response_code(400);
                    echo json_encode(["message" => "$requiredRole must specify student_id"]);
                    return;
                }
                // Verify student exists
                $student = new Student($this->db);
                if (!$student->studentExists($data->student_id)) {
                    http_response_code(404);
                    echo json_encode(["message" => "Student not found"]);
                    return;
                }
                $student_id = $data->student_id;
            }
    
            // If document_id is provided, verify ownership
            if (isset($data->document_id)) {
                $document = new Document($this->db);
                $doc_info = $document->getDocumentsByStudentId($student_id);
                
                // Check if document exists and belongs to the student
                $documentBelongsToStudent = false;
                foreach ($doc_info as $doc) {
                    if ($doc['document_id'] == $data->document_id) {
                        $documentBelongsToStudent = true;
                        break;
                    }
                }
                
                if (!$documentBelongsToStudent) {
                    http_response_code(403);
                    echo json_encode([
                        "message" => "Document not found or does not belong to this student"
                    ]);
                    return;
                }
            }
    
            $this->comment->requirement_id = $data->requirement_id;
            $this->comment->document_id = isset($data->document_id) ? $data->document_id : null;
            $this->comment->student_id = $student_id;
            $this->comment->body = $data->body;
            $this->comment->user_type = $user['user_type'];
            $this->comment->user_id = $user['user_id'];
            $this->comment->user_name = $user['user_name'];
    
            if ($this->comment->create()) {
                try {
                    // Create in-app notification
                    $this->createCommentNotification($user, $data->requirement_id, $student_id);
                    
                    // Send email notification
                    $this->sendCommentEmailNotification($user, $data->requirement_id, $student_id, $data->body);
                    
                    http_response_code(201);
                    echo json_encode([
                        "message" => "Comment created successfully",
                        "status" => "success"
                    ]);
                    return;
                } catch (Exception $e) {
                    // Comment was created but notification failed
                    error_log("Comment created but notification failed: " . $e->getMessage());
                    http_response_code(201);
                    echo json_encode([
                        "message" => "Comment created successfully, but notification failed",
                        "status" => "partial_success"
                    ]);
                    return;
                }
            }
    
            http_response_code(500);
            echo json_encode([
                "message" => "Unable to create comment",
                "status" => "error"
            ]);
    
        } catch (PDOException $e) {
            error_log("Error in addComment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "message" => "Server error occurred",
                "status" => "error"
            ]);
            return;
        }
    }

    private function sendCommentEmailNotification($user, $requirementId, $studentId, $commentBody) {
        try {
            // Get requirement details
            $query = "SELECT r.requirement_name, r.event_id, e.event_name 
                     FROM requirement r 
                     JOIN event e ON r.event_id = e.event_id 
                     WHERE r.requirement_id = :requirement_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->execute();
            $requirementData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$requirementData) {
                throw new Exception("Requirement data not found");
            }
            
            // Get recipient information based on who is commenting
            if ($user['user_type'] === 'student') {
                // Student commented - notify admins
                $query = "SELECT name, email FROM admin LIMIT 1";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
                $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$recipient) {
                    throw new Exception("No admin found to notify");
                }
                
                $recipientName = $recipient['name'];
                $recipientEmail = $recipient['email'];
                $subject = "New Student Comment on " . $requirementData['event_name'];
                $messageIntro = "Student " . $user['user_name'] . " has added a private comment on " . $requirementData['event_name'] . ", " . $requirementData['requirement_name'] . ".";
            } else {
                // Admin or staff commented - notify student
                $query = "SELECT name, email FROM student WHERE student_id = :student_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->execute();
                $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$recipient) {
                    throw new Exception("Student not found");
                }
                
                $recipientName = $recipient['name'];
                $recipientEmail = $recipient['email'];
                $subject = "Private Comment on Your Submission";
                
                if ($user['user_type'] === 'admin') {
                    $userRole = 'Admin';
                } else {
                    $userRole = 'Staff';
                }
                
                $messageIntro = $userRole . " " . $user['user_name'] . " has added a private comment on your submission for " . $requirementData['event_name'] . ", " . $requirementData['requirement_name'] . ".";
            }
            
            // Create email body - fixed indentation to avoid PHP syntax errors
            $body = "<html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; }
            .comment { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; }
            .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>HK Roadmap - Private Comment</h2>
            </div>
            <div class='content'>
                <p>Dear " . $recipientName . ",</p>
                <p>" . $messageIntro . "</p>
                <div class='comment'>
                    <p><strong>Comment:</strong></p>
                    <p>" . $commentBody . "</p>
                </div>
                <p>Please log in to the HK Roadmap system to view and respond to this comment.</p>
                <p>Thank you,<br>HK Roadmap Team</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>";
            
            // Send the email
            return $this->mailService->sendEmail($recipientEmail, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending comment email notification: " . $e->getMessage());
            // Don't throw the exception - we don't want to fail the comment creation
            // if email sending fails
            return false;
        }
    }

    public function getConversation() {
        try {
            $user = $this->authenticateUser();
            if (!$user) {
                http_response_code(401);
                echo json_encode(["message" => "Unauthorized"]);
                return;
            }

            $requirement_id = isset($_GET['requirement_id']) ? (int)$_GET['requirement_id'] : null;
            $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

            if (!$requirement_id || !$student_id) {
                http_response_code(400);
                echo json_encode(["message" => "Missing requirement_id or student_id"]);
                return;
            }

            // Access control
            if ($user['user_type'] === 'student' && (int)$user['user_id'] !== $student_id) {
                http_response_code(403);
                echo json_encode(["message" => "Access denied to this conversation"]);
                return;
            }

            $result = $this->comment->getConversation($requirement_id, $student_id);
            if ($result === false) {
                http_response_code(500);
                echo json_encode(["message" => "Error fetching conversation"]);
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
                    'document_id' => $row['document_id'] ? (int)$row['document_id'] : null,
                    'requirement_id' => (int)$row['requirement_id'],
                    'student_id' => (int)$row['student_id'],
                    'user_type' => $row['user_type'],
                    'user_name' => $row['user_name'],
                    'body' => $row['body'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'is_owner' => $is_owner
                ];
                array_push($comments_arr, $comment_item);
            }

            http_response_code(200);
            echo json_encode($comments_arr);

        } catch (Exception $e) {
            error_log("Error in getConversation: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "An error occurred while fetching conversation"]);
        }
    }

    public function updateComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['comment_id']) || !isset($data['body'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required fields: comment_id and body"]);
                return;
            }
    
            $existingComment = $this->comment->getCommentById($data['comment_id']);
            if (!$existingComment) {
                http_response_code(404);
                echo json_encode(["message" => "Comment not found"]);
                return;
            }
    
            // Access control
            if ($user['user_type'] === 'student') {
                if ($existingComment['user_type'] !== 'student' || 
                    (int)$existingComment['user_id'] !== (int)$user['user_id'] ||
                    (int)$existingComment['student_id'] !== (int)$user['user_id']) {
                    http_response_code(403);
                    echo json_encode(["message" => "You can only edit your own comments"]);
                    return;
                }
            } else if ($user['user_type'] === 'staff') {
                if ($existingComment['user_type'] !== 'staff' || 
                    (int)$existingComment['user_id'] !== (int)$user['user_id']) {
                    http_response_code(403);
                    echo json_encode(["message" => "You can only edit your own comments"]);
                    return;
                }
            }
    
            // For admins, no additional checks needed; they can edit any comment
            $this->comment->comment_id = $data['comment_id'];
            $this->comment->body = $data['body'];
            
            // If admin, use a different update method that doesn't check user_type/user_id
            if ($user['user_type'] === 'admin') {
                $success = $this->comment->updateByAdmin();
            } else {
                $this->comment->user_type = $user['user_type'];
                $this->comment->user_id = $user['user_id'];
                $success = $this->comment->update();
            }
    
            if ($success) {
                http_response_code(200);
                echo json_encode(["message" => "Comment updated successfully"]);
                return;
            }
    
            http_response_code(500);
            echo json_encode(["message" => "Failed to update comment"]);
    
        } catch (Exception $e) {
            error_log("Error in updateComment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "An error occurred while updating the comment"]);
        }
    }

    public function deleteComment() {
        $user = $this->authenticateUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['comment_id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing comment_id"]);
                return;
            }

            $existingComment = $this->comment->getCommentById($data['comment_id']);
            if (!$existingComment) {
                http_response_code(404);
                echo json_encode(["message" => "Comment not found"]);
                return;
            }

            $this->comment->comment_id = $data['comment_id'];

            if ($user['user_type'] === 'admin') {
                if ($this->comment->deleteByAdmin()) {
                    http_response_code(200);
                    echo json_encode([
                        "message" => "Comment deleted successfully",
                        "deleted_by" => "admin"
                    ]);
                    return;
                }
            } else if ($user['user_type'] === 'staff') {
                if ($existingComment['user_type'] === 'staff' && 
                    (int)$existingComment['user_id'] === (int)$user['user_id']) {
                    $this->comment->user_type = $user['user_type'];
                    $this->comment->user_id = $user['user_id'];
                    if ($this->comment->delete()) {
                        http_response_code(200);
                        echo json_encode([
                            "message" => "Comment deleted successfully",
                            "deleted_by" => "staff"
                        ]);
                        return;
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(["message" => "You can only delete your own comments"]);
                    return;
                }
            } else {
                // For students
                if ($existingComment['user_type'] === 'student' && 
                    (int)$existingComment['user_id'] === (int)$user['user_id'] &&
                    (int)$existingComment['student_id'] === (int)$user['user_id']) {
                    
                    $this->comment->user_type = $user['user_type'];
                    $this->comment->user_id = $user['user_id'];
                    
                    if ($this->comment->delete()) {
                        http_response_code(200);
                        echo json_encode([
                            "message" => "Comment deleted successfully",
                            "deleted_by" => "student"
                        ]);
                        return;
                    }
                } else {
                    http_response_code(403);
                    echo json_encode(["message" => "You can only delete your own comments"]);
                    return;
                }
            }

            http_response_code(500);
            echo json_encode(["message" => "Failed to delete comment"]);

        } catch (Exception $e) {
            error_log("Error in deleteComment: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete comment"]);
        }
    }

    private function createCommentNotification($user, $requirementId, $studentId) {
        try {
            // Get the requirement details (includes event_id)
            $query = "SELECT r.requirement_name, r.event_id, e.event_name 
                     FROM requirement r 
                     JOIN event e ON r.event_id = e.event_id 
                     WHERE r.requirement_id = :requirement_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':requirement_id', $requirementId);
            $stmt->execute();
            $requirementData = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$requirementData) {
                throw new Exception("Requirement data not found");
            }
    
            // Create notifications based on user type
            if ($user['user_type'] === 'admin' || $user['user_type'] === 'staff') {
                $notificationBody = "{$user['user_name']} has commented on {$requirementData['event_name']}, {$requirementData['requirement_name']}";
                $notificationType = ($user['user_type'] === 'admin') ? 'student' : 'admin';
                $query = "INSERT INTO notification (notification_body, notification_type, related_user_id, read_notif) 
                         VALUES (:notification_body, 'student', :student_id, 0)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':notification_body', $notificationBody);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->execute();
            } else {
                // Create notification for admin
                $notificationBody = "{$user['user_name']} has commented on {$requirementData['event_name']}, {$requirementData['requirement_name']}";
                
                $query = "INSERT INTO notification (notification_body, notification_type, related_user_id, read_notif) 
                         VALUES (:notification_body, 'admin', :student_id, 0)";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':notification_body', $notificationBody);
                $stmt->bindParam(':student_id', $studentId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error creating comment notification: " . $e->getMessage());
            throw $e;
        }
    }
}
?>