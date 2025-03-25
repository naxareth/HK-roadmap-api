<?php
namespace Controllers;

use Models\Announcement;
use Models\Admin;
use Models\Student;

class AnnouncementController {
    private $announcementModel;
    private $adminModel;
    private $studentModel;

    public function __construct($db) {
        $this->announcementModel = new Announcement($db);
        $this->adminModel = new Admin($db);
        $this->studentModel = new Student($db);
    }

    // Existing methods
    public function getAllAnnouncements() {
        $announcements = $this->announcementModel->getAllAnnouncements();
        if ($announcements !== false) {
            echo json_encode(["announcements" => $announcements]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch announcements"]);
        }
    }

    public function createAnnouncement() {
        $adminData = $this->validateAuth();
        if (!$adminData) return;

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['title']) || empty($input['content'])) {
            http_response_code(400);
            echo json_encode(["message" => "Title and content are required"]);
            return;
        }

        $announcementId = $this->announcementModel->createAnnouncement(
            $input['title'],
            $input['content'],
            $adminData['admin_id']
        );

        if ($announcementId) {
            http_response_code(201);
            echo json_encode([
                "message" => "Announcement created",
                "announcement_id" => $announcementId
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create announcement"]);
        }
    }

    public function updateAnnouncement() {
        $adminData = $this->validateAuth();
        if (!$adminData) return;

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['id']) || empty($input['title']) || empty($input['content'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID, title and content are required"]);
            return;
        }

        $success = $this->announcementModel->updateAnnouncement(
            $input['id'],
            $input['title'],
            $input['content']
        );

        if ($success) {
            echo json_encode(["message" => "Announcement updated"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update announcement"]);
        }
    }

    public function deleteAnnouncement() {
        $adminData = $this->validateAuth();
        if (!$adminData) return;

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Announcement ID is required"]);
            return;
        }

        $success = $this->announcementModel->deleteAnnouncement($input['id']);

        if ($success) {
            echo json_encode(["message" => "Announcement deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete announcement"]);
        }
    }

    // New methods for student announcements with read tracking
    public function getStudentAnnouncements() {
        $studentData = $this->validateStudentAuth();
        if (!$studentData) return;

        $announcements = $this->announcementModel->getAllAnnouncementsWithReadStatus($studentData['student_id']);
        
        if ($announcements !== false) {
            $unreadCount = $this->announcementModel->getUnreadCount($studentData['student_id']);
            echo json_encode([
                "announcements" => $announcements,
                "unread_count" => $unreadCount
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to fetch announcements"]);
        }
    }

    public function markAnnouncementAsRead() {
        $studentData = $this->validateStudentAuth();
        if (!$studentData) return;

        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['announcement_id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Announcement ID is required"]);
            return;
        }

        $success = $this->announcementModel->markAsRead(
            $input['announcement_id'],
            $studentData['student_id']
        );

        if ($success) {
            $unreadCount = $this->announcementModel->getUnreadCount($studentData['student_id']);
            echo json_encode([
                "message" => "Announcement marked as read",
                "unread_count" => $unreadCount
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to mark announcement as read"]);
        }
    }

    public function markAllAnnouncementsAsRead() {
        $studentData = $this->validateStudentAuth();
        if (!$studentData) return;

        $success = $this->announcementModel->markAllAsRead($studentData['student_id']);

        if ($success) {
            echo json_encode([
                "message" => "All announcements marked as read",
                "unread_count" => 0
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to mark all announcements as read"]);
        }
    }

    public function getUnreadCount() {
        $studentData = $this->validateStudentAuth();
        if (!$studentData) return;

        $count = $this->announcementModel->getUnreadCount($studentData['student_id']);
        echo json_encode(["unread_count" => $count]);
    }

    // Helper methods
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

    private function validateStudentAuth() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Authorization header missing"]);
            return null;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        return $this->studentModel->validateToken($token);
    }
}
?>