<?php
namespace Models;

use PDO;
use PDOException;
use PhpMailer\MailService;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../PhpMailer/MailService.php';

class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDb() {
        return $this->conn; // Return the database connection
    }

    public function getAllAdmins() {
        $query = "SELECT * FROM admin";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function validateSubmissionToken($token) {
        try {
            $query = "SELECT a.admin_id, a.name 
                     FROM admin_tokens at
                     JOIN admin a ON at.admin_id = a.admin_id
                     WHERE at.token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Token validation error: " . $e->getMessage());
            return false;
        }
    }

    public function updateToken($admin_id, $token) {
        try {
            $this->conn->beginTransaction(); // Start a new transaction here
    
            // Delete old tokens
            $deleteQuery = "DELETE FROM admin_tokens WHERE admin_id = :admin_id";
            $stmt = $this->conn->prepare($deleteQuery);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
    
            // Insert new token
            $query = "INSERT INTO admin_tokens (admin_id, token) VALUES (:admin_id, :token)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':admin_id', $admin_id);
            
            $result = $stmt->execute();
            $this->conn->commit(); // Now valid (transaction is active)
            return $result;
    
        } catch (PDOException $e) {
            $this->conn->rollBack(); // Now valid (transaction is active)
            error_log("Token update error: " . $e->getMessage());
            return false;
        }
    }

    public function updateProfile($admin_id, $profileData) {
        try {
            $this->conn->beginTransaction();
    
            // Check if profile exists
            $checkQuery = "SELECT profile_id FROM user_profiles 
                          WHERE user_id = :admin_id AND user_type = 'admin'";
            $stmt = $this->conn->prepare($checkQuery);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->execute();
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($profile) {
                $query = "UPDATE user_profiles 
                         SET department = :department,
                             position = :position,
                             contact_number = :contact_number,
                             office = :office
                         WHERE user_id = :admin_id AND user_type = 'admin'";
            } else {
                $query = "INSERT INTO user_profiles 
                         (user_id, user_type, department, position, contact_number, office)
                         VALUES 
                         (:admin_id, 'admin', :department, :position, :contact_number, :office)";
            }
    
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':department', $profileData['department']);
            $stmt->bindParam(':position', $profileData['position']);
            $stmt->bindParam(':contact_number', $profileData['contact_number']);
            $stmt->bindParam(':office', $profileData['office']);
    
            $success = $stmt->execute();
            
            if ($success) {
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollBack();
            return false;
    
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }

    public function register($name, $email, $password) {
        try {
            $this->conn->beginTransaction();

            if ($this->emailExists($email)) {
                $this->conn->rollBack();
                return false;
            }

            // Create admin account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO admin (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            
            if (!$stmt->execute()) {
                throw new PDOException("Failed to create admin account");
            }

            $adminId = $this->conn->lastInsertId();

            // Create initial profile
            $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email) 
                          VALUES (:user_id, 'admin', :name, :email)";
            
            $profileStmt = $this->conn->prepare($profileSql);
            $profileStmt->bindParam(':user_id', $adminId);
            $profileStmt->bindParam(':name', $name);
            $profileStmt->bindParam(':email', $email);
            
            if (!$profileStmt->execute()) {
                throw new PDOException("Failed to create admin profile");
            }

            $this->conn->commit();
            return $adminId;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Admin registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        try {
            $this->conn->beginTransaction();
    
            $query = "SELECT a.*, p.profile_id
                     FROM admin a
                     LEFT JOIN user_profiles p ON a.admin_id = p.user_id AND p.user_type = 'admin'
                     WHERE a.email = :email";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($admin && password_verify($password, $admin['password'])) {
                // Check if profile exists
                if (!$admin['profile_id']) {
                    // Create initial profile if it doesn't exist
                    $profileSql = "INSERT INTO user_profiles (user_id, user_type, name, email)
                                 VALUES (:user_id, 'admin', :name, :email)";
                    
                    $profileStmt = $this->conn->prepare($profileSql);
                    $profileStmt->bindParam(':user_id', $admin['admin_id']);
                    $profileStmt->bindParam(':name', $admin['name']);
                    $profileStmt->bindParam(':email', $admin['email']);
                    
                    if (!$profileStmt->execute()) {
                        throw new PDOException("Failed to create initial admin profile");
                    }
                }
                
                $this->conn->commit();
                return $admin;
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
        try {
            $query = "SELECT a.admin_id, a.name 
                      FROM admin_tokens at
                      JOIN admin a ON at.admin_id = a.admin_id
                      WHERE at.token = :token";
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
            $query = "DELETE FROM admin_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyOTP($email, $otp) {
        if (isset($_SESSION['otp']) && $_SESSION['otp'] == $otp && time() < $_SESSION['otp_expiry']) {
            return true;
        }
        return false;
    }

    public function requestOtp($email) {
        try {
            if ($this->emailExists($email)) {
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = time() + 300; // 5 minutes expiry
                $this->sendEmail($email, $otp);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("OTP request error: " . $e->getMessage());
            return false;
        }
    }

    public function emailExists($email) {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    private function sendEmail($email, $otp) {
        $mailService = new MailService();
        return $mailService->sendOTP($email, $otp);
    }

    public function getEmailById($adminId) {
        $query = "SELECT email FROM admin WHERE admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        return $admin ? $admin['email'] : null;
    }

    public function changePassword($email, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE admin SET password = :password WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Password change error: " . $e->getMessage());
            return false;
        }
    }
}
?>