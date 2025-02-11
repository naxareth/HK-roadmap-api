<?php
require_once '../models/Student.php';
require_once 'BaseController.php';

class StudentController extends BaseController {
    private $studentModel;

    public function __construct($db) {
        $this->studentModel = new Student($db);
    }

    public function register() {
        if (!isset($_POST['student_id']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            $this->handleError("Missing required fields.");
            return;
        }

        $student_id = $_POST['student_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($student_id) || empty($email) || empty($password)) {
            $this->handleError("All fields are required.");
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->studentModel->register($student_id, $email, $hashedPassword)) {
            $this->jsonResponse(["message" => "Student registered successfully."]);
        } else {
            $this->handleError("Student registration failed.");
        }
    }

    public function login() {
        if (!isset($_POST['student_id']) || !isset($_POST['password'])) {
            $this->handleError("Missing required fields.");
            return;
        }

        $student_id = $_POST['student_id'];
        $password = $_POST['password'];

        if (empty($student_id) || empty($password)) {
            $this->handleError("All fields are required.");
            return;
        }

        $result = $this->studentModel->login($student_id, $password);
        if ($result) {
            $this->jsonResponse(["message" => "Login successful.", "token" => $result['token']]);
        } else {
            $this->handleError("Invalid student ID or password.");
        }
    }

    public function logout(){
        $token = $_POST['token'];
        $result = $this->studentModel->logout($token);

        if ($result) {
            $this->jsonResponse(["message" => "Student logged out successfully."]);
        } else {
            $this->handleError("Failed to log out student.");
        }
    }
}
?>
