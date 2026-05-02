<?php
/**
 * CMS Estático XML - Panel de Administración - Dashboard
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-articles.php';
require_once __DIR__ . '/../includes/xml-categories.php';
require_once __DIR__ . '/../includes/xml-users.php';

requireAuth();

$currentUser = currentUser();
$isAdminUser = isAdmin();

// Stats
$articleCounts = countArticles();
$categories = listCategories();
$users = listUsers();

// Recent articles (last 5)
$recentResult = listArticles('all', 1, 5);
$recentArticles = $recentResult['articles'];

// Last generated
$lastGenerated = '';
if (file_exists(CACHE_DIR . '/last-generated.txt')) {
    $lastGenerated = trim(file_get_contents(CACHE_DIR . '/last-generated.txt'));
}

$flash = getFlash();
$settings = loadSettings();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?= sanitize($settings['site_name']) ?></title>
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
                <a href="/admin" class="active">
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

        <!-- Overlay for mobile -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <div class="d-flex items-center gap-2">
                    <button class="sidebar-toggle" id="sidebar-toggle">☰</button>
                    <h2>Dashboard</h2>
                </div>
                <div class="topbar-user">
                    <div class="user-avatar"><?= mb_strtoupper(mb_substr($currentUser['display_name'] ?? $currentUser['username'], 0, 1)) ?></div>
                    <span><?= sanitize($currentUser['display_name'] ?? $currentUser['username']) ?></span>
                </div>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon blue">📝</div>
                        <div class="stat-value"><?= $articleCounts['all'] ?></div>
                        <div class="stat-label">Total artículos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">✅</div>
                        <div class="stat-value"><?= $articleCounts['published'] ?></div>
                        <div class="stat-label">Publicados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon gray">📝</div>
                        <div class="stat-value"><?= $articleCounts['draft'] ?></div>
                        <div class="stat-label">Borradores</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple">⏰</div>
                        <div class="stat-value"><?= $articleCounts['scheduled'] ?></div>
                        <div class="stat-label">Programados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">🏷️</div>
                        <div class="stat-value"><?= count($categories) ?></div>
                        <div class="stat-label">Categorías</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red">👥</div>
                        <div class="stat-value"><?= count($users) ?></div>
                        <div class="stat-label">Usuarios</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <a href="/admin/nuevo-articulo" class="btn btn-primary">➕ Nuevo artículo</a>
                    <a href="/admin/nueva-categoria" class="btn btn-secondary">🏷️ Nueva categoría</a>
                    <?php if ($isAdminUser): ?>
                    <a href="/admin/nuevo-usuario" class="btn btn-secondary">👤 Nuevo usuario</a>
                    <a href="/admin/regenerar" class="btn btn-success">🔄 Regenerar sitio</a>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <!-- Recent Articles -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h3>📝 Artículos recientes</h3>
                            <a href="/admin/articulos" class="btn btn-sm btn-secondary">Ver todos</a>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($recentArticles)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📝</div>
                                <h3>Sin artículos</h3>
                                <p>Crea tu primer artículo para comenzar</p>
                                <a href="/admin/nuevo-articulo" class="btn btn-primary">Crear artículo</a>
                            </div>
                            <?php else: ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentArticles as $article): ?>
                                    <tr>
                                        <td>
                                            <a href="/admin/articulo/<?= sanitize($article['slug']) ?>">
                                                <?= sanitize($article['title']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= sanitize($article['status']) ?>">
                                                <?= $article['status'] === 'published' ? 'Publicado' : ($article['status'] === 'draft' ? 'Borrador' : 'Programado') ?>
                                            </span>
                                        </td>
                                        <td class="text-sm text-muted"><?= formatDate($article['created']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Categories & Info -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h3>🏷️ Categorías</h3>
                            <a href="/admin/categorias" class="btn btn-sm btn-secondary">Ver todas</a>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($categories)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">🏷️</div>
                                <h3>Sin categorías</h3>
                                <p>Crea categorías para organizar tus artículos</p>
                                <a href="/admin/nueva-categoria" class="btn btn-primary">Crear categoría</a>
                            </div>
                            <?php else: ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Artículos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                                    <tr>
                                        <td>
                                            <a href="/admin/categoria/<?= sanitize($cat['slug']) ?>">
                                                <?= sanitize($cat['name']) ?>
                                            </a>
                                        </td>
                                        <td><?= $cat['article_count'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <?php if ($lastGenerated): ?>
                <div class="admin-card mt-4">
                    <div class="card-body text-sm text-muted text-center">
                        🕐 Última regeneración: <?= formatDate($lastGenerated) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
