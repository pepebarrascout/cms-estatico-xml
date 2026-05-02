<?php
/**
 * CMS Estático XML - Verificación OTP (endpoint POST)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect('/admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/login');
}

$email = sanitize($_POST['email'] ?? '');
$otp = sanitize($_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    redirect('/admin/login', 'Datos incompletos. Inténtalo de nuevo.', 'error');
}

$result = verifyOTP($otp);

if ($result['success']) {
    redirect('/admin', 'Sesión iniciada correctamente', 'success');
} else {
    redirect('/admin/login', $result['message'], 'error');
}
