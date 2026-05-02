<?php
/**
 * Category listing page template
 * Variables:
 *   $category   - array: name, slug, description
 *   $articles   - array of article arrays
 *   $pagination - array: current_page, total_pages, has_prev, has_next, prev_page, next_page
 *   $categories - array of all category arrays
 *   $settings   - array: site settings
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($articles))   { $articles   = []; }
if (!isset($categories)) { $categories = []; }
if (!isset($pagination)) { $pagination = ['current_page' => 1, 'total_pages' => 1, 'has_prev' => false, 'has_next' => false]; }
if (!isset($category))   { $category   = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';

$catName        = $category['name'] ?? 'Categoria';
$catSlug        = $category['slug'] ?? '';
$catDescription = $category['description'] ?? '';
$currentPage    = $pagination['current_page'] ?? 1;

$pageTitle       = $catName . ' - ' . $siteName;
$pageDescription = !empty($catDescription) ? $catDescription : 'Artulos en la categoria ' . $catName;
$canonicalUrl    = '/categoria/' . $catSlug . ($currentPage > 1 ? '/page/' . $currentPage : '');

include TEMPLATES_DIR . '/partials/head.php';
?>

<body>
<?php include TEMPLATES_DIR . '/partials/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <div class="category-header">
            <h1 class="category-title"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (!empty($catDescription)): ?>
            <p class="category-description"><?= htmlspecialchars($catDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <section class="articles-section" aria-label="Artulos en <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (empty($articles)): ?>
            <div class="empty-state">
                <p>No hay articulos en esta categoria.</p>
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
                                $cName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                                $cSlug = is_array($cat) ? ($cat['slug'] ?? '') : '';
                            ?>
                            <a href="/categoria/<?= htmlspecialchars($cSlug, ENT_QUOTES, 'UTF-8') ?>" class="category-tag"><?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?></a>
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
            $hasPrev    = $pagination['has_prev'] ?? false;
            $hasNext    = $pagination['has_next'] ?? false;
            $totalPages = $pagination['total_pages'] ?? 1;
            ?>
            <?php if ($totalPages > 1): ?>
            <nav class="pagination" role="navigation" aria-label="Paginacion de categoria">
                <ul class="pagination-list">
                    <?php if ($hasPrev): ?>
                    <?php $prevPage = $currentPage - 1; ?>
                    <li class="pagination-item pagination-prev">
                        <?php if ($prevPage <= 1): ?>
                        <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>" aria-label="Pagina anterior">&laquo; Anterior</a>
                        <?php else: ?>
                        <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>/page/<?= $prevPage ?>" aria-label="Pagina anterior">&laquo; Anterior</a>
                        <?php endif; ?>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $pageUrl = $i <= 1 ? '/categoria/' . htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') : '/categoria/' . htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') . '/page/' . $i;
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
                        <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>/page/<?= $currentPage + 1 ?>" aria-label="Pagina siguiente">Siguiente &raquo;</a>
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
