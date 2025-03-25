<?php

namespace Controllers;

use Models\Submission;
use Controllers\AdminController;
use Controllers\StaffController;
use Models\Notification;

use PDOException;
use Exception;

require_once '../models/Submission.php';
require_once 'AdminController.php';
require_once 'StaffController.php';
require_once '../models/Notification.php';

class SubmissionController {
    private $submissionModel;
    private $adminController;
    private $staffController;
    private $notificationModel;

    public function __construct($db) {
        $this->submissionModel = new Submission($db);
        $this->adminController = new AdminController($db);
        $this->staffController = new StaffController($db);
        $this->notificationModel = new Notification($db); 
    }

    public function getSubmissionsByEventId($eventId) {
        return $this->submissionModel->getSubmissionsByEventId($eventId);
    }

    public function getSubmissionsBySubId() {
        $submissionId = $_GET['submission_id'] ?? null;

        if (!$submissionId) {
            http_response_code(400);
            echo json_encode(["message" => "Submission ID is required"]);
            return;
        }
    
        // Use $this->submissionModel to access the model
        $submissionDetails = $this->submissionModel->getSubmissionsBySubId($submissionId);
        echo json_encode($submissionDetails);
    }
    
    public function getAllSubmissions() {
        $submissions = $this->submissionModel->getAllSubmissions();
        echo json_encode($submissions);
        return;
    }

    public function updateSubmissionStatus() {
        header('Content-Type: application/json');
        try {
            $userData = $this->validateSubmissionToken(getallheaders()['Authorization'] ?? '');
            if (!$userData || (!isset($userData['admin_id']) && !isset($userData['staff_id']))) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Invalid token"]);
                return;
            }
    
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Invalid JSON"]);
                return;
            }
    
            if (!isset($input['submission_id'], $input['status'])) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Missing fields"]);
                return;
            }
    
            // Store just the name in the database
            $approverName = $userData['name'];
            
            // For notification message only
            $approverType = isset($userData['admin_id']) ? "Admin" : "Staff";
    
            $success = $this->submissionModel->updateSubmissionStatus(
                $input['submission_id'],
                $input['status'],
                $approverName  // Store only the name without prefix
            );
    
            if ($success) {
                $submission = $this->submissionModel->getSubmissionById($input['submission_id']);
                if ($submission) {
                    $requirementName = $this->submissionModel->getRequirementName($submission['requirement_id']);
                    
                    // Keep the full classification only in the notification message
                    $message = sprintf(
                        "Your submission for the requirement for %s has been %s by %s %s",
                        $requirementName ?? 'Unknown Requirement',
                        strtolower($input['status']),
                        $approverType,
                        $approverName
                    );
    
                    $notificationSuccess = $this->notificationModel->create(
                        $message,
                        'student',
                        $submission['student_id'],
                        isset($userData['admin_id']) ? $userData['admin_id'] : $userData['staff_id']
                    );
    
                    if (!$notificationSuccess) {
                        error_log("Failed to create notification for submission status update");
                    }
                }
            }
    
            echo json_encode([
                "success" => $success,
                "message" => $success ? "Status updated" : "Update failed"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Database error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Database error"]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log("Server error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Server error"]);
        }
    }

    private function validateSubmissionToken($authHeader) {
        if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
            return false;
        }
    
        $token = substr($authHeader, 7);
        return $this->adminController->validateSubmissionToken($token)  
        ?: $this->staffController->validateSubmissionToken($token);
    }

    private function parseMultipartFormData($rawInput) {
        $data = [];
        $boundary = substr($rawInput, 0, strpos($rawInput, "\r\n"));
        $parts = array_slice(explode($boundary, $rawInput), 1);
        
        foreach ($parts as $part) {
            if ($part == "--\r\n") break;
            
            $part = trim($part);
            list($headers, $body) = explode("\r\n\r\n", $part, 2);
            
            $name = null;
            foreach (explode("\r\n", $headers) as $header) {
                if (stripos($header, 'Content-Disposition:') === 0) {
                    preg_match('/name="([^"]+)"/', $header, $matches);
                    $name = $matches[1];
                }
            }
            
            if ($name) {
                $data[$name] = rtrim($body, "\r\n");
            }
        }
        
        return $data;
    }
}
?>