<?php
/**
 * CMS Estático XML - Funciones de utilidad
 */

/**
 * Generar slug a partir de texto
 */
function slugify(string $text): string {
    // Reemplazar caracteres especiales latinos
    $map = [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ā'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ė'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ī'=>'i',
        'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ō'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ū'=>'u',
        'ñ'=>'n','ń'=>'n','ļ'=>'l','ķ'=>'k','č'=>'c','ć'=>'c','ç'=>'c',
        'š'=>'s','ś'=>'s','ž'=>'z','ź'=>'z','ž'=>'z',
        'ę'=>'e','ǹ'=>'n','ğ'=>'g','ş'=>'s','ţ'=>'t',
    ];
    $text = strtr(mb_strtolower($text, 'UTF-8'), $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'articulo-' . time();
}

/**
 * Verificar si un slug ya existe para artículos
 */
function articleSlugExists(string $slug, ?string $excludeSlug = null): bool {
    $files = glob(ARTICLES_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml && (string)$xml->slug === $slug) {
            if ($excludeSlug && (string)$xml->slug === $excludeSlug) continue;
            return true;
        }
    }
    return false;
}

/**
 * Generar slug único para artículos
 */
function uniqueArticleSlug(string $title, ?string $excludeSlug = null): string {
    $slug = slugify($title);
    if (!articleSlugExists($slug, $excludeSlug)) {
        return $slug;
    }
    $counter = 2;
    while (articleSlugExists($slug . '-' . $counter, $excludeSlug)) {
        $counter++;
    }
    return $slug . '-' . $counter;
}

/**
 * Verificar si un slug ya existe para categorías
 */
function categorySlugExists(string $slug, ?string $excludeSlug = null): bool {
    $files = glob(CATEGORIES_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml && (string)$xml->slug === $slug) {
            if ($excludeSlug && (string)$xml->slug === $excludeSlug) continue;
            return true;
        }
    }
    return false;
}

/**
 * Generar slug único para categorías
 */
function uniqueCategorySlug(string $name, ?string $excludeSlug = null): string {
    $slug = slugify($name);
    if (!categorySlugExists($slug, $excludeSlug)) {
        return $slug;
    }
    $counter = 2;
    while (categorySlugExists($slug . '-' . $counter, $excludeSlug)) {
        $counter++;
    }
    return $slug . '-' . $counter;
}

/**
 * Generar código OTP de 6 dígitos
 */
function generateOTP(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Extraer las primeras N palabras de un texto
 */
function excerpt(string $text, int $words = 50): string {
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    $wordList = explode(' ', $text);
    if (count($wordList) <= $words) {
        return $text;
    }
    return implode(' ', array_slice($wordList, 0, $words)) . '...';
}

/**
 * Sanitizar entrada HTML
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirigir con mensaje flash
 */
function redirect(string $url, string $message = '', string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header("Location: $url");
    exit;
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Formatear fecha
 */
function formatDate(string $date): string {
    $ts = strtotime($date);
    $meses = [
        'Enero','Febrero','Marzo','Abril','Mayo','Junio',
        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];
    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    return $dias[date('w', $ts)] . ', ' . date('d', $ts) . ' de ' . $meses[date('n', $ts)-1] . ' de ' . date('Y', $ts);
}

/**
 * Calcular tiempo de lectura estimado
 */
function readingTime(string $content): string {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = max(1, (int)ceil($wordCount / 200));
    return $minutes . ' min';
}

/**
 * Paginación
 */
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => ($currentPage - 1) * $perPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
    ];
}

/**
 * Escribir archivo de forma segura (escritura atómica)
 */
function safeWrite(string $filePath, string $content): bool {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $tmpFile = tempnam($dir, 'tmp_');
    if (file_put_contents($tmpFile, $content) === false) {
        @unlink($tmpFile);
        return false;
    }
    if (!rename($tmpFile, $filePath)) {
        @unlink($tmpFile);
        return false;
    }
    return true;
}
