<?php
session_start();

require_once '../models/Admin.php';

class AdminController {
    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
    }

    public function register() {
        if (!isset($_POST['admin_id']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $admin_id = $_POST['admin_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($admin_id) || empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->adminModel->register($admin_id, $email, $hashedPassword)) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($admin_id, $token);
            echo json_encode(["message" => "Admin registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        if (!isset($_POST['admin_id']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $admin_id = $_POST['admin_id'];
        $password = $_POST['password'];

        if (empty($admin_id) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $admin = $this->adminModel->login($admin_id, $password);
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($admin_id, $token);
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid admin_id or password."]);
        }
    }

    public function logout() {
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
            if ($this->adminModel->updatePassword($_SESSION['admin_id'], $newPassword)) {
                echo "Password updated successfully.";
            } else {
                echo "Failed to update password.";
            }
        } else {
            echo "Invalid or expired OTP.";
        }
    }

    public function validateToken() {
        if (!isset($_POST['token'])) {
            echo json_encode(["message" => "Token is required."]);
            return false;
        }

        $token = $_POST['token'];
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
