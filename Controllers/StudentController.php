<?php

namespace Controllers;

use Models\Student;
use Models\Admin;
use PDO;
use PDOException;

require_once '../models/Student.php';
require_once '../models/Admin.php'; // Include Admin model

class StudentController {
    private $studentModel;
    private $adminModel; // Add admin model

    public function __construct($db) {
        $this->studentModel = new Student($db);
        $this->adminModel = new Admin($db); // Initialize admin model
    }

    public function validateToken() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(["message" => "Token is required"]);
            return false;
        }

        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid Authorization header format"]);
            return false;
        }

        $token = substr($authHeader, 7);

        // Check if the token is for a student
        $studentData = $this->studentModel->validateToken($token);
        if ($studentData) {
            return ['type' => 'student', 'data' => $studentData];
        }

        // Check if the token is for an admin
        $adminData = $this->adminModel->validateToken($token);
        if ($adminData) {
            return ['type' => 'admin', 'data' => $adminData];
        }

        return false; // Token is invalid for both
    }

    public function getStudentProfile() {
        try {
            $tokenData = $this->validateToken();
            
            if (!$tokenData) {
                return;
            }
            
            // Get student profile based on token type
            if ($tokenData['type'] === 'student') {
                $student_id = $tokenData['data']['student_id'];
                $student = $this->studentModel->getProfileById($student_id);
                
                if ($student) {
                    // Add default profile picture if none exists
                    if (empty($student['profile_picture_url'])) {
                        $student['profile_picture_url'] = '/assets/jpg/default-profile.png';
                    }
                    
                    echo json_encode($student);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Student profile not found"]);
                }
            } else {
                http_response_code(403);
                echo json_encode(["message" => "Access denied for admin token"]);
            }
        } catch (PDOException $e) {
            error_log("Profile fetch error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function getStudent() {
        try {
            $tokenData = $this->validateToken();
            
            if (!$tokenData) {
                return;
            }
            
            $students = $this->studentModel->getAllStudents();
            echo json_encode(["students" => $students]);
        } catch (PDOException $e) {
            error_log("Get students error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function getStudentEmails() {
        try {
            $tokenData = $this->validateToken();
            
            if (!$tokenData) {
                return;
            }
            
            $students = $this->studentModel->getAllStudents();
            $emails = array_column($students, 'email');
            echo json_encode($emails);
        } catch (PDOException $e) {
            error_log("Get emails error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function sendOTP() {
        try {
            if (!isset($_POST['email'])) {
                http_response_code(400);
                echo json_encode(["message" => "Email is required"]);
                return;
            }
            
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid email format"]);
                return;
            }
            
            if ($this->studentModel->requestOtp($email)) {
                echo json_encode(["message" => "OTP sent successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to send OTP"]);
            }
        } catch (PDOException $e) {
            error_log("Send OTP error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function verifyOTP() {
        try {
            if (!isset($_POST['email'], $_POST['otp'])) {
                http_response_code(400);
                echo json_encode(["message" => "Email and OTP are required"]);
                return;
            }
            
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $otp = filter_var($_POST['otp'], FILTER_SANITIZE_NUMBER_INT);
            
            if ($this->studentModel->verifyOTP($email, $otp)) {
                echo json_encode(["message" => "OTP verified successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Invalid OTP"]);
            }
        } catch (PDOException $e) {
            error_log("Verify OTP error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function changePassword() {
        try {
            if (!isset($_POST['email'], $_POST['new_password'])) {
                http_response_code(400);
                echo json_encode(["message" => "Email and new password are required"]);
                return;
            }
            
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $newPassword = $_POST['new_password'];
            
            if (strlen($newPassword) < 6) {
                http_response_code(400);
                echo json_encode(["message" => "Password must be at least 6 characters"]);
                return;
            }
            
            if ($this->studentModel->changePassword($email, $newPassword)) {
                echo json_encode(["message" => "Password changed successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to change password"]);
            }
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function register() {
        try {
            if (!isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
                http_response_code(400);
                echo json_encode(["message" => "All fields are required"]);
                return;
            }
            
            $name = trim($_POST['name']);
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                http_response_code(400);
                echo json_encode(["message" => "All fields are required"]);
                return;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid email format"]);
                return;
            }
            
            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(["message" => "Password must be at least 6 characters"]);
                return;
            }
            
            if ($password !== $confirm_password) {
                http_response_code(400);
                echo json_encode(["message" => "Passwords do not match"]);
                return;
            }
            
            if ($this->studentModel->emailExists($email)) {
                http_response_code(400);
                echo json_encode(["message" => "Email already exists"]);
                return;
            }
            
            if ($this->studentModel->register($name, $email, $password)) {
                echo json_encode(["message" => "Registration successful"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Registration failed"]);
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function login() {
        try {
            if (!isset($_POST['email'], $_POST['password'])) {
                http_response_code(400);
                echo json_encode(["message" => "Email and password are required"]);
                return;
            }
            
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                http_response_code(400);
                echo json_encode(["message" => "Email and password are required"]);
                return;
            }
            
            $student = $this->studentModel->login($email, $password);
            
            if ($student) {
                $token = bin2hex(random_bytes(32));
                
                if ($this->studentModel->updateToken($student['student_id'], $token)) {
                    echo json_encode([
                        "message" => "Login successful",
                        "token" => $token,
                        "student_id" => $student['student_id'],
                        "name" => $student['name'],
                        "email" => $student['email']
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(["message" => "Failed to generate token"]);
                }
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Invalid credentials"]);
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }

    public function logout() {
        try {
            $tokenData = $this->validateToken();
            
            if (!$tokenData) {
                return;
            }
            
            // Get the token from the Authorization header
            $headers = getallheaders();
            $authHeader = $headers['Authorization'];
            $token = substr($authHeader, 7);
            
            if ($this->studentModel->logout($token)) {
                echo json_encode(["message" => "Logout successful"]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Logout failed"]);
            }
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Server error"]);
        }
    }
}
?>
