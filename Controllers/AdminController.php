<?php
namespace Controllers;

use Models\Admin;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once '../models/Admin.php';


class AdminController {

    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
    }

    public function getAdmin() {
        $admins = $this->adminModel->getAllAdmins();
        echo json_encode($admins);
        return $admins;
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

        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        if ($password !== $confirm_password) {
            echo json_encode(["message" => "Passwords do not match."]);
            return;
        }

        // Check if the email already exists
        if ($this->adminModel->emailExists($email)) {
            echo json_encode(["message" => "An account with this email already exists."]);
            return;
        }

        $token = bin2hex(random_bytes(32)); // Generate a token
        if ($this->adminModel->register($name, $email, $password, $token)) { // Pass the token
            echo json_encode(["message" => "Admin registered successfully."]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        ob_clean();
        header('Content-Type: application/json');
        
        error_log(print_r($_POST, true)); // Log the POST data for debugging
        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $admin = $this->adminModel->login($email, $password);

        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($admin['admin_id'], $token); // Store token in the database

            http_response_code(200);
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid email or password."]);
        }
    }


    public function logout() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Authorization header missing."]);
            return;
        }
        
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return;
        }
        
        $token = substr($authHeader, 7);
        $result = $this->adminModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Admin logged out successfully."]);
        } else {
            echo json_encode(["message" => "Failed to log out admin."]);
        }
    }



    public function requestOtp() {
        ob_clean();
        header('Content-Type: application/json');
        $email = $_POST['email'];
    
        if ($this->adminModel->requestOtp($email)) {
            echo json_encode([
                'success' => true,
                'message' => 'OTP sent to your email.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Email does not exist.'
            ]);
        }
    }

    public function sendOTP() {
        if (!isset($_POST['email'])) {
            echo json_encode(["message" => "Email is required."]);
            return;
        }

        $email = $_POST['email'];
        if ($this->adminModel->requestOtp($email)) {
            echo json_encode(["message" => "OTP sent to your email."]);
        } else {
            echo json_encode(["message" => "Failed to send OTP."]);
        }
    }

    public function verifyOTP() {
        if (!isset($_POST['email'], $_POST['otp'])) {
            echo json_encode(["message" => "Email and OTP are required."]);
            return;
        }

        $email = $_POST['email'];
        $otp = $_POST['otp'];

        if ($this->adminModel->verifyOTP($email, $otp)) {
            echo json_encode(["message" => "OTP verified successfully."]);
        } else {
            echo json_encode(["message" => "Invalid OTP."]);
        }
    }

    public function changePassword() {
        if (!isset($_POST['email'], $_POST['new_password'])) {
            echo json_encode(["message" => "Email and new password are required."]);
            return;
        }

        $email = $_POST['email'];
        $newPassword = $_POST['new_password'];

        if ($this->adminModel->changePassword($email, $newPassword)) {
            echo json_encode(["message" => "Password changed successfully."]);
        } else {
            echo json_encode(["message" => "Failed to change password."]);
        }
    }

    public function validateToken() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(["message" => "Token is required."]);
            return false;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            echo json_encode(["message" => "Invalid Authorization header format."]);
            return false;
        }

        $token = substr($authHeader, 7);

        $admin = $this->adminModel->validateToken($token);
        if ($admin) {
            return true;
        } else {
            return false;
        }

    }
}
?>
