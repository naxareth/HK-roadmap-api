<?php
require_once '../models/Student.php';

class StudentController {
    private $studentModel;

    public function __construct($db) {
        $this->studentModel = new Student($db);
    }

    public function register() {
        $student_id = $_POST['student_id'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($this->studentModel->register($student_id, $email, $password)) {
            echo json_encode(["message" => "Student registered successfully."]);
        } else {
            echo json_encode(["message" => "Student registration failed."]);
        }
    }

    public function login() {
        $student_id = $_POST['student_id'];
        $password = $_POST['password'];

        $result = $this->studentModel->login($student_id, $password);
        if ($result) {
            echo json_encode(["message" => "Login successful.", "token" => $result['token']]);
        } else {
            echo json_encode(["message" => "Invalid student ID or password."]);
        }
    }

    public function logout(){
        $token = $_POST['token'];
        $result = $this->studentModel->logout($token);

        if ($result) {
            echo json_encode(["message" => "Student logged out successfully."]);
        } else {
            echo json_encode(["message" => "Failed to log out admin."]);
        }
    }
}
?>