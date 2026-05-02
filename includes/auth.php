<?php
/**
 * CMS Estático XML - Autenticación con OTP por email
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/xml-users.php';

/**
 * Iniciar sesión si no existe
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verificar si hay sesión activa
 */
function isLoggedIn(): bool {
    initSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtener usuario actual
 */
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return getUserByUsername($_SESSION['user_username'] ?? '');
}

/**
 * Verificar si es admin
 */
function isAdmin(): bool {
    $user = currentUser();
    return $user && ($user['role'] === 'admin');
}

/**
 * Requiere autenticación
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        redirect('/admin/login', 'Debes iniciar sesión', 'error');
    }
}

/**
 * Requiere rol de admin
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        redirect('/admin', 'No tienes permisos de administrador', 'error');
    }
}

/**
 * Solicitar código OTP
 * Retorna true si se envió correctamente, false si el email no está registrado
 */
function requestOTP(string $email): array {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return ['success' => false, 'message' => 'El correo electrónico no está registrado'];
    }
    
    if (!$user['active']) {
        return ['success' => false, 'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador'];
    }
    
    $otp = generateOTP();
    
    // Guardar OTP con expiración (15 minutos)
    $otpData = [
        'code' => $otp,
        'email' => $email,
        'expires' => time() + 900, // 15 minutos
        'attempts' => 0,
    ];
    
    initSession();
    $_SESSION['otp'] = $otpData;
    
    // Enviar correo
    $settings = loadSettings();
    $sent = sendOTPEmail($user['email'], $user['username'], $otp, $settings);
    
    if ($sent) {
        return ['success' => true, 'message' => 'Código enviado a tu correo electrónico'];
    } else {
        return ['success' => false, 'message' => 'Error al enviar el código. Intenta de nuevo'];
    }
}

/**
 * Verificar código OTP
 */
function verifyOTP(string $code): array {
    initSession();
    
    if (!isset($_SESSION['otp'])) {
        return ['success' => false, 'message' => 'No hay código pendiente. Solicita uno nuevo'];
    }
    
    $otpData = $_SESSION['otp'];
    
    // Verificar expiración
    if (time() > $otpData['expires']) {
        unset($_SESSION['otp']);
        return ['success' => false, 'message' => 'El código ha expirado. Solicita uno nuevo'];
    }
    
    // Verificar intentos (máximo 5)
    if ($otpData['attempts'] >= 5) {
        unset($_SESSION['otp']);
        return ['success' => false, 'message' => 'Demasiados intentos. Solicita un nuevo código'];
    }
    
    // Incrementar intentos
    $_SESSION['otp']['attempts']++;
    
    // Verificar código
    if ($code === $otpData['code']) {
        // Código correcto - iniciar sesión
        $user = getUserByEmail($otpData['email']);
        if ($user) {
            $_SESSION['user_id'] = $user['username'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            unset($_SESSION['otp']);
            return ['success' => true, 'message' => 'Sesión iniciada correctamente'];
        }
    }
    
    $remaining = 5 - $otpData['attempts'];
    return ['success' => false, 'message' => 'Código incorrecto. Te quedan ' . $remaining . ' intentos'];
}

/**
 * Cerrar sesión
 */
function logout(): void {
    initSession();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Verificar si ya existe un admin registrado
 */
function adminExists(): bool {
    $files = glob(USERS_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml && (string)$xml->role === 'admin') {
            return true;
        }
    }
    return false;
}
