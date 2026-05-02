<?php
/**
 * CMS Estático XML - Configuración inicial - Registro del primer admin
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-users.php';

// Si ya existe un admin, redirigir al login
if (adminExists()) {
    redirect('/admin/login');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $displayName = sanitize($_POST['display_name'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($displayName)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (strlen($username) < 3) {
        $error = 'El nombre de usuario debe tener al menos 3 caracteres';
    } elseif (!preg_match('/^[a-z0-9_-]+$/', $username)) {
        $error = 'El nombre de usuario solo puede contener letras minúsculas, números, guiones y guiones bajos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $result = createUser($username, $email, $password, 'admin', $displayName);
        if ($result['success']) {
            redirect('/admin/login', 'Administrador creado correctamente. Inicia sesión con tu correo electrónico.', 'success');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración inicial - CMS</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">📄</div>
            <h1>Configuración inicial</h1>
            <p class="auth-subtitle">Crea la cuenta de administrador para comenzar</p>

            <?php if ($error): ?>
            <div class="flash-message error"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="flash-message success"><?= sanitize($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/registrar">
                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" id="username" name="username" class="form-control"
                           placeholder="ej: admin" required
                           value="<?= sanitize($_POST['username'] ?? '') ?>"
                           pattern="[a-z0-9_-]{3,}" title="Solo letras minúsculas, números, guiones y guiones bajos (mín. 3)">
                    <div class="form-hint">Solo letras minúsculas, números, guiones y guiones bajos</div>
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="admin@ejemplo.com" required
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="display_name">Nombre para mostrar</label>
                    <input type="text" id="display_name" name="display_name" class="form-control"
                           placeholder="Juan Pérez" required
                           value="<?= sanitize($_POST['display_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                           placeholder="Repite la contraseña" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Crear administrador
                </button>
            </form>
        </div>
    </div>
</body>
</html>
