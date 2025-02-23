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
        $submission = $this->submissionModel->getAllSubmissions();
        echo json_encode($submission);
        return $submission;
    }

    public function updateSubmissionStatus() {
        $rawInput = file_get_contents('php://input');

        error_log("Raw input data: " . $rawInput);

        // Define a function to parse multipart/form-data
        function parseMultipartFormData($rawInput) {
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
                    // Trim the body to remove any trailing newlines or carriage returns
                    $data[$name] = rtrim($body, "\r\n");
                }
            }

            return $data;
        }

        // Parse the form data
        $input = parseMultipartFormData($rawInput);

        error_log("Parsed input data: " . print_r($input, true));

        // Check for required fields
        if (!isset($input['submission_id'], $input['status'], $input['approved_by'])) {
            echo json_encode(["success" => false, "message" => "Missing required fields: submission_id, status, approved_by."]);
            return;
        }

        // Extract the required fields
        $submissionId = $input['submission_id'];
        $status = $input['status'];
        $approvedBy = $input['approved_by'];

        error_log("Extracted fields - Submission ID: $submissionId, Status: $status, Approved By: $approvedBy");

        // Call the updateSubmissionStatus method with the validated inputs
        if ($this->submissionModel->updateSubmissionStatus($submissionId, $status, $approvedBy)) {
            echo json_encode(["success" => true, "message" => "Submission status updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update submission status."]);
        }
    }
}
?>