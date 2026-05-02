<?php
/**
 * 404 Error page template — Minimal Theme
 * Variables:
 *   $categories - array of all category arrays
 *   $settings   - array: site settings
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';

$pageTitle       = 'Pagina no encontrada — ' . $siteName;
$pageDescription = 'La pagina que buscas no existe.';
$canonicalUrl    = '/404';

$themePartialsDir = CMS_ROOT . '/templates/' . ($settings['theme'] ?? 'default') . '/partials';

include $themePartialsDir . '/head.php';
?>

<body>
<?php include $themePartialsDir . '/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <div class="error-page">
            <h1 class="error-code">404</h1>
            <p class="error-message">La pagina que buscas no existe o ha sido movida.</p>
            <a href="/" class="back-home">Volver al inicio</a>
        </div>
    </div>
</main>

<?php include $themePartialsDir . '/footer.php'; ?>
</body>
</html>
