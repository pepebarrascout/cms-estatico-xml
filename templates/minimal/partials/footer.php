<?php
/**
 * Site footer — Minimal Theme
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
    <div class="footer-inner">
        <div class="footer-categories">
            <?php if (!empty($categories)): ?>
            <ul class="footer-list">
                <?php foreach ($categories as $cat): ?>
                <li><a href="/categoria/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="footer-bottom">
            <p class="footer-copyright">&copy; <?= $currentYear ?> <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="footer-load-time">Tiempo de carga: <span id="load-time">-</span> s</p>
        </div>
    </div>
</footer>

<script>document.getElementById('load-time').textContent=(performance.now()-window._cms_start).toFixed(3);</script>
