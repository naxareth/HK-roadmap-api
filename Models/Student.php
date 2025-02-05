<?php

class Student {
    private $conn;
    private $tokenModel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($student_id, $email, $password) {
        $sql = "INSERT INTO student (student_id, email, password) VALUES (:student_id, :email, :password)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
        return $stmt->execute();
    }

    public function login($student_id, $password) {
        // Query to check student credentials
        $query = "SELECT * FROM students WHERE student_id = :student_id AND password = :password";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['student_id' => $student_id, 'password' => $password]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Generate a token and store it in the student_tokens table
            $token = bin2hex(random_bytes(32));
            $query = "INSERT INTO student_tokens (token, student_id) VALUES (:token, :student_id)";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['token' => $token, 'student_id' => $student['student_id']]);

            return ['token' => $token, 'student_id' => $student['student_id']];
        }

        return false;
    }

    public function validateToken($token) {
        // Query to validate token and get student info
        $query = "SELECT student_id FROM student_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query); // Use $this->conn here
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function logout($token) {
        $query = "DELETE FROM student_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        return $stmt->execute(['token' => $token]);
    }

}
?>