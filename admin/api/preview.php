<?php
/**
 * CMS Estático XML - API: Vista previa de Markdown
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/markdown.php';

// Must be authenticated
requireAuth();

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$content = $input['content'] ?? '';

$html = markdownToHtml($content);

echo json_encode([
    'success' => true,
    'html' => $html,
]);
