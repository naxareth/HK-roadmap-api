<?php
namespace Controllers;

use PhpMailer\MailService;

class MailController {
    private $mailService;

    public function __construct() {
        // No need for DB dependency here unless MailService requires it
        $this->mailService = new MailService();
    }

    public function sendEmail() {
        // Get and validate request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Check for valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid JSON format"]);
            return;
        }

        // Validate required fields
        $required = ['recipient', 'subject', 'body'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing required field: $field"]);
                return;
            }
        }

        try {
            $this->mailService->sendEmail(
                $data['recipient'],
                $data['subject'],
                $data['body']
            );
            echo json_encode(["success" => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}