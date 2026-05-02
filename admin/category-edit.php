<?php
/**
 * CMS Estático XML - Crear/Editar categoría
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-categories.php';
require_once __DIR__ . '/../includes/generator.php';

requireAuth();

$currentUser = currentUser();
$isAdminUser = isAdmin();
$flash = getFlash();
$settings = loadSettings();

// Edit mode
$slug = $_GET['slug'] ?? '';
$category = null;
$isEditing = false;

if (!empty($slug)) {
    $category = getCategoryBySlug($slug);
    if (!$category) {
        redirect('/admin/categorias', 'Categoría no encontrada', 'error');
    }
    $isEditing = true;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'El nombre de la categoría es obligatorio';
    } else {
        if ($isEditing) {
            $result = updateCategory($slug, $name, $description);
            if ($result['success']) {
                regenerateAll();
                redirect('/admin/categorias', 'Categoría actualizada correctamente', 'success');
            }
            $error = $result['message'];
        } else {
            $result = createCategory($name, $description);
            if ($result['success']) {
                regenerateAll();
                redirect('/admin/categorias', 'Categoría creada correctamente', 'success');
            }
            $error = $result['message'];
        }
    }
}

// Pre-fill form
$formName = $category['name'] ?? ($_POST['name'] ?? '');
$formDescription = $category['description'] ?? ($_POST['description'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Editar' : 'Nueva' ?> categoría - Panel de Administración</title>
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
                    <h2><?= $isEditing ? 'Editar categoría' : 'Nueva categoría' ?></h2>
                </div>
                <a href="/admin/categorias" class="btn btn-secondary">← Volver a categorías</a>
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
                        <form method="POST" action="<?= $isEditing ? '/admin/categoria/' . sanitize($slug) : '/admin/nueva-categoria' ?>" class="admin-form">
                            <div class="form-group">
                                <label for="name">Nombre de la categoría *</label>
                                <input type="text" id="name" name="name" class="form-control"
                                       placeholder="Ej: Tecnología, Tutoriales..." required
                                       value="<?= sanitize($formName) ?>">
                                <div class="form-hint">El slug se generará automáticamente a partir del nombre</div>
                            </div>

                            <div class="form-group">
                                <label for="description">Descripción</label>
                                <textarea id="description" name="description" class="form-control"
                                          placeholder="Descripción breve de la categoría"
                                          rows="3"><?= sanitize($formDescription) ?></textarea>
                            </div>

                            <?php if ($isEditing): ?>
                            <div class="form-group">
                                <label>Slug</label>
                                <input type="text" class="form-control" value="<?= sanitize($category['slug']) ?>" disabled>
                                <div class="form-hint">El slug no se puede modificar después de la creación</div>
                            </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?= $isEditing ? '💾 Guardar cambios' : '➕ Crear categoría' ?>
                                </button>
                                <a href="/admin/categorias" class="btn btn-secondary">Cancelar</a>
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
