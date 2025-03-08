<?php
namespace PhpMailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP() {
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'acephilipdenulan12@gmail.com';
        $this->mail->Password = 'jshj xqip psiv njlc';
        $this->mail->SMTPSecure = 'tls';
        $this->mail->Port = 587;
        $this->mail->setFrom('acephilipdenulan12@gmail.com', 'Scholaristech');
    }

    public function sendOTP($email, $otp) {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Your OTP Code';
            $this->mail->Body = "Your OTP code is: <strong>$otp</strong>";
            $this->mail->AltBody = "Your OTP code is: $otp";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    public function sendEmail($recipient, $subject, $body) {
        try {
            $this->mail->setFrom('noreply@yourdomain.com', 'Your System Name');
            $this->mail->addAddress($recipient);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            throw new Exception("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }
}
?>
