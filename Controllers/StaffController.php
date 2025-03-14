<?php
namespace Controllers;

use Models\Staff;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../models/Staff.php';

class StaffController {
    private $staffModel;

    public function __construct($db) {
        $this->staffModel = new Staff($db);
    }

    public function getStaff() {
        $staffData = $this->validateToken();
        
        if (!$staffData) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            return;
        }
    
        // Then fetch staff data
        $staff = $this->staffModel->getAllStaff();
        echo json_encode($staff);
    }

    public function validateSubmissionToken($token) {
        return $this->staffModel->validateSubmissionToken($token);
    }

    public function register() {
        if (!isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            echo json_encode(["message" => "Passwords do not match."]);
            return;
        }

        if ($this->staffModel->emailExists($email)) {
            echo json_encode(["message" => "Email already exists."]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        if ($this->staffModel->register($name, $email, $password, $token)) {
            echo json_encode(["message" => "Staff registered successfully."]);
        } else {
            echo json_encode(["message" => "Registration failed."]);
        }
    }

    public function login() {
        ob_clean();
        header('Content-Type: application/json');
        
        if (!isset($_POST['email'], $_POST['password'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing credentials."]);
            return;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        $staff = $this->staffModel->login($email, $password);

        if ($staff) {
            $token = bin2hex(random_bytes(32));
            $this->staffModel->updateToken($staff['staff_id'], $token);

            http_response_code(200);
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid credentials."]);
        }
    }

    public function logout() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization missing."]);
            return;
        }

        $token = substr($headers['Authorization'], 7);
        $result = $this->staffModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Logged out successfully."]);
        } else {
            echo json_encode(["message" => "Logout failed."]);
        }
    }

    public function requestOtp() {
        ob_clean();
        header('Content-Type: application/json');
        $email = $_POST['email'];

        if ($this->staffModel->requestOtp($email)) {
            echo json_encode(['success' => true, 'message' => 'OTP sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email not found.']);
        }
    }

    public function verifyOTP() {
        if (!isset($_POST['email'], $_POST['otp'])) {
            echo json_encode(["message" => "Missing data."]);
            return;
        }

        $email = $_POST['email'];
        $otp = $_POST['otp'];

        if ($this->staffModel->verifyOTP($email, $otp)) {
            echo json_encode(["message" => "OTP verified."]);
        } else {
            echo json_encode(["message" => "Invalid OTP."]);
        }
    }

    public function changePassword() {
        if (!isset($_POST['email'], $_POST['new_password'])) {
            echo json_encode(["message" => "Missing fields."]);
            return;
        }

        $email = $_POST['email'];
        $newPassword = $_POST['new_password'];

        if ($this->staffModel->changePassword($email, $newPassword)) {
            echo json_encode(["message" => "Password updated."]);
        } else {
            echo json_encode(["message" => "Update failed."]);
        }
    }

    public function validateToken() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            error_log("Authorization header missing");
            return false;
        }
    
        $token = substr($headers['Authorization'], 7);
        
        if (empty($token)) {
            error_log("Empty token received");
            return false;
        }
    
        $staff = $this->staffModel->validateToken($token);
        
        if (!$staff) {
            error_log("Invalid token: $token");
            return false;
        }
    
        error_log("Valid token for staff ID: " . $staff['staff_id']);
        return $staff;
    }
}
?>