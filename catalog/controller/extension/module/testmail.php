<?php
class ControllerExtensionModuleTestMail extends Controller {
    public function index() {
        $mail = new Mail();
        $mail->protocol = 'smtp';
        $mail->parameter = '';
        $mail->smtp_hostname = 'smtp.gmail.com';
        $mail->smtp_username = 'info@headstractor.com.au';
        $mail->smtp_password = 'wepfxymlsvefhomq';
        $mail->smtp_port = '587';
        $mail->smtp_timeout = '5';

        $mail->setTo('balwinder98.ca@gmail.com');
        $mail->setFrom('info@headstractor.com.au');
        $mail->setSender('OpenCart Test');
        $mail->setSubject('Test Email from OpenCart');
        $mail->setText('This is a test email sent from OpenCart using SMTP.');

        if ($mail->send()) {
            echo 'Email sent successfully.';
        } else {
            echo 'Email failed to send.';
        }
    }
}
