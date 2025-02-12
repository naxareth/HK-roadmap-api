<?php
require_once '../models/Student.php';

class StudentController {
    private $studentModel;

    public function __construct($db) {
        $this->studentModel = new Student($db);
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
            $this->studentModel->updateToken($student['id'], $token); // Store the token in the student_tokens table
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid email or password."]);
        }
    }

    public function logout() {
        $token = $_POST['token'];
        $result = $this->studentModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Student logged out successfully."]);
        } else {
            echo json_encode(["message" => "Failed to log out student."]);
        }
    }
}
?>
