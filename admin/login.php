<?php
/**
 * CMS Estático XML - Login con OTP por email
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Si ya está logueado, redirigir al panel
if (isLoggedIn()) {
    redirect('/admin');
}

// Si no existe admin, redirigir al registro
if (!adminExists()) {
    redirect('/admin/registrar');
}

$flash = getFlash();
$error = '';
$step = 1; // Step 1: email, Step 2: OTP
$email = '';

// POST Step 1: Request OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '1';

    if ($step === '1') {
        $email = sanitize($_POST['email'] ?? '');
        if (empty($email)) {
            $error = 'Introduce tu correo electrónico';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Correo electrónico no válido';
        } else {
            $result = requestOTP($email);
            if ($result['success']) {
                $step = 2;
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($step === '2') {
        $email = sanitize($_POST['email'] ?? '');
        $otp = sanitize($_POST['otp'] ?? '');

        if (empty($otp) || strlen($otp) !== 6) {
            $error = 'Introduce el código completo de 6 dígitos';
            $step = 2;
        } else {
            $result = verifyOTP($otp);
            if ($result['success']) {
                redirect('/admin', 'Sesión iniciada correctamente', 'success');
            } else {
                $error = $result['message'];
                $step = 2;
            }
        }
    }
}

// Check if there's a pending OTP from session
if ($step === 1 && isset($_SESSION['otp']) && time() <= ($_SESSION['otp']['expires'] ?? 0)) {
    $step = 2;
    $email = sanitize($_SESSION['otp']['email'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - CMS</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">📄</div>
            <h1>Iniciar sesión</h1>
            <p class="auth-subtitle">Accede al panel de administración</p>

            <?php if ($flash): ?>
            <div class="flash-message <?= sanitize($flash['type']) ?>">
                <?= sanitize($flash['message']) ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="flash-message error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
            <!-- Step 1: Email -->
            <form method="POST" action="/admin/login">
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="tu@email.com" required autofocus
                           value="<?= sanitize($email) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Enviar código de acceso
                </button>
                <p class="text-sm text-muted text-center mt-3">
                    Te enviaremos un código de verificación de un solo uso a tu correo.
                </p>
            </form>

            <?php else: ?>
            <!-- Step 2: OTP Code -->
            <form method="POST" action="/admin/login" id="otp-form">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="email" value="<?= sanitize($email) ?>">
                <input type="hidden" name="otp" id="otp-value">

                <p class="text-sm text-muted text-center mb-3">
                    Hemos enviado un código a <strong><?= sanitize($email) ?></strong>
                </p>

                <div class="otp-container mb-3">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" autofocus>
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Verificar código
                </button>

                <p class="text-sm text-center mt-3">
                    <a href="/admin/login?resend=1" class="text-muted">Reenviar código</a>
                </p>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Collect OTP digits on form submit
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('otp-form');
            if (form) {
                form.addEventListener('submit', function() {
                    var inputs = form.querySelectorAll('.otp-input');
                    var code = '';
                    inputs.forEach(function(input) { code += input.value; });
                    document.getElementById('otp-value').value = code;
                });
            }
        });
    </script>
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
