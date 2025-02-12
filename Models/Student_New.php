<?php

class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password, $token) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO student (name, email, password, token) VALUES (:name, :email, :password, :token)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    public function login($email, $password) {
        $query = "SELECT * FROM student WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['password'])) {
            return $student; // Return student data on successful login
        }
        return false; // Invalid credentials
    }

    public function validateToken($token) {
        $query = "SELECT student_id FROM student_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateToken($student_id, $token) {
        $query = "INSERT INTO student_tokens (student_id, token) VALUES (:student_id, :token)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':student_id', $student_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function logout($token) {
        try {
            $query = "DELETE FROM student_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function emailExists($email) {
        $query = "SELECT * FROM student WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; // Return true if email exists
    }
}
?>
