<?php
/**
 * CMS Estático XML - Cron: Publicar artículos programados
 * Este script se ejecuta cada minuto para publicar artículos cuya fecha de programación ya pasó.
 */

// Solo ejecutar desde CLI
if (php_sapi_name() !== 'cli' && !defined('CRON_RUNNING')) {
    http_response_code(403);
    exit('Acceso denegado');
}

define('CRON_RUNNING', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/xml-articles.php';
require_once __DIR__ . '/../includes/generator.php';

// Publicar artículos programados
$published = publishScheduledArticles();

if ($published > 0) {
    // Regenerar páginas estáticas afectadas
    rebuildSearchIndex();
    generateHomepage();
    
    // Log
    $log = date('Y-m-d H:i:s') . " - Publicados $published artículos programados\n";
    file_put_contents(CACHE_DIR . '/cron.log', $log, FILE_APPEND);
}
