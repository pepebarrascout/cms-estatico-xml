<?php
/**
 * CMS Estático XML - Crear/Editar artículo
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xml-articles.php';
require_once __DIR__ . '/../includes/xml-categories.php';
require_once __DIR__ . '/../includes/xml-users.php';
require_once __DIR__ . '/../includes/generator.php';

requireAuth();

$currentUser = currentUser();
$isAdminUser = isAdmin();
$flash = getFlash();
$settings = loadSettings();

// Edit mode
$slug = $_GET['slug'] ?? '';
$article = null;
$isEditing = false;

if (!empty($slug)) {
    $article = getArticleBySlug($slug);
    if (!$article) {
        redirect('/admin/articulos', 'Artículo no encontrado', 'error');
    }
    // Check permissions
    if (!$isAdminUser && $article['author'] !== $currentUser['username']) {
        redirect('/admin/articulos', 'No tienes permisos para editar este artículo', 'error');
    }
    $isEditing = true;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $metaDescription = sanitize($_POST['meta_description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    $scheduled = sanitize($_POST['scheduled'] ?? '');
    $categories = $_POST['categories'] ?? [];

    // Author
    if ($isAdminUser && isset($_POST['author'])) {
        $author = sanitize($_POST['author']);
    } else {
        $author = $isEditing ? $article['author'] : $currentUser['username'];
    }

    // Validate
    if (empty($title)) {
        $error = 'El título es obligatorio';
    } elseif (empty($content)) {
        $error = 'El contenido es obligatorio';
    } elseif ($status === 'scheduled' && empty($scheduled)) {
        $error = 'Debes especificar una fecha para la publicación programada';
    } elseif (strtotime($scheduled) <= time() && $status === 'scheduled') {
        $error = 'La fecha programada debe ser posterior a la hora actual';
    } else {
        if ($isEditing) {
            $result = updateArticle($slug, [
                'title' => $title,
                'content' => $content,
                'meta_description' => $metaDescription,
                'status' => $status,
                'scheduled' => $scheduled,
                'categories' => $categories,
            ]);
            if ($result['success']) {
                regenerateArticle($slug);
                redirect('/admin/articulos', 'Artículo actualizado correctamente', 'success');
            }
            $error = $result['message'];
        } else {
            $result = createArticle([
                'title' => $title,
                'content' => $content,
                'meta_description' => $metaDescription,
                'status' => $status,
                'scheduled' => $scheduled,
                'author' => $author,
                'categories' => $categories,
            ]);
            if ($result['success']) {
                regenerateArticle($result['slug']);
                redirect('/admin/articulos', 'Artículo creado correctamente', 'success');
            }
            $error = $result['message'];
        }
    }
}

// Get categories
$allCategories = listCategories();
$users = $isAdminUser ? listUsers() : [];

// Pre-fill form
$formTitle = $article['title'] ?? ($_POST['title'] ?? '');
$formContent = $article['content'] ?? ($_POST['content'] ?? '');
$formMetaDescription = $article['meta_description'] ?? ($_POST['meta_description'] ?? '');
$formStatus = $article['status'] ?? ($_POST['status'] ?? 'draft');
$formScheduled = $article['scheduled'] ?? ($_POST['scheduled'] ?? '');
$formAuthor = $article['author'] ?? ($_POST['author'] ?? $currentUser['username']);
$formCategories = $article['categories'] ?? ($_POST['categories'] ?? []);

// Convert category slugs to objects for JS
$formCategoryObjects = [];
foreach ($formCategories as $catSlug) {
    $catData = getCategoryBySlug($catSlug);
    $formCategoryObjects[] = [
        'slug' => $catSlug,
        'name' => $catData ? $catData['name'] : $catSlug,
    ];
}
$formCategoriesJson = htmlspecialchars(json_encode($formCategoryObjects), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Editar' : 'Nuevo' ?> artículo - Panel de Administración</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde@2/dist/easymde.min.css">
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
                    <h2><?= $isEditing ? 'Editar artículo' : 'Nuevo artículo' ?></h2>
                </div>
                <a href="/admin/articulos" class="btn btn-secondary">← Volver a artículos</a>
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

                <form method="POST" action="<?= $isEditing ? '/admin/articulo/' . sanitize($slug) : '/admin/nuevo-articulo' ?>" id="article-form">
                    <div class="admin-card">
                        <div class="card-header">
                            <h3>Información del artículo</h3>
                            <span id="autosave-indicator" class="text-sm text-muted"></span>
                        </div>
                        <div class="card-body">
                            <div class="admin-form">
                                <!-- Title -->
                                <div class="form-group">
                                    <label for="title">Título *</label>
                                    <input type="text" id="title" name="title" class="form-control"
                                           placeholder="Título del artículo" required
                                           value="<?= sanitize($formTitle) ?>">
                                </div>

                                <?php if ($isAdminUser): ?>
                                <!-- Author (admin only) -->
                                <div class="form-group">
                                    <label for="author">Autor</label>
                                    <select id="author" name="author" class="form-control">
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?= sanitize($u['username']) ?>"
                                                <?= $formAuthor === $u['username'] ? 'selected' : '' ?>>
                                            <?= sanitize($u['display_name'] ?: $u['username']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <!-- Categories -->
                                <div class="form-group">
                                    <label>Categorías</label>

                                    <div id="category-tags" class="category-tags"></div>

                                    <select id="category-select" class="form-control" multiple style="min-height: 80px;">
                                        <?php foreach ($allCategories as $cat): ?>
                                        <option value="<?= sanitize($cat['slug']) ?>"
                                                <?= in_array($cat['slug'], $formCategories) ? 'selected' : '' ?>>
                                            <?= sanitize($cat['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-hint">Mantén presionado Ctrl/Cmd para seleccionar varias</div>

                                    <input type="hidden" id="categories-input" name="categories[]" value="">

                                    <div class="new-category-row">
                                        <input type="text" id="new-category-name" class="form-control"
                                               placeholder="Nombre de nueva categoría">
                                        <button type="button" class="btn btn-secondary"
                                                onclick="CategoryManager.createNew(document.getElementById('new-category-name'))">
                                            Añadir
                                        </button>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="form-group">
                                    <div class="d-flex justify-between items-center mb-2">
                                        <label for="content-editor">Contenido *</label>
                                        <button type="button" id="toggle-preview" class="btn btn-sm btn-secondary">
                                            👁️ Vista previa
                                        </button>
                                    </div>

                                    <div id="editor-area">
                                        <textarea id="content-editor" name="content"
                                                  class="form-control markdown-editor"
                                                  placeholder="Escribe el contenido en Markdown..." required
                                                  style="min-height: 400px; font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace; font-size: 14px; resize: vertical;"><?= sanitize($formContent) ?></textarea>
                                    </div>

                                    <div id="preview-area" class="hidden">
                                        <div id="preview-content" class="markdown-preview"></div>
                                    </div>
                                </div>

                                <!-- Meta Description -->
                                <div class="form-group">
                                    <label for="meta_description">Meta descripción</label>
                                    <input type="text" id="meta_description" name="meta_description"
                                           class="form-control" maxlength="160"
                                           placeholder="Descripción breve para buscadores (máx. 160 caracteres)"
                                           value="<?= sanitize($formMetaDescription) ?>">
                                    <div class="form-hint"><span id="meta-counter">0 / 160</span> caracteres</div>
                                </div>

                                <div class="form-section-title">Publicación</div>

                                <!-- Status -->
                                <div class="form-group">
                                    <label>Estado</label>
                                    <div class="radio-group">
                                        <div class="form-check">
                                            <input type="radio" id="status-published" name="status" value="published"
                                                   <?= $formStatus === 'published' ? 'checked' : '' ?>>
                                            <label for="status-published">✅ Publicado</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" id="status-draft" name="status" value="draft"
                                                   <?= $formStatus === 'draft' ? 'checked' : '' ?>>
                                            <label for="status-draft">📝 Borrador</label>
                                        </div>
                                        <div class="form-check">
                                            <input type="radio" id="status-scheduled" name="status" value="scheduled"
                                                   <?= $formStatus === 'scheduled' ? 'checked' : '' ?>>
                                            <label for="status-scheduled">⏰ Programado</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scheduled Date -->
                                <div class="form-group <?= $formStatus !== 'scheduled' ? 'hidden' : '' ?>" id="scheduled-group">
                                    <label for="scheduled">Fecha de publicación programada</label>
                                    <input type="datetime-local" id="scheduled" name="scheduled"
                                           class="form-control"
                                           value="<?= sanitize($formScheduled) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEditing ? '💾 Guardar cambios' : '📝 Crear artículo' ?>
                        </button>
                        <a href="/admin/articulos" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/easymde@2/dist/easymde.min.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize EasyMDE
            var easyMDEInstance = new EasyMDE({
                element: document.getElementById('content-editor'),
                placeholder: 'Escribe el contenido en Markdown...',
                spellChecker: false,
                autosave: { enabled: false },
                toolbar: [
                    'bold', 'italic', 'strikethrough', '|',
                    'heading', 'heading-smaller', 'heading-bigger', '|',
                    'code', 'quote', 'unordered-list', 'ordered-list', '|',
                    'link', 'image', 'horizontal-rule', '|',
                    'preview', 'side-by-side', 'fullscreen', '|',
                    'guide'
                ],
                minHeight: '350px',
                status: ['lines', 'words'],
            });
            MarkdownPreview.setEasyMDE(easyMDEInstance);

            // Initialize categories
            var selectedCats = <?= $formCategoriesJson ?>;
            CategoryManager.init(selectedCats);

            // Sync multi-select with category manager on initial load
            var select = document.getElementById('category-select');
            var hiddenInput = document.getElementById('categories-input');
            if (hiddenInput) {
                hiddenInput.value = selectedCats.map(function(c) { return c.slug; }).join(',');
            }

            // Handle form submission - ensure categories are sent correctly
            var form = document.getElementById('article-form');
            form.addEventListener('submit', function() {
                var catInput = document.getElementById('categories-input');
                var select = document.getElementById('category-select');
                var selected = Array.from(select.selectedOptions).map(function(opt) { return opt.value; });
                catInput.value = selected.join(',');
            });
        });
    </script>
</body>
</html>
