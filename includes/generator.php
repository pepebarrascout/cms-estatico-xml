<?php
/**
 * CMS Estático XML - Generador de páginas estáticas HTML
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/markdown.php';
require_once __DIR__ . '/xml-articles.php';
require_once __DIR__ . '/xml-categories.php';

/**
 * Regenerar todas las páginas estáticas
 */
function regenerateAll(): void {
    // Limpiar caché estático
    cleanStaticFiles();
    
    // Publicar artículos programados
    publishScheduledArticles();
    
    // Generar portada
    generateHomepage();
    
    // Generar páginas individuales de artículos
    generateAllArticles();
    
    // Generar páginas de categorías
    generateAllCategories();
    
    // Generar página de búsqueda
    generateSearchPage();
    
    // Generar página 404
    generate404Page();
    
    // Reconstruir índice de búsqueda
    rebuildSearchIndex();
    
    // Guardar timestamp de última generación
    safeWrite(CACHE_DIR . '/last-generated.txt', date('Y-m-d H:i:s'));
}

/**
 * Generar solo las páginas afectadas por un artículo
 */
function regenerateArticle(string $slug): void {
    publishScheduledArticles();
    
    $article = getArticleBySlug($slug);
    if ($article && $article['status'] === 'published') {
        generateArticlePage($article);
    } else {
        // Eliminar página estática si fue despublicado
        $staticFile = STATIC_DIR . '/' . $slug . '.html';
        if (file_exists($staticFile)) {
            unlink($staticFile);
        }
    }
    
    // Regenerar portada
    generateHomepage();
    
    // Regenerar categorías afectadas
    if ($article) {
        foreach ($article['categories'] as $catSlug) {
            generateCategoryPages($catSlug);
        }
    }
    
    rebuildSearchIndex();
}

/**
 * Limpiar archivos estáticos
 */
function cleanStaticFiles(): void {
    $files = glob(STATIC_DIR . '/*.html');
    foreach ($files as $file) {
        @unlink($file);
    }
}

/**
 * Generar portada con paginación
 */
function generateHomepage(int $pageOverride = null): void {
    $settings = loadSettings();
    $perPage = (int)($settings['articles_per_page'] ?? 10);
    $totalResult = listArticles('published', 1, PHP_INT_MAX);
    $totalArticles = count($totalResult['articles']);
    $totalPages = max(1, (int)ceil($totalArticles / $perPage));
    
    $pages = $pageOverride ? [$pageOverride] : range(1, $totalPages);
    
    foreach ($pages as $page) {
        $result = listArticles('published', $page, $perPage);
        $categories = listCategories();
        
        $html = renderTemplate('home', [
            'articles' => $result['articles'],
            'pagination' => $result['pagination'],
            'categories' => $categories,
            'page' => $page,
            'settings' => $settings,
        ]);
        
        $filename = $page === 1 ? 'home' : "home-page-$page";
        safeWrite(STATIC_DIR . '/' . $filename . '.html', $html);
    }
}

/**
 * Generar página individual de artículo
 */
function generateArticlePage(array $article): void {
    $settings = loadSettings();
    $categories = listCategories();
    
    // Obtener categoría details
    $categoryDetails = [];
    foreach ($article['categories'] as $catSlug) {
        $cat = getCategoryBySlug($catSlug);
        if ($cat) $categoryDetails[] = $cat;
    }
    
    $html = renderTemplate('article', [
        'article' => $article,
        'categories' => $categories,
        'article_categories' => $categoryDetails,
        'settings' => $settings,
    ]);
    
    safeWrite(STATIC_DIR . '/' . $article['slug'] . '.html', $html);
}

/**
 * Generar todas las páginas de artículos
 */
function generateAllArticles(): void {
    $result = listArticles('published', 1, PHP_INT_MAX);
    foreach ($result['articles'] as $article) {
        $full = getArticleBySlug($article['slug']);
        if ($full) {
            generateArticlePage($full);
        }
    }
}

/**
 * Generar páginas de una categoría
 */
function generateCategoryPages(string $categorySlug): void {
    $settings = loadSettings();
    $perPage = (int)($settings['articles_per_page'] ?? 10);
    $category = getCategoryBySlug($categorySlug);
    
    if (!$category) return;
    
    $result = getArticlesByCategory($categorySlug, 1, PHP_INT_MAX);
    $totalArticles = count($result['articles']);
    $totalPages = max(1, (int)ceil($totalArticles / $perPage));
    
    $allCategories = listCategories();
    
    for ($page = 1; $page <= $totalPages; $page++) {
        $paged = getArticlesByCategory($categorySlug, $page, $perPage);
        
        $html = renderTemplate('category', [
            'category' => $category,
            'articles' => $paged['articles'],
            'pagination' => $paged['pagination'],
            'categories' => $allCategories,
            'settings' => $settings,
        ]);
        
        $filename = $page === 1 ? "category-$categorySlug" : "category-$categorySlug-page-$page";
        safeWrite(STATIC_DIR . '/' . $filename . '.html', $html);
    }
}

/**
 * Generar todas las páginas de categorías
 */
function generateAllCategories(): void {
    $categories = listCategories();
    foreach ($categories as $cat) {
        generateCategoryPages($cat['slug']);
    }
}

/**
 * Generar página de búsqueda
 */
function generateSearchPage(): void {
    $settings = loadSettings();
    $categories = listCategories();
    
    $html = renderTemplate('search', [
        'categories' => $categories,
        'settings' => $settings,
    ]);
    
    safeWrite(STATIC_DIR . '/search.html', $html);
}

/**
 * Generar página 404
 */
function generate404Page(): void {
    $settings = loadSettings();
    $categories = listCategories();
    
    $html = renderTemplate('404', [
        'categories' => $categories,
        'settings' => $settings,
    ]);
    
    safeWrite(STATIC_DIR . '/404.html', $html);
}

/**
 * Renderizar plantilla
 */
function renderTemplate(string $name, array $data): string {
    $templateFile = TEMPLATES_DIR . '/' . $name . '.php';
    if (!file_exists($templateFile)) {
        return "<html><body>Error: Template $name not found</body></html>";
    }
    
    // Extraer variables para la plantilla
    extract($data, EXTR_SKIP);
    
    ob_start();
    include $templateFile;
    return ob_get_clean();
}

/**
 * Reconstruir índice de búsqueda
 */
function rebuildSearchIndex(): void {
    $result = listArticles('published', 1, PHP_INT_MAX);
    $index = [];
    
    foreach ($result['articles'] as $article) {
        $full = getArticleBySlug($article['slug']);
        if (!$full) continue;
        
        $index[] = [
            's' => $article['slug'],
            't' => $full['title'],
            'e' => excerpt($full['content'], 100),
            'a' => $article['author'],
            'c' => $full['categories'],
            'd' => $article['created'],
        ];
    }
    
    if (!is_dir(INDEX_DIR)) mkdir(INDEX_DIR, 0755, true);
    safeWrite(SEARCH_INDEX_FILE, json_encode($index, JSON_UNESCAPED_UNICODE));
}
