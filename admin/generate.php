<?php
/**
 * CMS Estático XML - Regenerar sitio estático
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/generator.php';

requireAuth();

$isAdminUser = isAdmin();
$flash = getFlash();
$settings = loadSettings();
$currentUser = currentUser();
$message = '';
$messageType = '';

// Handle POST - Regenerate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regenerate') {
    $startTime = microtime(true);

    try {
        regenerateAll();
        $elapsed = round(microtime(true) - $startTime, 2);
        $message = "Sitio regenerado correctamente en {$elapsed} segundos";
        $messageType = 'success';

        // Update last generated time
        $lastGeneratedFile = CACHE_DIR . '/last-generated.txt';
        if (!is_dir(CACHE_DIR)) mkdir(CACHE_DIR, 0755, true);
        file_put_contents($lastGeneratedFile, date('Y-m-d H:i:s'));
    } catch (Exception $e) {
        $message = 'Error al regenerar: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Last generated
$lastGenerated = '';
if (file_exists(CACHE_DIR . '/last-generated.txt')) {
    $lastGenerated = trim(file_get_contents(CACHE_DIR . '/last-generated.txt'));
}

// Count static files
$staticFiles = glob(STATIC_DIR . '/*.html');
$staticCount = $staticFiles ? count($staticFiles) : 0;

// Count data files
$articleFiles = glob(ARTICLES_DIR . '/*.xml');
$articleCount = $articleFiles ? count($articleFiles) : 0;
$categoryFiles = glob(CATEGORIES_DIR . '/*.xml');
$categoryCount = $categoryFiles ? count($categoryFiles) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerar sitio - Panel de Administración</title>
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
                <?php if ($isAdminUser): ?>
                <a href="/admin/usuarios">
                    <span class="nav-icon">👥</span> Usuarios
                </a>
                <a href="/admin/configuracion">
                    <span class="nav-icon">⚙️</span> Configuración
                </a>
                <?php endif; ?>
                <a href="/admin/regenerar" class="active">
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
                    <h2>Regenerar sitio</h2>
                </div>
            </div>

            <div class="admin-content">
                <?php if ($flash): ?>
                <div class="flash-message <?= sanitize($flash['type']) ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="flash-message <?= sanitize($messageType) ?>">
                    <?= sanitize($message) ?>
                </div>
                <?php endif; ?>

                <!-- Info -->
                <div class="dashboard-stats mb-4">
                    <div class="stat-card">
                        <div class="stat-icon blue">📄</div>
                        <div class="stat-value"><?= $staticCount ?></div>
                        <div class="stat-label">Páginas estáticas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green">📝</div>
                        <div class="stat-value"><?= $articleCount ?></div>
                        <div class="stat-label">Artículos en datos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange">🏷️</div>
                        <div class="stat-value"><?= $categoryCount ?></div>
                        <div class="stat-label">Categorías</div>
                    </div>
                </div>

                <!-- Generate -->
                <div class="admin-card">
                    <div class="card-body generate-box">
                        <div class="generate-icon">🔄</div>
                        <h3>Regenerar todas las páginas estáticas</h3>
                        <p>Este proceso generará todas las páginas HTML estáticas del sitio a partir de los datos actuales. Los artículos programados cuya fecha haya pasado se publicarán automáticamente.</p>

                        <?php if ($lastGenerated): ?>
                        <p class="text-sm text-muted mb-3">
                            🕐 Última regeneración: <?= formatDate($lastGenerated) ?>
                        </p>
                        <?php endif; ?>

                        <form method="POST" action="/admin/regenerar">
                            <input type="hidden" name="action" value="regenerate">
                            <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 12px 32px;">
                                🔄 Regenerar sitio completo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- What gets regenerated -->
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h3>📋 Qué se regenera</h3>
                    </div>
                    <div class="card-body">
                        <ul style="list-style: none; padding: 0;">
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">✅ Publicar artículos programados cuya fecha ya pasó</li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">📄 Página principal (con paginación)</li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">📝 Páginas individuales de cada artículo publicado</li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">🏷️ Páginas de cada categoría (con paginación)</li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">🔍 Página de búsqueda</li>
                            <li style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;">❌ Página 404</li>
                            <li style="padding: 8px 0;">🔎 Índice de búsqueda</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
