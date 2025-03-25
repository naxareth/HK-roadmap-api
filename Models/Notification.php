<?php
namespace Models;

use PDO;
use PDOException;
use Exception;

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($message, $type, $recipientId, $userRelatedId) {
        try {
            $query = "INSERT INTO notification 
                     (notification_body, notification_type, recipient_id, related_user_id) 
                     VALUES (:message, :type, :recipient_id, :related_user_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':recipient_id', $recipientId, 
                $recipientId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':related_user_id', $userRelatedId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }

    public function getAllNotificationsAdmin() {
        try {
            $query = "SELECT * FROM notification 
                     WHERE notification_type = 'admin' 
                     ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching admin notifications: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationsByStudentId($studentId) {
        try {
            $query = "SELECT * FROM notification 
                     WHERE notification_type = 'student' 
                       AND recipient_id = :student_id
                     ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching student notifications: " . $e->getMessage());
            return false;
        }
    }

    public function editAdminNotification($notificationId, $readStatus, $adminId) {
        try {
            $query = "UPDATE notification 
                     SET read_notif = :read_status,
                         recipient_id = :recipient_id
                     WHERE notification_id = :id
                     AND notification_type = 'admin'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            $stmt->bindValue(':recipient_id', $readStatus ? $adminId : null, 
                $readStatus ? PDO::PARAM_INT : PDO::PARAM_NULL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Admin notification update error: " . $e->getMessage());
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

    public function editStudentNotification($notificationId, $readStatus) {
        try {
            $query = "UPDATE notification 
                     SET read_notif = :read_status
                     WHERE notification_id = :id
                     AND notification_type = 'student'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Student notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function getAdminUnreadCount() {
        try {
            $query = "SELECT * FROM notification 
                     WHERE notification_type = 'admin' 
                       AND read_notif = 0";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching unread admin notifications: " . $e->getMessage());
            return false;
        }
    }

    public function getStudentUnreadCount($studentId) {
        try {
            $query = "SELECT COUNT(*) AS unread_count 
                     FROM notification 
                     WHERE notification_type = 'student' 
                     AND recipient_id = :student_id
                     AND read_notif = 0";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['unread_count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Student unread count error: " . $e->getMessage());
            return 0;
        }
    }

    public function markAllAdminRead() {
        try {
            $query = "UPDATE notification 
                     SET read_notif = 1
                     WHERE notification_type = 'admin'";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Mark all admin read error: " . $e->getMessage());
            return false;
        }
    }

    public function markAllStudentRead($studentId) {
        try {
            $query = "UPDATE notification 
                     SET read_notif = 1
                     WHERE notification_type = 'student'
                     AND recipient_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Mark all student read error: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationsByStaff() {
        try {
            // Fetch notifications intended for all staff members
            $query = "SELECT * FROM notification 
                      WHERE notification_type = 'admin'
                      ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching admin notifications: " . $e->getMessage());
            return false;
        }
    }

    public function editStaffNotification($notificationId, $readStatus, $staffId) {
        try {
            $query = "UPDATE notification 
                     SET read_notif = :read_status,
                         staff_recipient_id = :staff_id
                     WHERE notification_id = :id
                     AND notification_type = 'admin'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            $stmt->bindValue(':staff_id', $staffId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("Successfully updated notification {$notificationId} for staff {$staffId}");
            } else {
                error_log("Failed to update notification {$notificationId} for staff {$staffId}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Staff notification update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createStaffNotification($message, $userRelatedId, $staffRecipientId) {
        try {
            $query = "INSERT INTO notification 
                    (notification_body, notification_type, related_user_id, staff_recipient_id) 
                    VALUES (:message, 'student', :related_user_id, :staff_recipient_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':related_user_id', $userRelatedId, PDO::PARAM_INT);
            $stmt->bindValue(':staff_recipient_id', $staffRecipientId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Failed to create staff notification. Message: $message, Related User: $userRelatedId, Staff: $staffRecipientId");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error creating staff notification: " . $e->getMessage());
            return false;
        }
    }
        
    
    // Add missing method to fetch unread staff notifications
    public function getStaffUnreadCount() {
        try {
            $query = "SELECT COUNT(*) AS unread_count 
                     FROM notification 
                     WHERE notification_type = 'admin' 
                       AND read_notif = 0";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['unread_count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Staff unread count error: " . $e->getMessage());
            return 0;
        }
    }

    public function markAllStaffRead($staffId) {
        $this->db->beginTransaction();
        
        try {
            $stmt = $this->db->prepare("
                UPDATE notification 
                SET read_notif = 1, read_notif = NOW(), notification_type = 'staff' 
                WHERE recipient_id = ? 
                AND (notification_type = 'staff' OR notification_type = 'admin')
            ");
            
            $result = $stmt->execute([$staffId]);
            $this->db->commit();
            return $result;

        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error marking all staff notifications as read: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadAdminNotifications() {
        try {
            // Let's log the query result for debugging
            error_log("Fetching unread admin notifications");
            
            $query = "SELECT notification_id, notification_body, notification_type, 
                            recipient_id, related_user_id, read_notif, created_at 
                     FROM notification 
                     WHERE notification_type = 'admin' 
                       AND read_notif = 0
                     ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug log
            error_log("Unread admin notifications: " . print_r($results, true));
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching unread admin notifications: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUnreadStaffNotifications() {
        try {
            // Same query as admin since staff should see all admin notifications
            $query = "SELECT notification_id, notification_body, notification_type, 
                            recipient_id, related_user_id, read_notif, created_at 
                     FROM notification 
                     WHERE notification_type = 'admin' 
                       AND read_notif = 0
                     ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching unread staff notifications: " . $e->getMessage());
            return [];
        }
    }
}