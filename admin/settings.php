<?php
/**
 * CMS Estático XML - Configuración del sitio (solo admin)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

requireAdmin();

$flash = getFlash();
$settings = loadSettings();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newSettings = [
        'site_name' => sanitize($_POST['site_name'] ?? ''),
        'site_description' => sanitize($_POST['site_description'] ?? ''),
        'site_url' => sanitize($_POST['site_url'] ?? ''),
        'articles_per_page' => sanitize($_POST['articles_per_page'] ?? '10'),
        'words_preview' => sanitize($_POST['words_preview'] ?? '50'),
        'meta_keywords' => sanitize($_POST['meta_keywords'] ?? ''),
        'meta_author' => sanitize($_POST['meta_author'] ?? ''),
        'smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
        'smtp_port' => sanitize($_POST['smtp_port'] ?? '587'),
        'smtp_user' => sanitize($_POST['smtp_user'] ?? ''),
        'smtp_pass' => $_POST['smtp_pass'] ?? '',
        'smtp_encryption' => sanitize($_POST['smtp_encryption'] ?? 'tls'),
    ];

    // If SMTP password is empty, keep the old one
    if (empty($newSettings['smtp_pass'])) {
        $newSettings['smtp_pass'] = $settings['smtp_pass'] ?? '';
    }

    if (empty($newSettings['site_name'])) {
        $error = 'El nombre del sitio es obligatorio';
    } elseif (!is_numeric($newSettings['articles_per_page']) || $newSettings['articles_per_page'] < 1) {
        $error = 'El número de artículos por página debe ser un número positivo';
    } elseif (!is_numeric($newSettings['words_preview']) || $newSettings['words_preview'] < 1) {
        $error = 'El número de palabras del resumen debe ser un número positivo';
    } else {
        if (saveSettings($newSettings)) {
            redirect('/admin/configuracion', 'Configuración guardada correctamente', 'success');
        }
        $error = 'Error al guardar la configuración';
    }
}

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['test_email'] ?? '') === '1') {
    $testTo = sanitize($_POST['test_email_to'] ?? $settings['smtp_user']);
    if (!empty($testTo) && filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $sent = sendMail(
            $testTo,
            '[' . $settings['site_name'] . '] Prueba de correo SMTP',
            '<p>Este es un correo de prueba desde el panel de administración de <strong>' . htmlspecialchars($settings['site_name']) . '</strong>.</p><p>Si recibes este mensaje, la configuración SMTP es correcta.</p>',
            $settings
        );
        if ($sent) {
            redirect('/admin/configuracion', 'Correo de prueba enviado correctamente a ' . $testTo, 'success');
        }
        redirect('/admin/configuracion', 'Error al enviar el correo de prueba. Verifica la configuración SMTP.', 'error');
    }
    redirect('/admin/configuracion', 'Dirección de correo no válida para la prueba', 'error');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Panel de Administración</title>
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
                <a href="/admin/usuarios">
                    <span class="nav-icon">👥</span> Usuarios
                </a>
                <a href="/admin/configuracion" class="active">
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
                    <h2>Configuración</h2>
                </div>
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

                <form method="POST" action="/admin/configuracion">
                    <!-- General -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h3>🌐 Sitio</h3>
                        </div>
                        <div class="card-body">
                            <div class="admin-form">
                                <div class="form-group">
                                    <label for="site_name">Nombre del sitio *</label>
                                    <input type="text" id="site_name" name="site_name" class="form-control"
                                           required value="<?= sanitize($settings['site_name']) ?>">
                                </div>

                                <div class="form-group">
                                    <label for="site_description">Descripción del sitio</label>
                                    <textarea id="site_description" name="site_description" class="form-control"
                                              rows="2"><?= sanitize($settings['site_description']) ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="site_url">URL del sitio</label>
                                        <input type="url" id="site_url" name="site_url" class="form-control"
                                               placeholder="https://ejemplo.com"
                                               value="<?= sanitize($settings['site_url']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="articles_per_page">Artículos por página</label>
                                        <input type="number" id="articles_per_page" name="articles_per_page"
                                               class="form-control" min="1" max="100"
                                               value="<?= sanitize($settings['articles_per_page']) ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="words_preview">Palabras en resumen</label>
                                        <input type="number" id="words_preview" name="words_preview"
                                               class="form-control" min="10" max="500"
                                               value="<?= sanitize($settings['words_preview']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="meta_author">Autor por defecto (meta)</label>
                                        <input type="text" id="meta_author" name="meta_author" class="form-control"
                                               value="<?= sanitize($settings['meta_author']) ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="meta_keywords">Palabras clave (meta, separadas por coma)</label>
                                    <input type="text" id="meta_keywords" name="meta_keywords" class="form-control"
                                           placeholder="blog, tecnología, tutoriales"
                                           value="<?= sanitize($settings['meta_keywords']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMTP -->
                    <div class="admin-card">
                        <div class="card-header">
                            <h3>📧 Configuración SMTP</h3>
                        </div>
                        <div class="card-body">
                            <div class="admin-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="smtp_host">Servidor SMTP</label>
                                        <input type="text" id="smtp_host" name="smtp_host" class="form-control"
                                               placeholder="smtp.gmail.com"
                                               value="<?= sanitize($settings['smtp_host']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="smtp_port">Puerto</label>
                                        <input type="number" id="smtp_port" name="smtp_port" class="form-control"
                                               placeholder="587" min="1" max="65535"
                                               value="<?= sanitize($settings['smtp_port']) ?>">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="smtp_user">Usuario SMTP</label>
                                        <input type="text" id="smtp_user" name="smtp_user" class="form-control"
                                               placeholder="tu@email.com"
                                               value="<?= sanitize($settings['smtp_user']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="smtp_pass">Contraseña SMTP</label>
                                        <input type="password" id="smtp_pass" name="smtp_pass" class="form-control"
                                               placeholder="Dejar vacío para no cambiar">
                                        <div class="form-hint">Dejar vacío para mantener la contraseña actual</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="smtp_encryption">Cifrado</label>
                                    <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                                        <option value="tls" <?= $settings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= $settings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        <option value="starttls" <?= $settings['smtp_encryption'] === 'starttls' ? 'selected' : '' ?>>STARTTLS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save" class="btn btn-primary">💾 Guardar configuración</button>
                    </div>
                </form>

                <!-- Test Email -->
                <div class="admin-card mt-4">
                    <div class="card-header">
                        <h3>🧪 Probar correo SMTP</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/admin/configuracion" class="d-flex gap-2 items-center flex-wrap">
                            <input type="hidden" name="test_email" value="1">
                            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                                <input type="email" name="test_email_to" class="form-control"
                                       placeholder="Correo destino" required
                                       value="<?= sanitize($settings['smtp_user']) ?>">
                            </div>
                            <button type="submit" class="btn btn-secondary">📤 Enviar correo de prueba</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
