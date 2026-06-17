<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendMail($to, $subject, $body, $replyTo = '', &$error = '') {
    $mail = new PHPMailer(true);
    try {
        $smtp_host = getSetting('smtp_host');
        if ($smtp_host) {
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->Port = (int)(getSetting('smtp_port') ?: 587);
            $mail->SMTPAuth = true;
            $mail->Username = getSetting('smtp_username');
            $mail->Password = getSetting('smtp_password');
            $enc = getSetting('smtp_encryption');
            if ($enc === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            elseif ($enc === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(getSetting('smtp_from_email') ?: getSetting('site_email'), getSetting('smtp_from_name') ?: getSetting('site_name'));
        $mail->addAddress($to);
        if ($replyTo) $mail->addReplyTo($replyTo);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = $e->getMessage();
        return false;
    }
}
