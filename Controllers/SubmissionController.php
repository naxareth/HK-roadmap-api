<?php

namespace Controllers;

use Models\Submission;
use Controllers\AdminController;

require_once '../models/Submission.php';
require_once 'AdminController.php';

class SubmissionController {
    private $submissionModel;
    private $adminController;

    public function __construct($db) {
        $this->submissionModel = new Submission($db);
        $this->adminController = new AdminController($db);
    }

    public function getSubmissionsByEventId($eventId) {
        return $this->submissionModel->getSubmissionsByEventId($eventId);
    }

    public function getAllSubmissions() {
        $submissions = $this->submissionModel->getAllSubmissions();
        echo json_encode($submissions);
        return $submissions;
    }

    public function updateSubmissionStatus() {
        $headers = getallheaders();
        $contentType = $headers['Content-Type'] ?? '';

        if (strpos($contentType, 'multipart/form-data') !== false) {
            $rawInput = file_get_contents('php://input');
            $input = $this->parseMultipartFormData($rawInput);
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
        }

        error_log("Processed input data: " . print_r($input, true));

        if (!isset($input['submission_id'], $input['status'], $input['approved_by'])) {
            echo json_encode([
                "success" => false, 
                "message" => "Missing required fields: submission_id, status, approved_by."
            ]);
            return;
        }

        $submissionId = $input['submission_id'];
        $status = $input['status'];
        $approvedBy = $input['approved_by'];

        error_log("Processing submission update - ID: $submissionId, Status: $status, Approved By: $approvedBy");

        if ($this->submissionModel->updateSubmissionStatus($submissionId, $status, $approvedBy)) {
            echo json_encode([
                "success" => true, 
                "message" => "Submission status updated successfully."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to update submission status."
            ]);
        }
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