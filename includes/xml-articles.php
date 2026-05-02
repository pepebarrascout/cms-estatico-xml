<?php
/**
 * CMS Estático XML - Gestión de artículos en XML
 */

require_once __DIR__ . '/functions.php';

/**
 * Obtener artículo por slug
 */
function getArticleBySlug(string $slug): ?array {
    $file = ARTICLES_DIR . '/' . $slug . '.xml';
    if (!file_exists($file)) return null;
    
    $xml = simplexml_load_file($file);
    if ($xml === false) return null;
    
    $categories = [];
    foreach ($xml->categories->category as $cat) {
        $categories[] = (string)$cat;
    }
    
    return [
        'slug' => (string)$xml->slug,
        'title' => (string)$xml->title,
        'content' => (string)$xml->content,
        'author' => (string)$xml->author,
        'categories' => $categories,
        'created' => (string)$xml->created,
        'updated' => (string)$xml->updated,
        'scheduled' => (string)$xml->scheduled,
        'status' => (string)$xml->status,
        'meta_description' => (string)$xml->meta_description,
    ];
}

/**
 * Listar artículos con paginación y filtros
 */
function listArticles(string $status = 'published', int $page = 1, int $perPage = 10, ?string $category = null, ?string $author = null, string $search = ''): array {
    $files = glob(ARTICLES_DIR . '/*.xml');
    $articles = [];
    
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) continue;
        
        $artStatus = (string)$xml->status;
        
        // Filtro de estado
        if ($status !== 'all' && $artStatus !== $status) continue;
        
        // Si es scheduled, verificar si ya pasó la hora
        if ($artStatus === 'scheduled') {
            $scheduled = (string)$xml->scheduled;
            if ($scheduled && strtotime($scheduled) <= time()) {
                // Publicar automáticamente
                $xml->status = 'published';
                $xml->asXML($file);
                $artStatus = 'published';
                if ($status === 'published' || $status === 'all') {
                    // Continuar procesando
                } else {
                    continue;
                }
            } elseif ($status !== 'scheduled' && $status !== 'all') {
                continue;
            }
        }
        
        // Filtro de categoría
        if ($category) {
            $found = false;
            foreach ($xml->categories->category as $cat) {
                if ((string)$cat === $category) { $found = true; break; }
            }
            if (!$found) continue;
        }
        
        // Filtro de autor
        if ($author && (string)$xml->author !== $author) continue;
        
        // Búsqueda
        if ($search) {
            $searchLower = mb_strtolower($search, 'UTF-8');
            $haystack = mb_strtolower((string)$xml->title . ' ' . (string)$xml->content, 'UTF-8');
            if (strpos($haystack, $searchLower) === false) continue;
        }
        
        $categories = [];
        foreach ($xml->categories->category as $cat) {
            $categories[] = (string)$cat;
        }
        
        $createdDate = (string)$xml->created;
        $scheduledDate = (string)$xml->scheduled;
        
        // Para artículos programados que ya se publicaron, usar la fecha de programación
        if ($artStatus === 'published' && $scheduledDate) {
            $createdDate = $scheduledDate;
        }
        
        $articles[] = [
            'slug' => (string)$xml->slug,
            'title' => (string)$xml->title,
            'excerpt' => excerpt((string)$xml->content, (int)getSetting('words_preview', '50')),
            'author' => (string)$xml->author,
            'categories' => $categories,
            'created' => $createdDate,
            'updated' => (string)$xml->updated,
            'scheduled' => $scheduledDate,
            'status' => $artStatus,
        ];
    }
    
    // Ordenar por fecha descendente
    usort($articles, function($a, $b) {
        return strcmp($b['created'], $a['created']);
    });
    
    $pagination = paginate(count($articles), $perPage, $page);
    $paged = array_slice($articles, $pagination['offset'], $perPage);
    
    return ['articles' => $paged, 'pagination' => $pagination];
}

/**
 * Crear artículo
 */
function createArticle(array $data): array {
    $slug = uniqueArticleSlug($data['title']);
    
    $status = $data['status'] ?? 'draft';
    $scheduled = '';
    
    if (isset($data['scheduled']) && $data['scheduled'] !== '') {
        $scheduled = $data['scheduled'];
        $status = 'scheduled';
    }
    
    $now = date('Y-m-d H:i:s');
    
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><article></article>');
    $xml->addChild('slug', $slug);
    $xml->addChild('title', htmlspecialchars($data['title'], ENT_XML1, 'UTF-8'));
    $xml->addChild('content', htmlspecialchars($data['content'], ENT_XML1, 'UTF-8'));
    $xml->addChild('author', htmlspecialchars($data['author'], ENT_XML1, 'UTF-8'));
    $xml->addChild('created', $now);
    $xml->addChild('updated', $now);
    $xml->addChild('scheduled', $scheduled);
    $xml->addChild('status', $status);
    $xml->addChild('meta_description', htmlspecialchars($data['meta_description'] ?? '', ENT_XML1, 'UTF-8'));
    
    $categoriesXml = $xml->addChild('categories');
    if (!empty($data['categories'])) {
        foreach ($data['categories'] as $cat) {
            $categoriesXml->addChild('category', htmlspecialchars($cat, ENT_XML1, 'UTF-8'));
        }
    }
    
    if (!is_dir(ARTICLES_DIR)) mkdir(ARTICLES_DIR, 0755, true);
    
    $file = ARTICLES_DIR . '/' . $slug . '.xml';
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Artículo creado', 'slug' => $slug];
    }
    return ['success' => false, 'message' => 'Error al guardar el artículo'];
}

/**
 * Actualizar artículo
 */
function updateArticle(string $slug, array $data): array {
    $file = ARTICLES_DIR . '/' . $slug . '.xml';
    if (!file_exists($file)) {
        return ['success' => false, 'message' => 'Artículo no encontrado'];
    }
    
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        return ['success' => false, 'message' => 'Error al leer el artículo'];
    }
    
    if (isset($data['title'])) $xml->title = htmlspecialchars($data['title'], ENT_XML1, 'UTF-8');
    if (isset($data['content'])) $xml->content = htmlspecialchars($data['content'], ENT_XML1, 'UTF-8');
    if (isset($data['meta_description'])) $xml->meta_description = htmlspecialchars($data['meta_description'], ENT_XML1, 'UTF-8');
    $xml->updated = date('Y-m-d H:i:s');
    
    if (isset($data['categories'])) {
        unset($xml->categories);
        $categoriesXml = $xml->addChild('categories');
        foreach ($data['categories'] as $cat) {
            $categoriesXml->addChild('category', htmlspecialchars($cat, ENT_XML1, 'UTF-8'));
        }
    }
    
    if (isset($data['status'])) {
        $xml->status = $data['status'];
    }
    
    if (isset($data['scheduled'])) {
        if ($data['scheduled'] !== '') {
            $xml->scheduled = $data['scheduled'];
            if ($data['status'] !== 'published') {
                $xml->status = 'scheduled';
            }
        } else {
            $xml->scheduled = '';
        }
    }
    
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Artículo actualizado', 'slug' => $slug];
    }
    return ['success' => false, 'message' => 'Error al guardar'];
}

/**
 * Eliminar artículo
 */
function deleteArticle(string $slug): bool {
    $file = ARTICLES_DIR . '/' . $slug . '.xml';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

/**
 * Publicar artículos programados whose time has come
 */
function publishScheduledArticles(): int {
    $count = 0;
    $files = glob(ARTICLES_DIR . '/*.xml');
    
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) continue;
        if ((string)$xml->status !== 'scheduled') continue;
        
        $scheduled = (string)$xml->scheduled;
        if ($scheduled && strtotime($scheduled) <= time()) {
            $xml->status = 'published';
            $xml->asXML($file);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Obtener total de artículos por estado
 */
function countArticles(): array {
    $count = ['published' => 0, 'draft' => 0, 'scheduled' => 0, 'all' => 0];
    $files = glob(ARTICLES_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) continue;
        $status = (string)$xml->status;
        $count['all']++;
        if (isset($count[$status])) $count[$status]++;
    }
    return $count;
}

/**
 * Obtener artículos de una categoría específica
 */
function getArticlesByCategory(string $categorySlug, int $page = 1, int $perPage = 10): array {
    return listArticles('published', $page, $perPage, $categorySlug);
}

/**
 * Obtener artículos de un autor
 */
function getArticlesByAuthor(string $author, string $status = 'published'): array {
    return listArticles($status, 1, PHP_INT_MAX, null, $author);
}
