<?php
namespace Controllers;

use Models\Notification;
use Models\Admin;
use Models\Student;
use Models\Staff;

class NotificationController {
    private $notificationModel;
    private $adminModel;
    private $studentModel;
    private $staffModel;

    public function __construct($db) {
        $this->notificationModel = new Notification($db);
        $this->adminModel = new Admin($db);
        $this->studentModel = new Student($db);
        $this->staffModel = new Staff($db);
    }

    private function validateAuthAdmin() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        return $this->adminModel->validateToken($token);
    }

    private function validateAuthStaff() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        return $this->staffModel->validateToken($token); // Assume Staff model exists
    }

    private function validateAuthStudent() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $studentData = $this->studentModel->validateToken($token);
        return $studentData ? ['student_id' => $studentData['id']] : null;
    }

    public function getAdminNotifications() {
        $admin = $this->validateAuthAdmin();
        if (!$admin) return;
        
        $notifications = $this->notificationModel->getAllNotificationsAdmin();
        echo json_encode($notifications);
    }

    public function getStudentNotifications() {
        header('Content-Type: application/json');
        
        $student = $this->validateAuthStudent();
        if (!$student) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $studentId = $input['student_id'] ?? null;

        if ($studentId != $student['id']) {
            http_response_code(403);
            echo json_encode(["message" => "Forbidden"]);
            return;
        }

        $notifications = $this->notificationModel->getNotificationsByStudentId($studentId);
        echo json_encode($notifications);
    }

    public function toggleAdminNotification() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($input['notification_id']) || !isset($input['read'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }

        // Authenticate admin
        $admin = $this->validateAuthAdmin();
        if (!$admin) return;

        // Get original notification
        $notification = $this->notificationModel->getNotificationById($input['notification_id']);
        if (!$notification || $notification['notification_type'] !== 'admin') {
            http_response_code(404);
            echo json_encode(["message" => "Admin notification not found"]);
            return;
        }

        // Update notification with admin's ID
        $success = $this->notificationModel->editAdminNotification(
            $input['notification_id'],
            (bool)$input['read'],
            $admin['admin_id']
        );

        if ($success) {
            $updatedNotification = $this->notificationModel->getNotificationById($input['notification_id']);
            
            // Create student notification if marked as read
            if ($updatedNotification['read_notif'] == 1) {
                $this->notificationModel->create(
                    "Admin {$admin['name']} has viewed your document",
                    'student',
                    $updatedNotification['related_user_id'], // Student ID
                    $admin['admin_id']
                );
            }

            echo json_encode([
                "success" => true,
                "notification" => $updatedNotification
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update admin notification"]);
        }
    }

    public function getAdminUnreadCount() {
        try {
            $admin = $this->validateAuthAdmin();
            if (!$admin) return;
            
            $count = $this->notificationModel->getAdminUnreadCount();
            echo json_encode(["unread_count" => $count]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function markAllAdminRead() {
        header('Content-Type: application/json');
        
        try {
            $admin = $this->validateAuthAdmin();
            if (!$admin) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized"]);
                return;
            }

            $notifications = $this->notificationModel->getUnreadAdminNotifications();
            $success = true;

            foreach ($notifications as $notification) {
                $updated = $this->notificationModel->editAdminNotification(
                    $notification['notification_id'],
                    true,
                    $admin['admin_id']
                );

                if ($updated) {
                    $this->notificationModel->create(
                        "Admin {$admin['name']} viewed your document",
                        'student',
                        $notification['related_user_id'],
                        $admin['admin_id']
                    );
                } else {
                    $success = false;
                }
            }

            echo json_encode([
                "success" => $success,
                "message" => $success ? 
                    "All admin notifications marked as read" : 
                    "Partial updates failed"
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }

    public function toggleStudentNotification() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (empty($input['notification_id']) || !isset($input['read'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }

        // Authenticate student
        $student = $this->validateAuthStudent();
        if (!$student) return;

        // Get original notification
        $notification = $this->notificationModel->getNotificationById($input['notification_id']);
        if (!$notification || $notification['notification_type'] !== 'student' || $notification['recipient_id'] != $student['student_id']) {
            http_response_code(404);
            echo json_encode(["message" => "Student notification not found"]);
            return;
        }

        // Update student notification
        $success = $this->notificationModel->editStudentNotification(
            $input['notification_id'],
            (bool)$input['read']
        );

        if ($success) {
            $updatedNotification = $this->notificationModel->getNotificationById($input['notification_id']);
            echo json_encode([
                "success" => true,
                "notification" => $updatedNotification
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update student notification"]);
        }
    }

    public function getStudentUnreadCount() {
        try {
            $student = $this->validateAuthStudent();
            if (!$student) return;
            
            $count = $this->notificationModel->getStudentUnreadCount($student['student_id']);
            echo json_encode(["unread_count" => $count]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function markAllStudentRead() {
        header('Content-Type: application/json');
        
        try {
            $student = $this->validateAuthStudent();
            if (!$student) return;
            
            $success = $this->notificationModel->markAllStudentRead($student['student_id']);
            
            if ($success) {
                echo json_encode([
                    "success" => true,
                    "message" => "All student notifications marked as read"
                ]);
            } else {
                throw new \Exception("Failed to update student notifications");
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }

    public function getStaffNotifications() {
        $staff = $this->validateAuthStaff();
        if (!$staff) return;

        $notifications = $this->notificationModel->getNotificationsByStaff();
        echo json_encode($notifications);
    }

    public function toggleStaffNotification() {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['notification_id']) || !isset($input['read'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }
    
        $staff = $this->validateAuthStaff();
        if (!$staff) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }
    
        $notification = $this->notificationModel->getNotificationById($input['notification_id']);
        if (!$notification) {
            http_response_code(404);
            echo json_encode(["message" => "Notification not found"]);
            return;
        }
    
        $success = $this->notificationModel->editStaffNotification(
            $input['notification_id'],
            (bool)$input['read'],
            $staff['staff_id']
        );
    
        if ($success && $input['read']) {
            $this->notificationModel->createStaffNotification(
                "Staff {$staff['name']} viewed your document",
                $notification['related_user_id'], // Ensure this is the correct student ID
                $staff['staff_id']
            );
        }
    
        echo json_encode(["success" => $success]);
    }
    // Get staff unread count
    public function getStaffUnreadCount() {
        try {
            $staff = $this->validateAuthStaff();
            if (!$staff) return;
            
            $count = $this->notificationModel->getStaffUnreadCount($staff['staff_id']);
            echo json_encode(["unread_count" => $count]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    // Mark all staff notifications as read
    public function markAllStaffRead() {
        header('Content-Type: application/json');
        
        try {
            $staff = $this->validateAuthStaff();
            if (!$staff) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized"]);
                return;
            }
    
            // Fetch all unread staff notifications
            $notifications = $this->notificationModel->getUnreadStaffNotifications($staff['staff_id']);
            $success = true;
    
            foreach ($notifications as $notification) {
                $updated = $this->notificationModel->editStaffNotification(
                    $notification['notification_id'],
                    true,
                    $staff['staff_id']
                );
    
                if ($updated) {
                    $this->notificationModel->create(
                        "Staff {$staff['name']} viewed your document",
                        'student',
                        $notification['related_user_id'],
                        $staff['staff_id']
                    );
                } else {
                    $success = false;
                }
            }
    
            echo json_encode([
                "success" => $success,
                "message" => $success ? 
                    "All staff notifications marked as read" : 
                    "Partial updates failed"
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    }
}