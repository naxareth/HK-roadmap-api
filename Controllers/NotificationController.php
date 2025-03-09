<?php
namespace Controllers;

use Models\Notification;

class NotificationController {
    private $notificationModel;

    public function __construct($db) {
        $this->notificationModel = new Notification($db);
    }

    public function getNotifications() {
        $notifications = $this->notificationModel->getAllNotifications();
        echo json_encode($notifications);
    }

    public function markNotif() {
        $notificationId = $_GET['notification_id'];
        $success = $this->notificationModel->markAsRead($notificationId);
        if ($success) {
            echo json_encode(["success" => true]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false]);
        }
    }
}