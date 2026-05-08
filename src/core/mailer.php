<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';

function portalMailConfig(): array
{
    $configPath = __DIR__ . '/../config/mail.php';
    if (!is_file($configPath)) {
        return [];
    }

    $config = require $configPath;
    return is_array($config) ? $config : [];
}

function sendPortalEmail(string $toEmail, string $subject, string $textBody, string $htmlBody = ''): bool
{
    $config = portalMailConfig();
    $host = trim((string) ($config['host'] ?? ''));
    $username = trim((string) ($config['username'] ?? ''));
    $password = (string) ($config['password'] ?? '');
    $fromEmail = trim((string) ($config['from_email'] ?? 'no-reply@localhost'));
    $fromName = trim((string) ($config['from_name'] ?? 'School Records Database'));

    if ($host === '' || $username === '' || $password === '') {
        error_log('Email not sent: SMTP settings are incomplete in src/config/mail.php');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->Port = (int) ($config['port'] ?? 587);

        $encryption = strtolower(trim((string) ($config['encryption'] ?? 'tls')));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        if (!empty($config['debug'])) {
            $mail->SMTPDebug = 2;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        $mail->Subject = $subject;

        if ($htmlBody !== '') {
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;
        } else {
            $mail->isHTML(false);
            $mail->Body = $textBody;
        }

        return $mail->send();
    } catch (Exception $e) {
        error_log('Email send failed: ' . $mail->ErrorInfo);
        return false;
    }
}

