<?php

namespace Controllers;

use Models\Student;

require_once '../models/Student.php';

class StudentController {

    private $studentModel;

    public function __construct($db) {
        $this->studentModel = new Student($db);
    }

    public function getStudent() {
        $students = $this->studentModel->getAllStudents();
        echo json_encode($students);
        return $students;
    }

    public function sendOTP() {
        if (!isset($_POST['email'])) {
            echo json_encode(["message" => "Email is required."]);
            return;
        }

        $email = $_POST['email'];
        if ($this->studentModel->requestOtp($email)) {
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

        if ($this->studentModel->verifyOTP($email, $otp)) {
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

        if ($this->studentModel->changePassword($email, $newPassword)) {
            echo json_encode(["message" => "Password changed successfully."]);
        } else {
            echo json_encode(["message" => "Failed to change password."]);
        }
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
        if ($this->studentModel->emailExists($email)) {
            echo json_encode(["message" => "An account with this email already exists."]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        if ($this->studentModel->register($name, $email, $password, $token)) {
            echo json_encode(["message" => "Student registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Student registration failed."]);
        }
    }



    public function login() {
        if (!isset($_POST['email'], $_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $student = $this->studentModel->login($email, $password);

        if ($student) {
            $token = bin2hex(random_bytes(32));
            $this->studentModel->updateToken($student['student_id'], $token); // Store the token in the student_tokens table
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
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
        $result = $this->studentModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Student logged out successfully."]);
        } else {
            echo json_encode(["message" => "Failed to log out student."]);
        }
    }


}
?>
