<?php
session_start();

require_once '../models/Admin.php';

class AdminController extends BaseController {

    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
    }

    public function register() {
        $admin_id = $_POST['admin_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($this->adminModel->register($admin_id, $email, $password)) {
            $this->jsonResponse(["message" => "Admin registered successfully."]);

        } else {
            $this->jsonResponse(["message" => "Admin registration failed."], 400);

        }
    }

    public function login() {
        $admin_id = $_POST['admin_id'];
        $password = $_POST['password'];

        $result = $this->adminModel->login($admin_id, $password);
        if ($result) {
            $this->jsonResponse(["message" => "Login successful.", "token" => $result['token']]);

        } else {
            $this->jsonResponse(["message" => "Invalid admin_id or password."], 401);

        }
    }

    public function logout(){
        $token = $_POST['token'];
        $result = $this->adminModel->logout($token);

        if ($result) {
            $this->jsonResponse(["message" => "Admin logged out successfully."]);

        } else {
            $this->jsonResponse(["message" => "Failed to log out admin."], 400);

        }
    }

    public function requestOtp() {
        $email = $_POST['email'];
        if ($this->adminModel->requestOtp($email)) {
            $this->jsonResponse(["message" => "OTP sent to your email."]);

        } else {
            $this->jsonResponse(["message" => "Email does not exist."], 404);

        }
    }

    public function verifyOtpAndUpdatePassword() {
        $inputOtp = $_POST['otp'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password']; 

        if ($newPassword !== $confirmPassword) {
            $this->handleError("Passwords do not match.");

                return;
        }

        if ($this->adminModel->verifyOtp($inputOtp)) {
            if ($this->adminModel->updatePassword($_SESSION['email'], $newPassword)) {
            $this->jsonResponse(["message" => "Password updated successfully."]);

            } else {
            $this->jsonResponse(["message" => "Failed to update password."], 400);

            }
        } else {
            $this->handleError("Invalid or expired OTP.");

        }
    }
}
?>
