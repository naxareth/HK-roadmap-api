<?php
namespace Models;

use PDO;
use PDOException;
use PhpMailer\MailService;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PhpMailer/MailService.php';

class Staff {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllStaff() {
        $query = "SELECT * FROM staff";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateToken($staff_id, $token) {
        $query = "INSERT INTO staff_tokens (staff_id, token) VALUES (:staff_id, :token)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':staff_id' => $staff_id, ':token' => $token]);
    }

    public function register($name, $email, $password) {
        if ($this->emailExists($email)) return false;

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO staff (name, email, password) VALUES (:name, :email, :password)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $hashedPassword
        ]);

        $staffId = $this->conn->lastInsertId();

        // Create initial profile
        $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email) 
        VALUES (:user_id, 'student', :name, :email)";

        $profileStmt = $this->conn->prepare($profileSql);
        $profileStmt->bindParam(':user_id', $staffId);
        $profileStmt->bindParam(':user_type', 'staff');
        $profileStmt->bindParam(':name', $name);
        $profileStmt->bindParam(':email', $email);

        if (!$profileStmt->execute()) {
        throw new PDOException("Failed to create student profile");
        }

        $this->conn->commit();
        return true;
    }

    public function login($email, $password) {
        try {
            $this->conn->beginTransaction();
    
            // First check if login credentials are valid
            $query = "SELECT s.*, p.profile_id
                     FROM staff s
                     LEFT JOIN user_profiles p ON s.staff_id = p.user_id AND p.user_type = 'staff'
                     WHERE s.email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['email' => $email]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($staff && password_verify($password, $staff['password'])) {
                // Check if profile exists
                if (!$staff['profile_id']) {
                    // Create initial profile if it doesn't exist
                    $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email)
                                 VALUES (:user_id, 'staff', :name, :email)";
                    
                    $profileStmt = $this->conn->prepare($profileSql);
                    $profileStmt->bindParam(':user_id', $staff['staff_id']);
                    $profileStmt->bindParam(':name', $staff['name']);
                    $profileStmt->bindParam(':email', $staff['email']);
                    
                    if (!$profileStmt->execute()) {
                        throw new PDOException("Failed to create initial staff profile");
                    }
                }
                
                $this->conn->commit();
                return $staff;
            }
    
            $this->conn->commit();
            return false;
    
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Staff login error: " . $e->getMessage());
            return false;
        }
    }

    public function validateSubmissionToken($token) {
        try {
            $query = "SELECT s.staff_id, s.name 
                      FROM staff_tokens st
                      JOIN staff s ON st.staff_id = s.staff_id
                      WHERE st.token = :token";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Staff token validation error: " . $e->getMessage());
            return false;
        }
    }

    public function validateToken($token) {
        try {
            $query = "SELECT s.staff_id, s.name FROM staff_tokens st
                      JOIN staff s ON st.staff_id = s.staff_id
                      WHERE st.token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Token validation error: " . $e->getMessage());
            return false;
        }
    }

    public function logout($token) {
        try {
            $query = "DELETE FROM staff_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':token' => $token]);
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    public function emailExists($email) {
        $query = "SELECT * FROM staff WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email]);
        return $stmt->rowCount() > 0;
    }

    public function requestOtp($email) {
        if (!$this->emailExists($email)) return false;

        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300;

        $mailer = new MailService();
        return $mailer->sendOTP($email, $otp);
    }

    public function verifyOTP($email, $otp) {
        return isset($_SESSION['otp']) && 
               $_SESSION['otp'] == $otp && 
               time() < $_SESSION['otp_expiry'];
    }

    public function changePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE staff SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':email' => $email
        ]);
    }
}
?>