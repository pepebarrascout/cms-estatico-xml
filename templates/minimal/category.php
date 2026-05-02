<?php
/**
 * Category listing page template — Minimal Theme
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

$pageTitle       = $catName . ' — ' . $siteName;
$pageDescription = !empty($catDescription) ? $catDescription : 'Articulos en la categoria ' . $catName;
$canonicalUrl    = '/categoria/' . $catSlug . ($currentPage > 1 ? '/page/' . $currentPage : '');

$themePartialsDir = CMS_ROOT . '/templates/' . ($settings['theme'] ?? 'default') . '/partials';

include $themePartialsDir . '/head.php';
?>

<body>
<?php include $themePartialsDir . '/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <div class="category-header">
            <h1 class="category-title"><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (!empty($catDescription)): ?>
            <p class="category-description"><?= htmlspecialchars($catDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <section class="articles-list" aria-label="Articulos en <?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (empty($articles)): ?>
            <p class="empty-state">No hay articulos en esta categoria.</p>
            <?php else: ?>

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
            <article class="article-item">
                <time class="article-item-date" datetime="<?= htmlspecialchars($created, ENT_QUOTES, 'UTF-8') ?>"><?= $formattedDate ?></time>

                <h2 class="article-item-title">
                    <a href="/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></a>
                </h2>

                <p class="article-item-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>

                <?php if (!empty($artCats)): ?>
                <div class="article-item-categories">
                    <?php foreach ($artCats as $cat):
                        $cName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                        $cSlug = is_array($cat) ? ($cat['slug'] ?? '') : '';
                    ?>
                    <a href="/categoria/<?= htmlspecialchars($cSlug, ENT_QUOTES, 'UTF-8') ?>" class="category-link"><?= htmlspecialchars($cName, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <a href="/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="read-more">Leer mas &rarr;</a>
            </article>
            <?php endforeach; ?>

            <?php endif; ?>

            <?php
            $hasPrev    = $pagination['has_prev'] ?? false;
            $hasNext    = $pagination['has_next'] ?? false;
            $totalPages = $pagination['total_pages'] ?? 1;
            ?>
            <?php if ($totalPages > 1): ?>
            <nav class="pagination" role="navigation" aria-label="Paginacion de categoria">
                <?php if ($hasPrev): ?>
                <?php $prevPage = $currentPage - 1; ?>
                <?php if ($prevPage <= 1): ?>
                <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>" class="pagination-link pagination-prev">&larr; Anterior</a>
                <?php else: ?>
                <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>/page/<?= $prevPage ?>" class="pagination-link pagination-prev">&larr; Anterior</a>
                <?php endif; ?>
                <?php endif; ?>

                <span class="pagination-info"><?= $currentPage ?> / <?= $totalPages ?></span>

                <?php if ($hasNext): ?>
                <a href="/categoria/<?= htmlspecialchars($catSlug, ENT_QUOTES, 'UTF-8') ?>/page/<?= $currentPage + 1 ?>" class="pagination-link pagination-next">Siguiente &rarr;</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include $themePartialsDir . '/footer.php'; ?>
</body>
</html>
