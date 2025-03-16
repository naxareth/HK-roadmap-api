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

    public function getStudentNotifications() {
        $student = $this->validateAuthStudent();
        if (!$student) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }

        // Use the student ID from the validated token
        $notifications = $this->notificationModel->getNotificationsByStudentId($student['student_id']);
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
            
            // Debug log
            error_log("Processing notifications: " . print_r($notifications, true));
            
            if (empty($notifications)) {
                echo json_encode([
                    "success" => true,
                    "message" => "No unread notifications to process"
                ]);
                return;
            }
    
            $success = true;
            $processedCount = 0;
            $failedCount = 0;
    
            foreach ($notifications as $notification) {
                error_log("Processing notification: " . print_r($notification, true));
                
                $updated = $this->notificationModel->editAdminNotification(
                    $notification['notification_id'],
                    true,
                    $admin['admin_id']
                );
    
                if ($updated) {
                    if (!empty($notification['related_user_id'])) {
                        $notificationCreated = $this->notificationModel->create(
                            "Admin {$admin['name']} viewed your document",
                            'student',
                            $notification['related_user_id'],
                            $admin['admin_id']
                        );
                        
                        if ($notificationCreated) {
                            $processedCount++;
                        } else {
                            error_log("Failed to create student notification for notification_id: " 
                                . $notification['notification_id']);
                            $failedCount++;
                        }
                    } else {
                        error_log("Missing related_user_id for notification_id: " 
                            . $notification['notification_id']);
                        $failedCount++;
                    }
                } else {
                    error_log("Failed to update notification_id: " . $notification['notification_id']);
                    $success = false;
                }
            }
    
            echo json_encode([
                "success" => $success,
                "message" => sprintf(
                    "Processed %d notifications. %d failed. %s",
                    $processedCount,
                    $failedCount,
                    $success ? "All updates completed" : "Some updates failed"
                )
            ]);
        } catch (\Exception $e) {
            error_log("Error in markAllAdminRead: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error processing notifications: " . $e->getMessage()
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

    public function toggleStaffNotification() {
        header('Content-Type: application/json');
        
        try {
            // Validate input
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['notification_id']) || !isset($input['read'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing required fields"]);
                return;
            }
        
            // Authenticate staff
            $staff = $this->validateAuthStaff();
            if (!$staff) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized"]);
                return;
            }
        
            // Get original notification
            $notification = $this->notificationModel->getNotificationById($input['notification_id']);
            if (!$notification) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Notification not found"]);
                return;
            }
    
            // Debug log
            error_log("Processing staff notification toggle: " . print_r($notification, true));
        
            // Update notification
            $success = $this->notificationModel->editStaffNotification(
                $input['notification_id'],
                (bool)$input['read'],
                $staff['staff_id']
            );
        
            if ($success && $input['read']) {
                // Only create student notification if there's a related_user_id
                if (!empty($notification['related_user_id'])) {
                    $notificationCreated = $this->notificationModel->createStaffNotification(
                        "Staff {$staff['name']} viewed your document",
                        $notification['related_user_id'],
                        $staff['staff_id']
                    );
                    
                    if (!$notificationCreated) {
                        error_log("Failed to create student notification for staff view");
                    }
                } else {
                    error_log("No related_user_id found for notification {$input['notification_id']}");
                }
            }
        
            // Get updated notification for response
            $updatedNotification = $this->notificationModel->getNotificationById($input['notification_id']);
        
            echo json_encode([
                "success" => $success,
                "notification" => $updatedNotification,
                "message" => $success ? "Notification updated successfully" : "Failed to update notification"
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in toggleStaffNotification: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error processing notification: " . $e->getMessage()
            ]);
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
            if (!$staff) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Unauthorized"]);
                return;
            }
    
            $notifications = $this->notificationModel->getUnreadStaffNotifications();
            
            if (empty($notifications)) {
                echo json_encode([
                    "success" => true,
                    "message" => "No unread notifications to process"
                ]);
                return;
            }
    
            $success = true;
            $processedCount = 0;
    
            foreach ($notifications as $notification) {
                $updated = $this->notificationModel->editStaffNotification(
                    $notification['notification_id'],
                    true,
                    $staff['staff_id']
                );
    
                if ($updated) {
                    // Only create student notification if related_user_id exists
                    if (!empty($notification['related_user_id'])) {
                        $this->notificationModel->createStaffNotification(
                            "Staff {$staff['name']} viewed your document",
                            $notification['related_user_id'],
                            $staff['staff_id']
                        );
                    }
                    $processedCount++;
                } else {
                    error_log("Failed to update notification_id: " . $notification['notification_id']);
                    $success = false;
                }
            }
    
            echo json_encode([
                "success" => $success,
                "message" => sprintf(
                    "Processed %d notifications. %s",
                    $processedCount,
                    $success ? "All updates completed" : "Some updates failed"
                )
            ]);
        } catch (\Exception $e) {
            error_log("Error in markAllStaffRead: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Error processing notifications: " . $e->getMessage()
            ]);
        }
    }
}