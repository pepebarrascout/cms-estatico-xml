<?php
/**
 * CMS Estático XML - API: Crear categoría desde el editor de artículos
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/xml-categories.php';

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
$name = sanitize($input['name'] ?? '');
$description = sanitize($input['description'] ?? '');

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre de la categoría es obligatorio']);
    exit;
}

$result = createCategory($name, $description);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'slug' => $result['slug'],
        'name' => htmlspecialchars_decode($name),
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
