<?php
namespace Models;

use PDO;

class Announcement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Your existing methods
    public function getAllAnnouncements() {
        try {
            $query = "
                SELECT a.*, ad.name AS author_name 
                FROM announcement a
                JOIN admin ad ON a.admin_id = ad.admin_id
                ORDER BY a.created_at DESC
            ";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Announcement fetch error: " . $e->getMessage());
            return false;
        }
    }

    public function createAnnouncement($title, $content, $adminId) {
        try {
            $this->db->beginTransaction();
            
            $query = "INSERT INTO announcement (title, content, admin_id) 
                     VALUES (:title, :content, :admin_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':admin_id', $adminId);
            
            $stmt->execute();
            $announcementId = $this->db->lastInsertId();
            
            $this->db->commit();
            return $announcementId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Announcement creation error: " . $e->getMessage());
            return false;
        }
    }

    public function updateAnnouncement($id, $title, $content) {
        try {
            $query = "UPDATE announcement 
                     SET title = :title, content = :content 
                     WHERE announcement_id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Announcement update error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAnnouncement($id) {
        try {
            $query = "DELETE FROM announcement WHERE announcement_id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Announcement deletion error: " . $e->getMessage());
            return false;
        }
    }

    // New methods for read tracking
    public function markAsRead($announcementId, $studentId) {
        try {
            $query = "INSERT INTO announcement_reads 
                     (announcement_id, student_id) 
                     VALUES (:announcement_id, :student_id)
                     ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':announcement_id', $announcementId, PDO::PARAM_INT);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error marking announcement as read: " . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead($studentId) {
        try {
            $query = "INSERT INTO announcement_reads (announcement_id, student_id)
                     SELECT a.announcement_id, :student_id
                     FROM announcement a
                     LEFT JOIN announcement_reads ar 
                        ON a.announcement_id = ar.announcement_id 
                        AND ar.student_id = :student_id
                     WHERE ar.read_id IS NULL";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error marking all announcements as read: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount($studentId) {
        try {
            $query = "SELECT COUNT(*) as count 
                     FROM announcement a
                     LEFT JOIN announcement_reads ar 
                        ON a.announcement_id = ar.announcement_id 
                        AND ar.student_id = :student_id
                     WHERE ar.read_id IS NULL";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (\Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllAnnouncementsWithReadStatus($studentId) {
        try {
            $query = "SELECT 
                        a.*, 
                        ad.name AS author_name,
                        CASE WHEN ar.read_id IS NULL THEN 0 ELSE 1 END as is_read,
                        ar.read_at
                     FROM announcement a
                     JOIN admin ad ON a.admin_id = ad.admin_id
                     LEFT JOIN announcement_reads ar 
                        ON a.announcement_id = ar.announcement_id 
                        AND ar.student_id = :student_id
                     ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error fetching announcements with read status: " . $e->getMessage());
            return [];
        }
    }
}
?>