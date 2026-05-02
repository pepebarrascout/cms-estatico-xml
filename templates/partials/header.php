<?php
/**
 * Site header with navigation and search form
 * Variables:
 *   $settings   - array: site_name, site_url, ...
 *   $categories - array of arrays: each with 'name' and 'slug'
 */
if (!isset($settings)) { $settings = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';
$siteUrl  = rtrim($settings['site_url'] ?? '/', '/');
?>
<header class="site-header" role="banner">
    <div class="container header-inner">
        <a href="/" class="site-logo" aria-label="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <nav class="main-nav" role="navigation" aria-label="Navegacion principal">
            <button class="nav-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="nav-menu">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>

            <ul id="nav-menu" class="nav-list">
                <li><a href="/">Inicio</a></li>
                <?php foreach ($categories as $cat): ?>
                <li><a href="/categoria/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
                <li><a href="/buscar">Buscar</a></li>
            </ul>
        </nav>

        <form class="header-search" action="/buscar" method="get" role="search" aria-label="Buscar en el sitio">
            <label for="header-search-input" class="sr-only">Buscar</label>
            <input
                type="search"
                id="header-search-input"
                name="q"
                placeholder="Buscar articulos..."
                autocomplete="off"
                spellcheck="false"
            >
            <button type="submit" class="header-search-btn" aria-label="Buscar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </form>
    </div>
</header>
