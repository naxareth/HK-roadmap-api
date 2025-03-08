<?php
namespace Models;

use PDO;

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllNotifications() {
        $query = "SELECT * FROM notification";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId) {
        $query = "UPDATE notification SET read_notif = 1 WHERE notification_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $notificationId);
        return $stmt->execute();
    }
}