<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception; // Ensure PHPMailer is correctly imported


class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($admin_id, $email, $password) {
        $sql = "INSERT INTO admin (admin_id, email, password) VALUES (:admin_id, :email, :password)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
        
        if (!$stmt->execute()) {
            error_log("Admin registration failed: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
        return true;
    }

    public function login($admin_id, $password) {
        $sql = "SELECT * FROM admin WHERE admin_id = :admin_id"; 
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['admin_id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($admin && password_verify($password, $admin['password'])) {
            $token = bin2hex(random_bytes(32));
            $query = "INSERT INTO admin_tokens (token, admin_id) VALUES (:token, :admin_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(['token' => $token, 'admin_id' => $admin['admin_id']]);
    
            return ['token' => $token, 'admin_id' => $admin['admin_id']];
        }

        return false;
    }

    public function validateToken($token) {
        $query = "SELECT admin_id FROM admin_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function logout($token) {
        $query = "DELETE FROM admin_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        return $stmt->execute(['token' => $token]);
    }

    public function verifyOtp($inputOtp) {
        if (isset($_SESSION['otp']) && $_SESSION['otp'] == $inputOtp && time() < $_SESSION['otp_expiry']) {
            return true;
        }
        return false;
    }

    public function requestOtp($email) {
        if ($this->emailExists($email)) {
            $otp = rand(100000, 999999); 
            $_SESSION['otp'] = $otp; 
            $_SESSION['otp_expiry'] = time() + 300; 

            error_log("Session OTP: " . (isset($_SESSION['otp']) ? $_SESSION['otp'] : 'Not set'));
            $this->sendEmail($email, $otp);
            return true; 
        } else {
            return false; 
        }
    }

    private function emailExists($email) {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; 
    }

    private function sendEmail($email, $otp) {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0;

        try {
            $mail->isSMTP(); 
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'YOUR_EMAIL@example.com'; // Replace with your email
            $mail->Password = 'YOUR_PASSWORD'; // Replace with your password

            $mail->SMTPSecure = 'tls'; 
            $mail->Port = 587; 

            $mail->setFrom(getenv('EMAIL_USERNAME'), 'Scholastech');
            $mail->addAddress($email);

            $mail->isHTML(true); 
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: <strong>$otp</strong>"; 
            $mail->AltBody = "Your OTP code is: $otp"; 

            $mail->send();
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    }

    public function updatePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE admin SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['password' => $hashedPassword, 'email' => $email]);
    }
}
?>
