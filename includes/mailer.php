<?php
/**
 * CMS Estático XML - Envío de correo SMTP con PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cargar PHPMailer
require_once CMS_ROOT . '/vendor/autoload.php';

/**
 * Enviar correo SMTP
 */
function sendMail(string $to, string $subject, string $body, array $settings): bool {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->Port = (int)$settings['smtp_port'];
        $mail->SMTPSecure = $settings['smtp_encryption'] ?: 'tls';
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'];
        $mail->Password = $settings['smtp_pass'];
        
        // Timeout bajo para no bloquear
        $mail->Timeout = 10;
        
        // Remitente
        $siteName = $settings['site_name'] ?? 'CMS';
        $mail->setFrom($settings['smtp_user'], $siteName);
        $mail->addAddress($to);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->CharSet = 'UTF-8';
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Error envío correo: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Enviar código OTP por email
 */
function sendOTPEmail(string $email, string $username, string $code, array $settings): bool {
    $siteName = htmlspecialchars($settings['site_name'] ?? 'CMS');
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0;'>
            <h1 style='margin: 0;'>$siteName</h1>
            <p style='margin: 5px 0 0;'>Código de verificación</p>
        </div>
        <div style='background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; border-top: none;'>
            <p style='font-size: 16px;'>Hola, <strong>" . htmlspecialchars($username) . "</strong></p>
            <p style='font-size: 16px;'>Tu código de verificación es:</p>
            <div style='background: #ffffff; border: 2px dashed #2563eb; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;'>
                <span style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2563eb;'>$code</span>
            </div>
            <p style='color: #64748b; font-size: 14px;'>Este código expira en 15 minutos. Si no solicitaste este código, ignora este mensaje.</p>
        </div>
        <div style='background: #f1f5f9; padding: 15px; border-radius: 0 0 8px 8px; text-align: center; font-size: 12px; color: #94a3b8;'>
            $siteName &mdash; CMS Estático
        </div>
    </div>";
    
    return sendMail($email, "[$siteName] Código de verificación: $code", $body, $settings);
}
