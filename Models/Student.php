<?php
namespace Models;

use PDO;
use PDOException;
use PhpMailer\MailService;



require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PhpMailer/MailService.php';

class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllStudents() {
        $query = "SELECT * FROM student";
        $stmt = $this->conn->query($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function register($name, $email, $password, $token) {
        if ($this->emailExists($email)) {
            return false; // Email already exists
        }


        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO student (name, email, password, token) VALUES (:name, :email, :password, :token)";
        $stmt = $this->conn->prepare($sql);

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    public function emailExists($email) {
        $query = "SELECT * FROM student WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; // Return true if email exists
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
        // Query to validate token and get student info
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

    public function requestOtp($email) {
        try {
            if ($this->emailExists($email)) {
                $otp = rand(100000, 999999); 
                $_SESSION['otp'] = $otp; 
                $_SESSION['otp_expiry'] = time() + 300; 

                $this->sendEmail($email, $otp);
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            error_log("OTP request error: " . $e->getMessage());
            return false;
        }
    }

    private function sendEmail($email, $otp) {
        $mailService = new MailService();
        return $mailService->sendOTP($email, $otp);
    }


    public function verifyOTP($email, $otp) {
        if (isset($_SESSION['otp']) && $_SESSION['otp'] == $otp && time() < $_SESSION['otp_expiry']) {
            return true;
        }
        return false;
    }


    public function changePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE student SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        return $stmt->execute(); // Return true if password changed successfully
    }


    public function logout($token) {
        try {
            // First verify token exists
            $query = "SELECT * FROM student_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                error_log("Token not found: " . $token);
                return false;
            }

            // Proceed with deletion
            $query = "DELETE FROM student_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }


    public function studentExists($student_id) {
        try {
            $query = "SELECT student_id FROM student WHERE student_id = :student_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            error_log("Student exists check error: " . $e->getMessage());
            return false;
        }
    }

}
?>
