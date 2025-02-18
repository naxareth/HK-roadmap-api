<?php
namespace Models;

use PDO;
use PDOException;




if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function updateToken($admin_id, $token) {
        $query = "INSERT INTO admin_tokens (admin_id, token) VALUES (:admin_id, :token)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':admin_id', $admin_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }




    public function register($name, $email, $password, $token) {
        if ($this->emailExists($email)) {
            return false; // Email already exists
        }


        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO admin (name, email, password, token) VALUES (:name, :email, :password, :token)";
        $stmt = $this->conn->prepare($sql);

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }


    public function login($email, $password) {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            return $admin; // Return admin data on successful login
        }
        return false; // Invalid credentials
    }


    public function validateToken($token) {
        try {
            $sql = "SELECT * FROM admin WHERE token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            return $admin ? $admin : false;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
    }

    public function logout($token) {
        try {
            // First verify token exists
            $query = "SELECT * FROM admin_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                error_log("Token not found: " . $token);
                return false;
            }

            // Debug log the token being deleted
            error_log("Attempting to delete token: " . $token);

            // Try to delete using token_id
            if (isset($tokenData['token_id'])) {

                // Delete using token_id if found
                $query = "DELETE FROM admin_tokens WHERE token_id = :token_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':token_id', $tokenData['token_id']);
                $deleted = $stmt->execute();
                error_log("Admin token deleted using token_id: " . ($deleted ? 'true' : 'false'));
                return $deleted;
            } else {
                // Fallback to delete using token if token_id not found
                $query = "DELETE FROM admin_tokens WHERE token = :token";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':token', $token);
                $deleted = $stmt->execute(['token' => $token]);
                error_log("Admin token deleted using token: " . ($deleted ? 'true' : 'false'));
                return $deleted;
            }

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
                $_SESSION['otp_expiry'] = time() + 300; 

                $this->sendEmail($email, $otp);
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
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
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;

        try {
            $mail->isSMTP(); 
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'acephilipdenulan12@gmail.com';
            $mail->Password = 'jshj xqip psiv njlc';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('acephilipdenulan12@gmail.com', 'Scholaristech');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: <strong>$otp</strong>";
            $mail->AltBody = "Your OTP code is: $otp";

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
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
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE admin SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        return $stmt->execute(); // Return true if password changed successfully
    }

    public function updatePassword($email, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query = "UPDATE admin SET password = :password WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute(['password' => $hashedPassword, 'email' => $email]);
        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            return false;
        }
    }
}
?>
</create_file>
