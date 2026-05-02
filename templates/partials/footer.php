<?php
/**
 * Site footer with copyright and page load time
 * Variables:
 *   $settings   - array: site_name, site_url, ...
 *   $categories - array of category arrays
 */
if (!isset($settings)) { $settings = []; }
if (!isset($categories)) { $categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';
$currentYear = date('Y');
?>
<footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
        <div class="footer-grid">
            <div class="footer-about">
                <h3 class="footer-heading"><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="footer-text">Contenido de calidad actualizado regularmente.</p>
            </div>

            <?php if (!empty($categories)): ?>
            <div class="footer-categories">
                <h3 class="footer-heading">Categorias</h3>
                <ul class="footer-list">
                    <?php foreach ($categories as $cat): ?>
                    <li><a href="/categoria/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="footer-links">
                <h3 class="footer-heading">Enlaces</h3>
                <ul class="footer-list">
                    <li><a href="/">Inicio</a></li>
                    <li><a href="/buscar">Buscar</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copyright">&copy; <?= $currentYear ?> <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>. Todos los derechos reservados.</p>
            <p class="footer-load-time">Tiempo de carga: <span id="load-time">-</span> segundos</p>
        </div>
    </div>
</footer>

<script>document.getElementById('load-time').textContent=(performance.now()-window._cms_start).toFixed(3);</script>
