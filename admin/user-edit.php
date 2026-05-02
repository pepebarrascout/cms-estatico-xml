<?php
/**
 * CMS Estático XML - Crear/Editar usuario (solo admin)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-users.php';

requireAdmin();

$flash = getFlash();
$settings = loadSettings();
$currentUser = currentUser();

// Edit mode
$username = $_GET['username'] ?? '';
$user = null;
$isEditing = false;

if (!empty($username)) {
    $user = getUserByUsername($username);
    if (!$user) {
        redirect('/admin/usuarios', 'Usuario no encontrado', 'error');
    }
    $isEditing = true;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formUsername = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $displayName = sanitize($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'editor');
    $active = isset($_POST['active']) && $_POST['active'] === '1';

    // Validate
    $error = '';
    if (empty($formUsername) || empty($email) || empty($displayName)) {
        $error = 'Todos los campos obligatorios deben estar completos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido';
    } elseif (!in_array($role, ['admin', 'editor'])) {
        $error = 'Rol no válido';
    } elseif ($isEditing) {
        // Update existing user
        $data = [
            'email' => $email,
            'display_name' => $displayName,
            'role' => $role,
            'active' => $active,
        ];
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                $data['new_password'] = $password;
            }
        }

        if (empty($error)) {
            $result = updateUser($username, $data);
            if ($result['success']) {
                redirect('/admin/usuarios', 'Usuario actualizado correctamente', 'success');
            }
            $error = $result['message'];
        }
    } else {
        // Create new user
        if (empty($password)) {
            $error = 'La contraseña es obligatoria para nuevos usuarios';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } elseif (strlen($formUsername) < 3) {
            $error = 'El nombre de usuario debe tener al menos 3 caracteres';
        } elseif (!preg_match('/^[a-z0-9_-]+$/', $formUsername)) {
            $error = 'El nombre de usuario solo puede contener letras minúsculas, números, guiones y guiones bajos';
        } else {
            $result = createUser($formUsername, $email, $password, $role, $displayName);
            if ($result['success']) {
                redirect('/admin/usuarios', 'Usuario creado correctamente', 'success');
            }
            $error = $result['message'];
        }
    }
}

// Pre-fill form
$formUsername = $user['username'] ?? ($_POST['username'] ?? '');
$formEmail = $user['email'] ?? ($_POST['email'] ?? '');
$formDisplayName = $user['display_name'] ?? ($_POST['display_name'] ?? '');
$formRole = $user['role'] ?? ($_POST['role'] ?? 'editor');
$formActive = isset($user['active']) ? $user['active'] : (isset($_POST['active']) && $_POST['active'] === '1');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Editar' : 'Nuevo' ?> usuario - Panel de Administración</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>📄 <?= sanitize($settings['site_name']) ?></h1>
                <p>Panel de Administración</p>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="/admin/articulos">
                    <span class="nav-icon">📝</span> Artículos
                </a>
                <a href="/admin/nuevo-articulo">
                    <span class="nav-icon">➕</span> Nuevo artículo
                </a>
                <a href="/admin/categorias">
                    <span class="nav-icon">🏷️</span> Categorías
                </a>
                <div class="nav-divider"></div>
                <a href="/admin/usuarios" class="active">
                    <span class="nav-icon">👥</span> Usuarios
                </a>
                <a href="/admin/configuracion">
                    <span class="nav-icon">⚙️</span> Configuración
                </a>
                <a href="/admin/regenerar">
                    <span class="nav-icon">🔄</span> Regenerar sitio
                </a>
                <div class="nav-divider"></div>
                <a href="/" target="_blank">
                    <span class="nav-icon">🌐</span> Ver sitio
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="/admin/salir">🚪 Cerrar sesión</a>
            </div>
        </aside>

        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <main class="admin-main">
            <div class="admin-topbar">
                <div class="d-flex items-center gap-2">
                    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
                    <h2><?= $isEditing ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
                </div>
                <a href="/admin/usuarios" class="btn btn-secondary">← Volver a usuarios</a>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="flash-message error"><?= sanitize($error) ?></div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="card-body">
                        <form method="POST" action="<?= $isEditing ? '/admin/usuario/' . sanitize($username) : '/admin/nuevo-usuario' ?>" class="admin-form">
                            <div class="form-group">
                                <label for="username">Nombre de usuario *</label>
                                <input type="text" id="username" name="username" class="form-control"
                                       placeholder="ej: editor1" required
                                       value="<?= sanitize($formUsername) ?>"
                                       <?= $isEditing ? 'readonly style="background-color: #f1f5f9;"' : 'pattern="[a-z0-9_-]{3,}"' ?>>
                                <?php if ($isEditing): ?>
                                <div class="form-hint">El nombre de usuario no se puede modificar</div>
                                <?php else: ?>
                                <div class="form-hint">Solo letras minúsculas, números, guiones y guiones bajos (mín. 3)</div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="email">Correo electrónico *</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       placeholder="usuario@ejemplo.com" required
                                       value="<?= sanitize($formEmail) ?>">
                            </div>

                            <div class="form-group">
                                <label for="display_name">Nombre para mostrar *</label>
                                <input type="text" id="display_name" name="display_name" class="form-control"
                                       placeholder="Nombre completo" required
                                       value="<?= sanitize($formDisplayName) ?>">
                            </div>

                            <div class="form-group">
                                <label for="password">Contraseña <?= $isEditing ? '(dejar vacío para no cambiar)' : '*' ?></label>
                                <input type="password" id="password" name="password" class="form-control"
                                       placeholder="<?= $isEditing ? 'Nueva contraseña (opcional)' : 'Mínimo 6 caracteres' ?>"
                                       <?= $isEditing ? '' : 'required minlength="6"' ?>>
                            </div>

                            <div class="form-group">
                                <label>Rol</label>
                                <div class="radio-group">
                                    <div class="form-check">
                                        <input type="radio" id="role-admin" name="role" value="admin"
                                               <?= $formRole === 'admin' ? 'checked' : '' ?>>
                                        <label for="role-admin">🔑 Administrador</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="role-editor" name="role" value="editor"
                                               <?= $formRole === 'editor' ? 'checked' : '' ?>>
                                        <label for="role-editor">✏️ Editor</label>
                                    </div>
                                </div>
                            </div>

                            <?php if ($isEditing): ?>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" id="active" name="active" value="1"
                                           <?= $formActive ? 'checked' : '' ?>>
                                    <label for="active">Cuenta activa</label>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $isEditing ? '💾 Guardar cambios' : '➕ Crear usuario' ?>
                                </button>
                                <a href="/admin/usuarios" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
