<?php
 namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP()
    {
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = config('MAIL_HOST');
        $this->mail->SMTPAuth = true;
        $this->mail->Username = config('MAIL_USERNAME');
        $this->mail->Password = config('MAIL_PASSWORD');
        $this->mail->SMTPSecure = config('MAIL_ENCRYPTION');
        $this->mail->Port = config('MAIL_PORT');
    }

    public function sendEmail($subject, $body, $recipients, $fromName)
    {
        try {
            // Recipients
            $this->mail->setFrom($this->mail->Username, $fromName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);

            // Add recipients
            foreach ((array) $recipients as $recipient) {
                $this->mail->addAddress($recipient);
            }

            $this->mail->send();
            return true; // Email sent successfully
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false; // Email sending failed
        }
    }
}
