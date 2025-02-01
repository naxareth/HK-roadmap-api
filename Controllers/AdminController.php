<?php
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

        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($this->adminModel->register($name, $email, $hashedPassword)) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($name, $token);
            echo json_encode(["message" => "Admin registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        if (!isset($_POST['name']) || !isset($_POST['password'])) {
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        $name = $_POST['name'];
        $password = $_POST['password'];

        if (empty($name) || empty($password)) {
            echo json_encode(["message" => "All fields are required."]);
            return;
        }

        $admin = $this->adminModel->login($name, $password);
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($name, $token);
            echo json_encode(["message" => "Login successful.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid name or password."]);
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