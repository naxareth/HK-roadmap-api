<?php
session_start();

require_once '../models/Admin.php';

class AdminController {
    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
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

            echo json_encode(["message" => "Admin registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        error_log(print_r($_POST, true)); // Log the POST data for debugging
        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $admin = $this->adminModel->login($email, $password);

        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($admin['admin_id'], $token); // Store token in the database

            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid email or password."]);
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
