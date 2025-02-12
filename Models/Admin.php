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

    public function register($name, $email, $password, $token) {
        try {
            $sql = "INSERT INTO admin (name, email, password, token) VALUES (:name, :email, :password, :token)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
            $stmt->bindParam(':token', $token); // Bind the token

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Admin registration error: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        try {
            $sql = "SELECT * FROM admin WHERE email = :email";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($admin && password_verify($password, $admin['password'])) {
                $token = bin2hex(random_bytes(32));
                $query = "INSERT INTO admin_tokens (token, admin_id) VALUES (:token, :admin_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(['token' => $token, 'admin_id' => $admin['admin_id']]);
        
                return ['token' => $token, 'admin_id' => $admin['admin_id']];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            return false;
        }
    }

    public function updateToken($admin_id, $token) {
        try {
            $sql = "UPDATE admin SET token = :token WHERE admin_id = :admin_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':admin_id', $admin_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return false;
        }
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
            $mail->Username = 'blueblade906@gmail.com';
            $mail->Password = 'anpq ggby ysjj mbfw';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('blueblade906@gmail.com', 'Scholaristech');
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
