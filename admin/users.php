<?php
/**
 * CMS Estático XML - Gestión de usuarios (solo admin)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-users.php';

requireAdmin();

$flash = getFlash();
$settings = loadSettings();
$currentUser = currentUser();
$users = listUsers();

// Handle DELETE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $username = sanitize($_POST['username'] ?? '');
    if ($username) {
        // Cannot delete self
        if ($username === $currentUser['username']) {
            redirect('/admin/usuarios', 'No puedes eliminar tu propia cuenta', 'error');
        }
        $user = getUserByUsername($username);
        if ($user) {
            deleteUser($username);
            redirect('/admin/usuarios', 'Usuario eliminado correctamente', 'success');
        }
    }
    redirect('/admin/usuarios', 'Usuario no encontrado', 'error');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Panel de Administración</title>
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
                    <h2>Usuarios</h2>
                </div>
                <a href="/admin/nuevo-usuario" class="btn btn-primary">➕ Nuevo usuario</a>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">👥</div>
                            <h3>Sin usuarios</h3>
                            <p>No hay usuarios registrados</p>
                            <a href="/admin/nuevo-usuario" class="btn btn-primary">Crear usuario</a>
                        </div>
                        <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th>Nombre</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td style="font-weight: 500;">
                                        <?= sanitize($u['username']) ?>
                                        <?php if ($u['username'] === $currentUser['username']): ?>
                                        <span class="text-sm text-muted">(tú)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-sm"><?= sanitize($u['email']) ?></td>
                                    <td class="text-sm"><?= sanitize($u['display_name'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge badge-<?= sanitize($u['role']) ?>">
                                            <?= $u['role'] === 'admin' ? 'Admin' : 'Editor' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $u['active'] ? 'active' : 'inactive' ?>">
                                            <?= $u['active'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td class="text-sm text-muted"><?= formatDate($u['created']) ?></td>
                                    <td>
                                        <div class="action-buttons" style="justify-content: flex-end;">
                                            <a href="/admin/usuario/<?= sanitize($u['username']) ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                            <?php if ($u['username'] !== $currentUser['username']): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    data-delete="¿Estás seguro de que deseas eliminar al usuario &quot;<?= sanitize(addslashes($u['display_name'] ?: $u['username'])) ?>&quot;? Esta acción no se puede deshacer."
                                                    data-slug="<?= sanitize($u['username']) ?>">
                                                🗑️
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
