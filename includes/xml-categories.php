<?php
/**
 * CMS Estático XML - Gestión de categorías en XML
 */

require_once __DIR__ . '/functions.php';

/**
 * Obtener categoría por slug
 */
function getCategoryBySlug(string $slug): ?array {
    $file = CATEGORIES_DIR . '/' . $slug . '.xml';
    if (!file_exists($file)) return null;
    
    $xml = simplexml_load_file($file);
    if ($xml === false) return null;
    
    return [
        'name' => (string)$xml->name,
        'slug' => (string)$xml->slug,
        'description' => (string)$xml->description,
        'created' => (string)$xml->created,
    ];
}

/**
 * Listar todas las categorías
 */
function listCategories(): array {
    $categories = [];
    $files = glob(CATEGORIES_DIR . '/*.xml');
    
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml) {
            $slug = (string)$xml->slug;
            $categories[] = [
                'name' => (string)$xml->name,
                'slug' => $slug,
                'description' => (string)$xml->description,
                'created' => (string)$xml->created,
                'article_count' => countArticlesInCategory($slug),
            ];
        }
    }
    
    usort($categories, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $categories;
}

/**
 * Crear categoría
 */
function createCategory(string $name, string $description = ''): array {
    if (empty(trim($name))) {
        return ['success' => false, 'message' => 'El nombre de la categoría es obligatorio'];
    }
    
    $slug = uniqueCategorySlug($name);
    
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><category></category>');
    $xml->addChild('name', htmlspecialchars($name, ENT_XML1, 'UTF-8'));
    $xml->addChild('slug', $slug);
    $xml->addChild('description', htmlspecialchars($description, ENT_XML1, 'UTF-8'));
    $xml->addChild('created', date('Y-m-d H:i:s'));
    
    if (!is_dir(CATEGORIES_DIR)) mkdir(CATEGORIES_DIR, 0755, true);
    
    $file = CATEGORIES_DIR . '/' . $slug . '.xml';
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Categoría creada', 'slug' => $slug];
    }
    return ['success' => false, 'message' => 'Error al guardar la categoría'];
}

/**
 * Actualizar categoría
 */
function updateCategory(string $slug, string $name, string $description = ''): array {
    $file = CATEGORIES_DIR . '/' . $slug . '.xml';
    if (!file_exists($file)) {
        return ['success' => false, 'message' => 'Categoría no encontrada'];
    }
    
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        return ['success' => false, 'message' => 'Error al leer la categoría'];
    }
    
    $xml->name = htmlspecialchars($name, ENT_XML1, 'UTF-8');
    $xml->description = htmlspecialchars($description, ENT_XML1, 'UTF-8');
    
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Categoría actualizada'];
    }
    return ['success' => false, 'message' => 'Error al guardar'];
}

/**
 * Eliminar categoría
 */
function deleteCategory(string $slug): bool {
    // Eliminar referencia de la categoría en todos los artículos
    $files = glob(ARTICLES_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) continue;
        
        $changed = false;
        foreach ($xml->categories->category as $cat) {
            if ((string)$cat === $slug) {
                $dom = dom_import_simplexml($cat);
                $dom->parentNode->removeChild($dom);
                $changed = true;
            }
        }
        
        if ($changed) {
            // Re-cargar porque removeChild puede afectar la iteración
            $xml->asXML($file);
        }
    }
    
    $file = CATEGORIES_DIR . '/' . $slug . '.xml';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

/**
 * Contar artículos en una categoría
 */
function countArticlesInCategory(string $categorySlug): int {
    $count = 0;
    $files = glob(ARTICLES_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if (!$xml) continue;
        if ((string)$xml->status !== 'published') continue;
        
        foreach ($xml->categories->category as $cat) {
            if ((string)$cat === $categorySlug) {
                $count++;
                break;
            }
        }
    }
    return $count;
}
