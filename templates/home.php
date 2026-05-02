<?php
/**
 * Homepage template
 * Variables:
 *   $articles    - array of article arrays (slug, title, content, author, created, categories, ...)
 *   $pagination  - array: current_page, total_pages, has_prev, has_next, prev_page, next_page
 *   $categories  - array of category arrays
 *   $settings    - array: site settings
 *   $page        - int (current page number, default 1)
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($articles))   { $articles   = []; }
if (!isset($categories)) { $categories = []; }
if (!isset($pagination)) { $pagination = ['current_page' => 1, 'total_pages' => 1, 'has_prev' => false, 'has_next' => false]; }
if (!isset($page))       { $page = 1; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';
$siteDesc = $settings['site_description'] ?? '';

$pageTitle = ($page > 1) ? $siteName . ' - Pagina ' . $page : $siteName;
$pageDescription = $siteDesc;

$canonicalUrl = ($page > 1) ? '/page/' . $page : '/';

include TEMPLATES_DIR . '/partials/head.php';
?>

<body>
<?php include TEMPLATES_DIR . '/partials/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <section class="articles-section" aria-label="Ultimos articulos">
            <?php if (empty($articles)): ?>
            <div class="empty-state">
                <p>Aun no hay articulos publicados.</p>
            </div>
            <?php else: ?>

            <div class="articles-grid">
                <?php foreach ($articles as $article):
                    $slug      = $article['slug'] ?? '';
                    $title     = $article['title'] ?? 'Sin titulo';
                    $content   = $article['content'] ?? '';
                    $author    = $article['author'] ?? '';
                    $created   = $article['created'] ?? '';
                    $artCats   = $article['categories'] ?? [];

                    /* Excerpt: first 50 words */
                    $words = preg_split('/\s+/', strip_tags($content), 51);
                    if (count($words) > 50) {
                        array_pop($words);
                        $excerpt = implode(' ', $words) . '...';
                    } else {
                        $excerpt = implode(' ', $words);
                    }
                    if (strlen($excerpt) > 200) {
                        $excerpt = substr($excerpt, 0, 200) . '...';
                    }

                    /* Format date */
                    $formattedDate = '';
                    if (!empty($created)) {
                        $ts = is_numeric($created) ? (int)$created : strtotime($created);
                        if ($ts) {
                            $formattedDate = date('d \d\e F, Y', $ts);
                        }
                    }
                ?>
                <article class="article-card">
                    <div class="article-card-body">
                        <?php if (!empty($artCats)): ?>
                        <div class="article-card-categories">
                            <?php foreach ($artCats as $cat):
                                $catName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                                $catSlug = is_array($cat) ? ($cat['slug'] ?? '') : '';
                            ?>
                            <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>" class="category-tag"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <h2 class="article-card-title">
                            <a href="/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></a>
                        </h2>

                        <p class="article-card-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>

                        <div class="article-card-meta">
                            <?php if (!empty($author)): ?>
                            <span class="article-card-author"><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($formattedDate)): ?>
                            <time class="article-card-date" datetime="<?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?>"><?= $formattedDate ?></time>
                            <?php endif; ?>
                        </div>

                        <a href="/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="article-card-read-more">Leer mas</a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>

            <?php
            /* Pagination */
            $hasPrev     = $pagination['has_prev'] ?? false;
            $hasNext     = $pagination['has_next'] ?? false;
            $currentPage = $pagination['current_page'] ?? 1;
            $totalPages  = $pagination['total_pages'] ?? 1;
            ?>
            <?php if ($totalPages > 1): ?>
            <nav class="pagination" role="navigation" aria-label="Paginacion">
                <ul class="pagination-list">
                    <?php if ($hasPrev): ?>
                    <?php $prevPage = $currentPage - 1; ?>
                    <li class="pagination-item pagination-prev">
                        <a href="<?= $prevPage <= 1 ? '/' : '/page/' . $prevPage ?>" aria-label="Pagina anterior">&laquo; Anterior</a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $pageUrl = $i <= 1 ? '/' : '/page/' . $i;
                        $isActive = ($i === $currentPage);
                    ?>
                    <li class="pagination-item <?= $isActive ? 'pagination-active' : '' ?>">
                        <?php if ($isActive): ?>
                        <span aria-current="page"><?= $i ?></span>
                        <?php else: ?>
                        <a href="<?= $pageUrl ?>"><?= $i ?></a>
                        <?php endif; ?>
                    </li>
                    <?php endfor; ?>

                    <?php if ($hasNext): ?>
                    <li class="pagination-item pagination-next">
                        <a href="/page/<?= $currentPage + 1 ?>" aria-label="Pagina siguiente">Siguiente &raquo;</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include TEMPLATES_DIR . '/partials/footer.php'; ?>
</body>
</html>
