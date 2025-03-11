<?php
namespace Models;

use PDO;

class Announcement {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

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
}
?>