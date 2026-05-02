<?php
/**
 * CMS Estático XML - Gestión de categorías (lista)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-categories.php';
require_once __DIR__ . '/../includes/xml-articles.php';
require_once __DIR__ . '/../includes/generator.php';

requireAuth();

$currentUser = currentUser();
$isAdminUser = isAdmin();
$flash = getFlash();
$settings = loadSettings();

// Handle DELETE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!$isAdminUser) {
        redirect('/admin/categorias', 'Solo los administradores pueden eliminar categorías', 'error');
    }
    $slug = sanitize($_POST['slug'] ?? '');
    if ($slug) {
        $cat = getCategoryBySlug($slug);
        if ($cat) {
            deleteCategory($slug);
            regenerateAll();
            redirect('/admin/categorias', 'Categoría eliminada correctamente', 'success');
        }
    }
    redirect('/admin/categorias', 'Categoría no encontrada', 'error');
}

// List categories
$categories = listCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - Panel de Administración</title>
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
                <a href="/admin/categorias" class="active">
                    <span class="nav-icon">🏷️</span> Categorías
                </a>
                <div class="nav-divider"></div>
                <?php if ($isAdminUser): ?>
                <a href="/admin/usuarios">
                    <span class="nav-icon">👥</span> Usuarios
                </a>
                <a href="/admin/configuracion">
                    <span class="nav-icon">⚙️</span> Configuración
                </a>
                <?php endif; ?>
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
                    <h2>Categorías</h2>
                </div>
                <a href="/admin/nueva-categoria" class="btn btn-primary">➕ Nueva categoría</a>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <div class="admin-card">
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">🏷️</div>
                            <h3>Sin categorías</h3>
                            <p>Crea tu primera categoría para organizar los artículos</p>
                            <a href="/admin/nueva-categoria" class="btn btn-primary">Crear categoría</a>
                        </div>
                        <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Descripción</th>
                                    <th>Artículos</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= sanitize($cat['name']) ?></td>
                                    <td class="text-sm text-muted"><?= sanitize($cat['slug']) ?></td>
                                    <td class="text-sm text-muted">
                                        <?= sanitize(mb_strimwidth($cat['description'], 0, 80, '...')) ?>
                                    </td>
                                    <td><span class="badge badge-draft"><?= $cat['article_count'] ?></span></td>
                                    <td>
                                        <div class="action-buttons" style="justify-content: flex-end;">
                                            <a href="/admin/categoria/<?= sanitize($cat['slug']) ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                            <?php if ($isAdminUser): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    data-delete="¿Estás seguro de que deseas eliminar la categoría &quot;<?= sanitize(addslashes($cat['name'])) ?>&quot;? Los artículos perderán esta categoría."
                                                    data-slug="<?= sanitize($cat['slug']) ?>">
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
