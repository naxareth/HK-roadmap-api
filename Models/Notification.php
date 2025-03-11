<?php
namespace Models;

use PDO;

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllNotifications() {
        try {
            $query = "SELECT * FROM notification ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return false;
        }
    }

    public function editNotification($notificationId, $readStatus) {
        try {
            $query = "UPDATE notification 
                     SET read_notif = :read_status
                     WHERE notification_id = :id";  // Removed updated_at
    
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationById($notificationId) {
        try {
            $query = "SELECT * FROM notification WHERE notification_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notification: " . $e->getMessage());
            return false;
        }
    }

    public function markAllRead() {
        try {
            $query = "UPDATE notification 
                     SET read_notif = 1
                     WHERE read_notif = 0"; // Remove user/admin ID filter
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Mark all read error: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount() {
        try {
            $query = "SELECT COUNT(*) AS unread_count 
                     FROM notification 
                     WHERE read_notif = 0";
            
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['unread_count'] ?? 0);

        } catch (\PDOException $e) {
            error_log("Unread count error: " . $e->getMessage());
            return 0;
        }
    }
}