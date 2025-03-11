<?php
namespace Controllers;

use Models\Notification;
use Models\Admin;

class NotificationController {
    private $notificationModel;
    private $adminModel;

    public function __construct($db) {
        $this->notificationModel = new Notification($db);
        $this->adminModel = new Admin($db);
    }

    private function validateAuth() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        return $this->adminModel->validateToken($token);
    }

    public function getNotifications() {
        $notifications = $this->notificationModel->getAllNotifications();
        echo json_encode($notifications);
    }

    public function toggleNotificationStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['notification_id']) || !isset($input['read'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            return;
        }
    
        $success = $this->notificationModel->editNotification(
            $input['notification_id'],
            (bool)$input['read']
        );
    
        if ($success) {
            $notification = $this->notificationModel->getNotificationById(
                $input['notification_id']
            );
            echo json_encode([
                "success" => true,
                "notification" => $notification
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update notification status"]);
        }
    }

    public function getUnreadCount() {
        try {
            $count = $this->notificationModel->getUnreadCount();
            echo json_encode(["unread_count" => $count]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function markAllRead() {
        header('Content-Type: application/json');
        
        try {
            $this->validateAuth(); // Just validate auth, no need for user data
            
            $success = $this->notificationModel->markAllRead();
            
            if ($success) {
                echo json_encode([
                    "success" => true,
                    "message" => "All notifications marked as read"
                ]);
            } else {
                throw new \Exception("Database update failed");
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}