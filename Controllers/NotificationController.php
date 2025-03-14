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
        return $this->studentModel->validateToken($token);
    }

    public function getAdminNotifications() {
        $admin = $this->validateAuthAdmin();
        if (!$admin) return;
        
        $notifications = $this->notificationModel->getAllNotificationsAdmin();
        echo json_encode($notifications);
    }

    public function getStudentNotifications($studentId) {
        $student = $this->validateAuthStudent();
        if (!$student) return;

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
                    "Admin #{$admin['admin_id']} has viewed your document",
                    'student',
                    $updatedNotification['user_related_id'], // Student ID
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
            $this->validateAuthAdmin();
            $success = $this->notificationModel->markAllAdminRead();
            
            if ($success) {
                echo json_encode([
                    "success" => true,
                    "message" => "All admin notifications marked as read"
                ]);
            } else {
                throw new \Exception("Failed to update admin notifications");
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
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

    // Toggle staff notification status
    public function toggleStaffNotification() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['notification_id']) || !isset($input['read'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }

        $staff = $this->validateAuthStaff();
        if (!$staff) return;

        $notification = $this->notificationModel->getNotificationById($input['notification_id']);

        $success = $this->notificationModel->editStaffNotification(
            $input['notification_id'],
            (bool)$input['read'],
            $staff['staff_id'] // This was missing
        );

        if ($success) {
            echo json_encode([
                "success" => true,
                "notification" => $this->notificationModel->getNotificationById($input['notification_id'])
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update staff notification"]);
        }
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
            if (!$staff) return;
            
            $success = $this->notificationModel->markAllStaffRead($staff['staff_id']);
            
            echo json_encode([
                "success" => $success,
                "message" => $success ? 
                    "All staff notifications marked as read" : 
                    "Failed to update staff notifications"
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}