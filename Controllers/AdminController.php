<?php
session_start();

require_once '../models/Admin.php';

class AdminController {
    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
    }

    public function register() {
        if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $admin_id = $_POST['admin_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->adminModel->register($admin_id, $email, $hashedPassword)) {
            echo json_encode(["message" => "Admin registered successfully."]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        if (!isset($_POST['name']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $admin_id = $_POST['admin_id'];
        $password = $_POST['password'];

        if (empty($name) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        if (empty($name) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $result = $this->adminModel->login($admin_id, $password);
        if ($result) {
            echo json_encode(["message" => "Login successful.", "token" => $result['token']]);
        } else {
            echo json_encode(["message" => "Invalid admin_id or password."]);
        }
    }

    public function logout(){
        if (!isset($_POST['token'])) {
            echo json_encode(["message" => "Token is required."]);
            return false;
        }

        $token = $_POST['token'];
        $result = $this->adminModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Admin logged out successfully."]);
        } else {
            echo json_encode(["message" => "Failed to log out admin."]);
        }
    }

    public function requestOtp() {
        $email = $_POST['email'];
        if ($this->adminModel->requestOtp($email)) {
            echo "OTP sent to your email.";
        } else {
            echo "Email does not exist.";
        }
    }

    public function verifyOtpAndUpdatePassword() {
        $inputOtp = $_POST['otp'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password']; 

        if ($newPassword !== $confirmPassword) {
                echo "Passwords do not match.";
                return;
        }

        if ($this->adminModel->verifyOtp($inputOtp)) {
            if ($this->adminModel->updatePassword($_SESSION['email'], $newPassword)) {
                echo "Password updated successfully.";
            } else {
                echo "Failed to update password.";
            }
        } else {
            echo "Invalid or expired OTP.";
        }
        $admin = $this->adminModel->validateToken($token);
        if ($admin) {
            echo json_encode(["message" => "Token is valid.", "admin" => $admin]);
            return true;
        } else {
            echo json_encode(["message" => "Invalid token."]);
            return false;
        }
    }
}
?>