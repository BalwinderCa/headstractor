<?php
namespace Mail;

class Smtp {
    public $to;
    public $from;
    public $sender;
    public $reply_to;
    public $subject;
    public $text;
    public $html;
    public $attachments = array();
    
    public $smtp_hostname = 'smtp.gmail.com';
    public $smtp_username;
    public $smtp_password;
    public $smtp_port = 587;  // Changed to 587 for STARTTLS
    public $smtp_timeout = 30;
    public $max_attempts = 3;
    public $verp = false;
    private $debug = true;

    private function log($message) {
        if ($this->debug) {
            error_log('SMTP Debug: ' . $message);
        }
    }

    public function send() {
        if (is_array($this->to)) {
            $to = implode(',', $this->to);
        } else {
            $to = $this->to;
        }

        $this->log("Attempting to connect to {$this->smtp_hostname}:{$this->smtp_port}");

        // Initial connection (plain TCP)
        $handle = @stream_socket_client(
            "{$this->smtp_hostname}:{$this->smtp_port}",
            $errno,
            $errstr,
            $this->smtp_timeout
        );

        if (!$handle) {
            throw new \Exception("Connection failed: $errstr ($errno)");
        }

        $this->log("Initial connection established");

        // Set timeout
        if (substr(PHP_OS, 0, 3) != 'WIN') {
            stream_set_timeout($handle, $this->smtp_timeout, 0);
        }

        // Read greeting
        $this->getResponse($handle);

        // Send EHLO
        $this->log("Sending initial EHLO");
        $hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fputs($handle, "EHLO {$hostname}\r\n");
        $this->getResponse($handle, 250);

        // Start TLS
        $this->log("Starting TLS");
        fputs($handle, "STARTTLS\r\n");
        $this->getResponse($handle, 220);

        // Enable crypto
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        if (stream_socket_enable_crypto($handle, true, $crypto_method) === false) {
            throw new \Exception("Failed to enable TLS encryption");
        }

        $this->log("TLS encryption enabled");

        // Send EHLO again after TLS
        $this->log("Sending EHLO after TLS");
        fputs($handle, "EHLO {$hostname}\r\n");
        $this->getResponse($handle, 250);

        // Authentication
        $this->log("Starting authentication");
        fputs($handle, "AUTH LOGIN\r\n");
        $this->getResponse($handle, 334);

        fputs($handle, base64_encode($this->smtp_username) . "\r\n");
        $this->getResponse($handle, 334);

        fputs($handle, base64_encode($this->smtp_password) . "\r\n");
        $this->getResponse($handle, 235);

        // Send From
        if ($this->verp) {
            fputs($handle, "MAIL FROM: <{$this->from}>XVERP\r\n");
        } else {
            fputs($handle, "MAIL FROM: <{$this->from}>\r\n");
        }
        $this->getResponse($handle, 250);

        // Send To
        if (!is_array($this->to)) {
            fputs($handle, "RCPT TO: <{$this->to}>\r\n");
            $reply = $this->getResponse($handle);
            if (substr($reply, 0, 3) != 250 && substr($reply, 0, 3) != 251) {
                throw new \Exception('Error: RCPT TO not accepted from server!');
            }
        } else {
            foreach ($this->to as $recipient) {
                fputs($handle, "RCPT TO: <{$recipient}>\r\n");
                $reply = $this->getResponse($handle);
                if (substr($reply, 0, 3) != 250 && substr($reply, 0, 3) != 251) {
                    throw new \Exception('Error: RCPT TO not accepted from server!');
                }
            }
        }

        // Send Data
        fputs($handle, "DATA\r\n");
        $this->getResponse($handle, 354);

        // Construct message
        $boundary = '----=_NextPart_' . md5(time());

        $header = 'MIME-Version: 1.0' . "\r\n";
        $header .= 'To: <' . $to . '>' . "\r\n";
        $header .= 'Subject: =?UTF-8?B?' . base64_encode($this->subject) . '?=' . "\r\n";
        $header .= 'Date: ' . date('D, d M Y H:i:s O') . "\r\n";
        $header .= 'From: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . "\r\n";
        
        if (!$this->reply_to) {
            $header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->sender) . '?= <' . $this->from . '>' . "\r\n";
        } else {
            $header .= 'Reply-To: =?UTF-8?B?' . base64_encode($this->reply_to) . '?= <' . $this->reply_to . '>' . "\r\n";
        }

        $header .= 'Return-Path: ' . $this->from . "\r\n";
        $header .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        $header .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n\r\n";

        // Send the message
        $message = $this->constructMessage($boundary);
        
        fputs($handle, $header . $message . "\r\n.\r\n");
        $this->getResponse($handle, 250);

        // Quit
        fputs($handle, "QUIT\r\n");
        $this->getResponse($handle, 221);

        fclose($handle);
        
        return true;
    }

    private function getResponse($handle, $expected_code = false) {
        $response = '';
        
        while (($line = fgets($handle, 515)) !== false) {
            $this->log("Server response: " . trim($line));
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }

        if ($expected_code && substr($response, 0, 3) != $expected_code) {
            throw new \Exception("Error: Expected $expected_code but got response: $response");
        }

        return $response;
    }

    private function constructMessage($boundary) {
        $message = '';
        
        if (!$this->html) {
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
            $message .= base64_encode($this->text) . "\r\n";
        } else {
            $message .= '--' . $boundary . "\r\n";
            $message .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '_alt"' . "\r\n\r\n";
            $message .= '--' . $boundary . '_alt' . "\r\n";
            $message .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";

            if ($this->text) {
                $message .= base64_encode($this->text) . "\r\n";
            } else {
                $message .= base64_encode('This is a HTML email and your email client software does not support HTML email!') . "\r\n";
            }

            $message .= '--' . $boundary . '_alt' . "\r\n";
            $message .= 'Content-Type: text/html; charset="utf-8"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
            $message .= base64_encode($this->html) . "\r\n";
            $message .= '--' . $boundary . '_alt--' . "\r\n";
        }

        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment)) {
                $handle = fopen($attachment, 'r');
                $content = fread($handle, filesize($attachment));
                fclose($handle);

                $message .= '--' . $boundary . "\r\n";
                $message .= 'Content-Type: application/octet-stream; name="' . basename($attachment) . '"' . "\r\n";
                $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
                $message .= 'Content-Disposition: attachment; filename="' . basename($attachment) . '"' . "\r\n";
                $message .= 'Content-ID: <' . urlencode(basename($attachment)) . '>' . "\r\n";
                $message .= 'X-Attachment-Id: ' . urlencode(basename($attachment)) . "\r\n\r\n";
                $message .= chunk_split(base64_encode($content));
            }
        }

        $message .= '--' . $boundary . '--' . "\r\n";
        
        return $message;
    }
}