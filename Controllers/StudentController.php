<?php
require_once '../models/Student.php';

class StudentController {
    private $studentModel;

    public function __construct($db) {
        $this->studentModel = new Student($db);
    }

    public function register() {
        if (!isset($_POST['student_id']) || !isset($_POST['email']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $student_id = $_POST['student_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($student_id) || empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->studentModel->register($student_id, $email, $hashedPassword)) {
            $token = bin2hex(random_bytes(32));
            $this->studentModel->updateToken($student_id, $token);
            echo json_encode(["message" => "Student registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Student registration failed."]);
        }
    }

    public function login() {
        if (!isset($_POST['student_id']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $student_id = $_POST['student_id'];
        $password = $_POST['password'];

        if (empty($student_id) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $student = $this->studentModel->login($student_id, $password);
        if ($student) {
            $token = bin2hex(random_bytes(32));
            $this->studentModel->updateToken($student_id, $token);
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid student ID or password."]);
        }
    }
}