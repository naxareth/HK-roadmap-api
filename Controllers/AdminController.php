<?php
require_once '../models/Admin.php';

class AdminController {
    private $adminModel;

    public function __construct($db) {
        $this->adminModel = new Admin($db);
    }

    public function register() {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($this->adminModel->register($name, $email, $password)) {
            $token = bin2hex(random_bytes(32));
            $this->adminModel->updateToken($name, $token);
            echo json_encode(["message" => "Admin registered successfully.", "token" => $token]);
        } else {
            echo json_encode(["message" => "Admin registration failed."]);
        }
    }

    public function login() {
        $name = $_POST['name'];
        $password = $_POST['password'];

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
        $token = $_POST['token'];
        $admin = $this->adminModel->validateToken($token);
        return $admin ? $admin : false;
    }
}
?>