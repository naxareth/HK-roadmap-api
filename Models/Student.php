<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Student {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
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
        } catch (Exception $e) {
            error_log("OTP request error: " . $e->getMessage());
            return false;
        }
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
            $query = "DELETE FROM student_tokens WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            return $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
