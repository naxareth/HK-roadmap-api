<?php

class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Method to check if a student ID exists
    public function studentExists($student_id) {
        $sql = "SELECT COUNT(*) FROM student WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0; // Returns true if student exists
    }

    public function register($student_id, $email, $password) {
        try {
            $sql = "INSERT INTO student (student_id, email, password) VALUES (:student_id, :email, :password)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login($student_id, $password) {
        try {
            // Query to check student credentials
            $query = "SELECT * FROM student WHERE student_id = :student_id AND password = :password";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['student_id' => $student_id, 'password' => $password]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                // Generate a token and store it in the student_tokens table
                $token = bin2hex(random_bytes(32));
                $query = "INSERT INTO student_tokens (token, student_id) VALUES (:token, :student_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(['token' => $token, 'student_id' => $student['student_id']]);

                return ['token' => $token, 'student_id' => $student['student_id']];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function validateToken($token) {
        // Query to validate token and get student info
        $query = "SELECT student_id FROM student_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateToken($student_id, $token) {
        $query = "UPDATE student SET token = :token WHERE student_id = :student_id";
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
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
}
?>
