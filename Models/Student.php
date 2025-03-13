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

    private function getStudentName($studentId) {
        $query = "SELECT name FROM student WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        return $student ? $student['name'] : 'Unknown Student';
    }

    public function getAllStudents() {
        $query = "SELECT * FROM student";
        $stmt = $this->conn->query($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProfile($token) {
        try {
            $query = "SELECT s.student_id, s.name, s.email, p.*
                     FROM student s
                     LEFT JOIN user_profiles p ON s.student_id = p.user_id AND p.user_type = 'student'
                     JOIN student_tokens st ON s.student_id = st.student_id
                     WHERE st.token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get profile error: " . $e->getMessage());
            return null;
        }
    }

    public function getProfileById($student_id) {
        try {
            $query = "SELECT s.student_id, s.name, s.email, p.*
                     FROM student s
                     LEFT JOIN user_profiles p ON s.student_id = p.user_id AND p.user_type = 'student'
                     WHERE s.student_id = :student_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get profile error: " . $e->getMessage());
            return null;
        }
    }

    public function register($name, $email, $password) {
        try {
            $this->conn->beginTransaction();

            if ($this->emailExists($email)) {
                $this->conn->rollBack();
                return false;
            }

            // Create student account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO student (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            
            if (!$stmt->execute()) {
                throw new PDOException("Failed to create student account");
            }

            $studentId = $this->conn->lastInsertId();

            // Create initial profile
            $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email) 
                          VALUES (:user_id, 'student', :name, :email)";
            
            $profileStmt = $this->conn->prepare($profileSql);
            $profileStmt->bindParam(':user_id', $studentId);
            $profileStmt->bindParam(':name', $name);
            $profileStmt->bindParam(':email', $email);
            
            if (!$profileStmt->execute()) {
                throw new PDOException("Failed to create student profile");
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Student registration error: " . $e->getMessage());
            return false;
        }
    }

    public function emailExists($email) {
        $query = "SELECT * FROM student WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function login($email, $password) {
        try {
            $this->conn->beginTransaction();
    
            // First check if login credentials are valid
            $query = "SELECT s.*, p.profile_id
                     FROM student s
                     LEFT JOIN user_profiles p ON s.student_id = p.user_id AND p.user_type = 'student'
                     WHERE s.email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['email' => $email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($student && password_verify($password, $student['password'])) {
                // Check if profile exists
                if (!$student['profile_id']) {
                    // Create initial profile if it doesn't exist
                    $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email)
                                 VALUES (:user_id, 'student', :name, :email)";
                    
                    $profileStmt = $this->conn->prepare($profileSql);
                    $profileStmt->bindParam(':user_id', $student['student_id']);
                    $profileStmt->bindParam(':name', $student['name']);
                    $profileStmt->bindParam(':email', $student['email']);
                    
                    if (!$profileStmt->execute()) {
                        throw new PDOException("Failed to create initial student profile");
                    }
                }
                
                $this->conn->commit();
                return $student;
            }
    
            $this->conn->commit();
            return false;
    
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function validateToken($token) {
        $query = "SELECT student_id FROM student_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateToken($student_id, $token) {
        // First delete any existing tokens for this student
        $deleteQuery = "DELETE FROM student_tokens WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        // Then insert new token
        $query = "INSERT INTO student_tokens (student_id, token) VALUES (:student_id, :token)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':student_id', $student_id);
        return $stmt->execute();
    }

    public function requestOtp($email) {
        try {
            if ($this->emailExists($email)) {
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = time() + 300;
                $this->sendEmail($email, $otp);
                return true;
            }
            return false;
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
        return $stmt->execute();
    }

    public function logout($token) {
        try {
            $query = "DELETE FROM student_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            return $stmt->execute();
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