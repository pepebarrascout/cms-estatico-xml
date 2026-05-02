<?php
/**
 * 404 Error page template
 * Variables:
 *   $categories - array of all category arrays
 *   $settings   - array: site settings
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';

$pageTitle       = 'Pagina no encontrada - ' . $siteName;
$pageDescription = 'La pagina que buscas no existe.';
$canonicalUrl    = '/404';

include TEMPLATES_DIR . '/partials/head.php';
?>

<body>
<?php include TEMPLATES_DIR . '/partials/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <div class="error-page">
            <h1 class="error-code">404</h1>
            <h2 class="error-title">Pagina no encontrada</h2>
            <p class="error-message">La pagina que buscas no existe o ha sido movida.</p>
            <a href="/" class="btn-home">Volver al inicio</a>
        </div>
    </div>
</main>

<?php include TEMPLATES_DIR . '/partials/footer.php'; ?>
</body>
</html>
