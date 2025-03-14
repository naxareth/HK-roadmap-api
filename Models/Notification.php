<?php
namespace Models;

use PDO;

class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($message, $type, $userId, $adminId) {
        try {
            $query = "INSERT INTO notification (notification_body, notification_type, user_related_id, admin_id) 
                      VALUES (:message, :type, :user_id, :user_related_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_related_id', $adminId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\Exception $e) {
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
            // Prepare the SQL query
            $query = "UPDATE notification 
                     SET read_notif = :read_status,
                         recipient_id = :recipient_id
                     WHERE notification_id = :id
                     AND notification_type = 'admin'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            
            // Set recipient_id to NULL if the notification is being marked as unread
            if (!$readStatus) {
                $stmt->bindValue(':recipient_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':recipient_id', $adminId, PDO::PARAM_INT);
            }
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Admin notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationById($notificationId) { 
        try { 
            $query = "SELECT * FROM notification WHERE notification_id = :id";
            $stmt = $this->db->prepare($query); $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT); 
            $stmt->execute(); return $stmt->fetch(PDO::FETCH_ASSOC); 
        } catch (PDOException $e) { 
            error_log("Error fetching notification: " . $e->getMessage()); 
            return false; } }

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
        } catch (\Exception $e) {
            error_log("Student notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function getAdminUnreadCount() {
        try {
            $query = "SELECT COUNT(*) AS unread_count 
                     FROM notification 
                     WHERE notification_type = 'admin' 
                     AND read_notif = 0";
            
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['unread_count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Admin unread count error: " . $e->getMessage());
            return 0;
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
        } catch (\PDOException $e) {
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
        } catch (\Exception $e) {
            error_log("Mark all student read error: " . $e->getMessage());
            return false;
        }
    }

    public function getNotificationsByStaff() {
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

    public function editStaffNotification($notificationId, $readStatus, $staffId) {
        try {
            // Prepare the SQL query
            $query = "UPDATE notification 
                     SET read_notif = :read_status,
                         staff_recipient_id = :staff_recipient_id
                     WHERE notification_id = :id
                     AND notification_type = 'admin'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->bindValue(':read_status', $readStatus, PDO::PARAM_BOOL);
            
            // Set staff_recipient_id based on read status
            if (!$readStatus) {
                $stmt->bindValue(':staff_recipient_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':staff_recipient_id', $staffId, PDO::PARAM_INT);
            }
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Staff notification update error: " . $e->getMessage());
            return false;
        }
    }

    public function getStaffUnreadCount($staffId) {
        try {
            $query = "SELECT COUNT(*) AS unread_count 
                     FROM notification 
                     WHERE notification_type = 'admin' 
                     AND read_notif = 0";
            
            $stmt = $this->db->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['unread_count'] ?? 0);
        } catch (\PDOException $e) {
            error_log("Staff unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function markAllStaffRead($staffId) {
        // Start a transaction to ensure data integrity
        $this->db->beginTransaction();
        
        try {
            // Update notifications to mark them as read and change type if necessary
            $stmt = $this->db->prepare("
                UPDATE notification 
                SET read_notif = 1, read_notif = NOW(), notification_type = 'staff' 
                WHERE recipient_id = ? 
                AND (notification_type = 'staff' OR notification_type = 'admin')
            ");
            
            $result = $stmt->execute([$staffId]);

            // Commit the transaction
            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $this->db->rollBack();
            error_log("Error marking all staff notifications as read: " . $e->getMessage());
            return false;
        }
    }
}