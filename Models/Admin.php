<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        return $stmt->execute();
    }

    public function login($admin_id, $password) {
        $sql = "SELECT * FROM admin WHERE admin_id = :admin_id"; // Only fetch by admin_id
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['admin_id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Check if the admin exists and verify the password
        if ($admin && password_verify($password, $admin['password'])) {
            $token = bin2hex(random_bytes(32));
            $query = "INSERT INTO admin_tokens (token, admin_id) VALUES (:token, :admin_id)";
            $stmt = $this->conn->prepare($query); // Use $this->conn here
            $stmt->execute(['token' => $token, 'admin_id' => $admin['admin_id']]);
    
            return ['token' => $token, 'admin_id' => $admin['admin_id']];
        }

        return false;
    }

    public function validateToken($token) {
        $query = "SELECT admin_id FROM admin_tokens WHERE token = :token";
        $stmt = $this->conn->prepare($query); // Use $this->conn here
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
        // Check if the OTP is set in the session and if it matches
        if (isset($_SESSION['otp']) && $_SESSION['otp'] == $inputOtp && time() < $_SESSION['otp_expiry']) {
            // OTP is valid
            return true;
        }
        // OTP is invalid or expired
        return false;
    }

    public function requestOtp($email) {
        if ($this->emailExists($email)) {
            $otp = rand(100000, 999999); 
            $_SESSION['otp'] = $otp; 
            $_SESSION['otp_expiry'] = time() + 300; 

            error_log("Session OTP: " . (isset($_SESSION['otp']) ? $_SESSION['otp'] : 'Not set'));
            error_log("Input OTP: $inputOtp");
            error_log("Current Time: " . time());
            error_log("OTP Expiry Time: " . (isset($_SESSION['otp_expiry']) ? $_SESSION['otp_expiry'] : 'Not set'));

            $this->sendEmail($email, $otp);
            return true; // Indicate that the OTP was sent
        } else {
            return false; // Email does not exist
        }
    }

    // Check if the email exists in the database
    private function emailExists($email) {
        $query = "SELECT * FROM admin WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false; // Return true if email exists
    }

    // Send OTP via email
    private function sendEmail($email, $otp) {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;

        try {
            $mail->SMTPDebug = 2;
            $mail->isSMTP(); 
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'blueblade906@gmail.com';
            $mail->Password = 'anpq ggby ysjj mbfw';
            $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 587; // TCP port to connect to

            $mail->setFrom('blueblade906@gmail.com', 'Scholastech');
            $mail->addAddress($email);


            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP code is: <strong>$otp</strong>"; // Replace with your OTP code
            $mail->AltBody = "Your OTP code is: $otp"; // Plain text for non-HTML mail clients

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    // Update the password in the database
    public function updatePassword($email, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE admin SET password = :password WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['password' => $hashedPassword, 'email' => $email]);
    }
}
?>