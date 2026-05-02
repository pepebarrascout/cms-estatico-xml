<?php
/**
 * CMS Estático XML - Configuración Principal
 */

// Rutas base
define('CMS_ROOT', dirname(__DIR__));
define('DATA_DIR', CMS_ROOT . '/data');
define('ARTICLES_DIR', DATA_DIR . '/articles');
define('CATEGORIES_DIR', DATA_DIR . '/categories');
define('USERS_DIR', DATA_DIR . '/users');
define('INDEX_DIR', DATA_DIR . '/index');
define('CACHE_DIR', CMS_ROOT . '/cache');
define('STATIC_DIR', CMS_ROOT . '/static');
define('INCLUDES_DIR', CMS_ROOT . '/includes');
define('ADMIN_DIR', CMS_ROOT . '/admin');
define('TEMPLATES_DIR', CMS_ROOT . '/templates');
define('ASSETS_DIR', CMS_ROOT . '/assets');

// Archivo de configuración
define('SETTINGS_FILE', DATA_DIR . '/settings.xml');
define('SEARCH_INDEX_FILE', INDEX_DIR . '/search-index.json');
define('ARTICLES_INDEX_FILE', INDEX_DIR . '/articles-index.json');
define('CATEGORIES_INDEX_FILE', INDEX_DIR . '/categories-index.json');

// Configuración por defecto
define('DEFAULT_SETTINGS', [
    'site_name' => 'Mi CMS Estático',
    'site_description' => 'Sitio web estático generado con CMS XML',
    'site_url' => '',
    'articles_per_page' => '10',
    'words_preview' => '50',
    'admin_email' => '',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_encryption' => 'tls',
    'meta_keywords' => '',
    'meta_author' => '',
]);

/**
 * Cargar configuración desde XML
 */
function loadSettings(): array {
    if (!file_exists(SETTINGS_FILE)) {
        return DEFAULT_SETTINGS;
    }
    $xml = simplexml_load_file(SETTINGS_FILE);
    if ($xml === false) {
        return DEFAULT_SETTINGS;
    }
    $settings = [];
    foreach (DEFAULT_SETTINGS as $key => $default) {
        $val = (string)$xml->{$key};
        $settings[$key] = $val !== '' ? $val : $default;
    }
    return $settings;
}

/**
 * Guardar configuración a XML
 */
function saveSettings(array $settings): bool {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');
    foreach ($settings as $key => $value) {
        $xml->addChild($key, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
    }
    $dir = dirname(SETTINGS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $xml->asXML(SETTINGS_FILE) !== false;
}

/**
 * Obtener una configuración específica
 */
function getSetting(string $key, string $default = ''): string {
    static $settings = null;
    if ($settings === null) {
        $settings = loadSettings();
    }
    return $settings[$key] ?? $default;
}
