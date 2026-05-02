<?php
/**
 * CMS Estático XML - Gestión de artículos (lista)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-articles.php';
require_once __DIR__ . '/../includes/xml-categories.php';
require_once __DIR__ . '/../includes/generator.php';

requireAuth();

$currentUser = currentUser();
$isAdminUser = isAdmin();
$flash = getFlash();
$settings = loadSettings();

// Handle DELETE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $slug = sanitize($_POST['slug'] ?? '');
    if ($slug) {
        $article = getArticleBySlug($slug);
        if ($article) {
            // Only admin or article author can delete
            if ($isAdminUser || $article['author'] === $currentUser['username']) {
                deleteArticle($slug);
                regenerateArticle($slug);
                redirect('/admin/articulos', 'Artículo eliminado correctamente', 'success');
            } else {
                redirect('/admin/articulos', 'No tienes permisos para eliminar este artículo', 'error');
            }
        }
    }
    redirect('/admin/articulos', 'Artículo no encontrado', 'error');
}

// Filters
$status = sanitize($_GET['status'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

// If not admin, force filter by current user
$author = null;
if (!$isAdminUser) {
    $author = $currentUser['username'];
}

$result = listArticles($status, $page, $perPage, null, $author);
$articles = $result['articles'];
$pagination = $result['pagination'];
$counts = countArticles();

// Status labels
$statusLabels = [
    'all' => 'Todos',
    'published' => 'Publicados',
    'draft' => 'Borradores',
    'scheduled' => 'Programados',
];

// Get all categories for display
$allCategories = listCategories();
$categoryMap = [];
foreach ($allCategories as $cat) {
    $categoryMap[$cat['slug']] = $cat['name'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artículos - Panel de Administración</title>
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
                <a href="/admin/articulos" class="active">
                    <span class="nav-icon">📝</span> Artículos
                </a>
                <a href="/admin/nuevo-articulo">
                    <span class="nav-icon">➕</span> Nuevo artículo
                </a>
                <a href="/admin/categorias">
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
                    <h2>Artículos</h2>
                </div>
                <a href="/admin/nuevo-articulo" class="btn btn-primary">➕ Nuevo artículo</a>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="filter-bar">
                    <?php foreach ($statusLabels as $key => $label): ?>
                    <a href="/admin/articulos?status=<?= $key ?>"
                       class="filter-btn <?= $status === $key ? 'active' : '' ?>">
                        <?= $label ?> (<?= $counts[$key] ?? 0 ?>)
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Articles Table -->
                <div class="admin-card">
                    <div class="card-body" style="padding: 0; overflow-x: auto;">
                        <?php if (empty($articles)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <h3>No se encontraron artículos</h3>
                            <p>No hay artículos con el estado seleccionado</p>
                            <a href="/admin/nuevo-articulo" class="btn btn-primary">Crear artículo</a>
                        </div>
                        <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Estado</th>
                                    <th>Autor</th>
                                    <th>Categorías</th>
                                    <th>Fecha</th>
                                    <th style="text-align: right;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td>
                                        <a href="/admin/articulo/<?= sanitize($article['slug']) ?>" style="font-weight: 500;">
                                            <?= sanitize($article['title']) ?>
                                        </a>
                                        <?php if ($article['scheduled'] && $article['status'] === 'scheduled'): ?>
                                        <br><span class="text-sm text-muted">Programado: <?= formatDate($article['scheduled']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= sanitize($article['status']) ?>">
                                            <?= $article['status'] === 'published' ? 'Publicado' : ($article['status'] === 'draft' ? 'Borrador' : 'Programado') ?>
                                        </span>
                                    </td>
                                    <td><?= sanitize($article['author']) ?></td>
                                    <td>
                                        <?php foreach ($article['categories'] as $catSlug): ?>
                                        <span class="badge badge-draft"><?= sanitize($categoryMap[$catSlug] ?? $catSlug) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="text-sm text-muted"><?= formatDate($article['created']) ?></td>
                                    <td>
                                        <div class="action-buttons" style="justify-content: flex-end;">
                                            <a href="/admin/articulo/<?= sanitize($article['slug']) ?>" class="btn btn-sm btn-secondary">✏️ Editar</a>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    data-delete="¿Estás seguro de que deseas eliminar el artículo &quot;<?= sanitize(addslashes($article['title'])) ?>&quot;? Esta acción no se puede deshacer."
                                                    data-slug="<?= sanitize($article['slug']) ?>">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>

                    <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="card-footer">
                        <div class="pagination">
                            <?php if ($pagination['has_prev']): ?>
                            <a href="/admin/articulos?status=<?= $status ?>&page=<?= $pagination['prev_page'] ?>" class="btn btn-sm btn-secondary">← Anterior</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++):
                                if ($i === 1 || $i === $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                                <a href="/admin/articulos?status=<?= $status ?>&page=<?= $i ?>"
                                   class="<?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                                <?php elseif (abs($i - $pagination['current_page']) === 3): ?>
                                <span>...</span>
                                <?php endif;
                            endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                            <a href="/admin/articulos?status=<?= $status ?>&page=<?= $pagination['next_page'] ?>" class="btn btn-sm btn-secondary">Siguiente →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
