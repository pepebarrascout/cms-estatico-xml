<?php
/**
 * Single article page template — Minimal Theme
 * Variables:
 *   $article            - array: slug, title, content, author, created, categories, meta_description, ...
 *   $article_categories - array of category detail arrays: name, slug, description
 *   $categories         - array of all category arrays
 *   $settings           - array: site settings
 */
if (!isset($settings))   { $settings   = []; }
if (!isset($categories)) { $categories = []; }
if (!isset($article))    { $article    = []; }
if (!isset($article_categories)) { $article_categories = []; }

$siteName = $settings['site_name'] ?? 'Mi Sitio';
$siteUrl  = rtrim($settings['site_url'] ?? '/', '/');

$articleTitle    = $article['title'] ?? 'Sin titulo';
$articleSlug     = $article['slug'] ?? '';
$articleContent  = $article['content'] ?? '';
$articleAuthor   = $article['author'] ?? '';
$articleCreated  = $article['created'] ?? '';
$articleMetaDesc = $article['meta_description'] ?? '';

/* Build page meta */
$pageTitle = $articleTitle . ' — ' . $siteName;

/* Meta description */
if (!empty($articleMetaDesc)) {
    $pageDescription = $articleMetaDesc;
} else {
    $plainContent = strip_tags($articleContent);
    $words = preg_split('/\s+/', $plainContent, 31);
    if (count($words) > 30) {
        array_pop($words);
        $pageDescription = implode(' ', $words) . '...';
    } else {
        $pageDescription = implode(' ', $words);
    }
    if (strlen($pageDescription) > 160) {
        $pageDescription = substr($pageDescription, 0, 157) . '...';
    }
}

$canonicalUrl = '/' . $articleSlug;
$ogType = 'article';

/* Format date */
$formattedDate = '';
$dateIso = '';
if (!empty($articleCreated)) {
    $ts = is_numeric($articleCreated) ? (int)$articleCreated : strtotime($articleCreated);
    if ($ts) {
        $formattedDate = date('d \d\e F, Y', $ts);
        $dateIso = date('c', $ts);
    }
}

/* Reading time */
$readingMinutes = '1 min';
if (function_exists('readingTime')) {
    $readingMinutes = readingTime($articleContent);
}

/* HTML content */
$htmlContent = '';
if (function_exists('markdownToHtml')) {
    $htmlContent = markdownToHtml($articleContent);
} else {
    $htmlContent = '<p>' . nl2br(htmlspecialchars($articleContent, ENT_QUOTES, 'UTF-8')) . '</p>';
}

/* Breadcrumb category */
$breadcrumbCat = null;
if (!empty($article_categories)) {
    $breadcrumbCat = $article_categories[0];
}

$themePartialsDir = CMS_ROOT . '/templates/' . ($settings['theme'] ?? 'default') . '/partials';

include $themePartialsDir . '/head.php';
?>

<body>
<?php include $themePartialsDir . '/header.php'; ?>

<main class="site-main" role="main">
    <div class="container">
        <nav class="breadcrumb" aria-label="Ruta de navegacion">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item"><a href="/">Inicio</a></li>
                <?php if ($breadcrumbCat): ?>
                <li class="breadcrumb-item"><a href="/categoria/<?= htmlspecialchars($breadcrumbCat['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($breadcrumbCat['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item breadcrumb-current" aria-current="page"><?= htmlspecialchars($articleTitle, ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>

        <article class="article-single" itemscope itemtype="https://schema.org/Article">
            <header class="article-header">
                <h1 class="article-title" itemprop="headline"><?= htmlspecialchars($articleTitle, ENT_QUOTES, 'UTF-8') ?></h1>

                <div class="article-meta">
                    <?php if (!empty($articleAuthor)): ?>
                    <span class="article-author" itemprop="author"><?= htmlspecialchars($articleAuthor, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>

                    <?php if (!empty($formattedDate)): ?>
                    <time class="article-date" datetime="<?= htmlspecialchars($dateIso, ENT_QUOTES, 'UTF-8') ?>" itemprop="datePublished"><?= $formattedDate ?></time>
                    <?php endif; ?>

                    <span class="article-reading-time"><?= htmlspecialchars($readingMinutes, ENT_QUOTES, 'UTF-8') ?> de lectura</span>
                </div>

                <?php if (!empty($article_categories)): ?>
                <div class="article-categories">
                    <?php foreach ($article_categories as $cat): ?>
                    <a href="/categoria/<?= htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8') ?>" class="category-link"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </header>

            <div class="article-content" itemprop="articleBody">
                <?= $htmlContent ?>
            </div>

            <footer class="article-footer">
                <a href="/" class="back-link">&larr; Volver al inicio</a>
            </footer>
        </article>

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Article",
            "headline": <?= json_encode($articleTitle, JSON_UNESCAPED_UNICODE) ?>,
            <?php if (!empty($articleAuthor)): ?>
            "author": { "@type": "Person", "name": <?= json_encode($articleAuthor, JSON_UNESCAPED_UNICODE) ?> },
            <?php endif; ?>
            <?php if (!empty($dateIso)): ?>
            "datePublished": <?= json_encode($dateIso) ?>,
            <?php endif; ?>
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": <?= json_encode($siteUrl . $canonicalUrl, JSON_UNESCAPED_UNICODE) ?>
            },
            "publisher": {
                "@type": "Organization",
                "name": <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>
            }
        }
        </script>
    </div>
</main>

<?php include $themePartialsDir . '/footer.php'; ?>
</body>
</html>
