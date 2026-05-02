<?php
/**
 * Site header — Minimal Theme
 * Variables:
 *   $settings   - array: site_name, site_url, ...
 *   $categories - array of arrays: each with 'name' and 'slug'
 */
if (!isset($settings)) { $settings = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';
?>
<header class="site-header" role="banner">
    <div class="header-inner">
        <a href="/" class="site-logo" aria-label="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <nav class="main-nav" role="navigation" aria-label="Navegacion principal">
            <ul class="nav-list">
                <li><a href="/">Inicio</a></li>
                <?php foreach ($categories as $cat): ?>
                <li><a href="/categoria/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
                <li><a href="/buscar">Buscar</a></li>
            </ul>
        </nav>
    </div>
</header>
