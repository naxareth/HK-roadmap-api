<?php
namespace Controllers;

use Models\Announcement;
use Models\Admin;

class AnnouncementController {
    private $announcementModel;
    private $adminModel;

    public function __construct($db) {
        $this->announcementModel = new Announcement($db);
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
}
?>