<?php
/**
 * CMS Estático XML - Gestión de usuarios en XML
 */

require_once __DIR__ . '/functions.php';

/**
 * Obtener usuario por username
 */
function getUserByUsername(string $username): ?array {
    $file = USERS_DIR . '/' . $username . '.xml';
    if (!file_exists($file)) return null;
    
    $xml = simplexml_load_file($file);
    if ($xml === false) return null;
    
    return [
        'username' => (string)$xml->username,
        'email' => (string)$xml->email,
        'password_hash' => (string)$xml->password_hash,
        'role' => (string)$xml->role,
        'display_name' => (string)$xml->display_name,
        'created' => (string)$xml->created,
        'active' => (string)$xml->active === '1',
    ];
}

/**
 * Obtener usuario por email
 */
function getUserByEmail(string $email): ?array {
    $files = glob(USERS_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml && strtolower((string)$xml->email) === strtolower($email)) {
            return [
                'username' => (string)$xml->username,
                'email' => (string)$xml->email,
                'password_hash' => (string)$xml->password_hash,
                'role' => (string)$xml->role,
                'display_name' => (string)$xml->display_name,
                'created' => (string)$xml->created,
                'active' => (string)$xml->active === '1',
            ];
        }
    }
    return null;
}

/**
 * Crear usuario
 */
function createUser(string $username, string $email, string $password, string $role, string $displayName): array {
    // Verificar que no existe
    if (getUserByUsername($username)) {
        return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
    }
    if (getUserByEmail($email)) {
        return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
    }
    
    // Validar
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'El nombre de usuario debe tener al menos 3 caracteres'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Correo electrónico no válido'];
    }
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
    }
    
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><user></user>');
    $xml->addChild('username', htmlspecialchars($username, ENT_XML1, 'UTF-8'));
    $xml->addChild('email', htmlspecialchars($email, ENT_XML1, 'UTF-8'));
    $xml->addChild('password_hash', password_hash($password, PASSWORD_DEFAULT));
    $xml->addChild('role', htmlspecialchars($role, ENT_XML1, 'UTF-8'));
    $xml->addChild('display_name', htmlspecialchars($displayName, ENT_XML1, 'UTF-8'));
    $xml->addChild('created', date('Y-m-d H:i:s'));
    $xml->addChild('active', '1');
    
    if (!is_dir(USERS_DIR)) mkdir(USERS_DIR, 0755, true);
    
    $file = USERS_DIR . '/' . $username . '.xml';
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Usuario creado correctamente'];
    }
    return ['success' => false, 'message' => 'Error al crear el usuario'];
}

/**
 * Actualizar usuario
 */
function updateUser(string $username, array $data): array {
    $user = getUserByUsername($username);
    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado'];
    }
    
    // Verificar email duplicado
    if (isset($data['email']) && $data['email'] !== $user['email']) {
        $existing = getUserByEmail($data['email']);
        if ($existing) {
            return ['success' => false, 'message' => 'El correo electrónico ya está registrado'];
        }
    }
    
    $file = USERS_DIR . '/' . $username . '.xml';
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        return ['success' => false, 'message' => 'Error al leer el usuario'];
    }
    
    if (isset($data['email'])) $xml->email = htmlspecialchars($data['email'], ENT_XML1, 'UTF-8');
    if (isset($data['display_name'])) $xml->display_name = htmlspecialchars($data['display_name'], ENT_XML1, 'UTF-8');
    if (isset($data['role'])) $xml->role = htmlspecialchars($data['role'], ENT_XML1, 'UTF-8');
    if (isset($data['active'])) $xml->active = $data['active'] ? '1' : '0';
    if (isset($data['new_password']) && $data['new_password'] !== '') {
        $xml->password_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
    }
    
    if ($xml->asXML($file)) {
        return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
    }
    return ['success' => false, 'message' => 'Error al guardar'];
}

/**
 * Eliminar usuario (dar de baja)
 */
function deleteUser(string $username): bool {
    $file = USERS_DIR . '/' . $username . '.xml';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

/**
 * Listar todos los usuarios
 */
function listUsers(): array {
    $users = [];
    $files = glob(USERS_DIR . '/*.xml');
    foreach ($files as $file) {
        $xml = simplexml_load_file($file);
        if ($xml) {
            $users[] = [
                'username' => (string)$xml->username,
                'email' => (string)$xml->email,
                'role' => (string)$xml->role,
                'display_name' => (string)$xml->display_name,
                'created' => (string)$xml->created,
                'active' => (string)$xml->active === '1',
            ];
        }
    }
    usort($users, function($a, $b) { return strcmp($a['username'], $b['username']); });
    return $users;
}

/**
 * Verificar credenciales para login tradicional
 */
function verifyCredentials(string $email, string $password): ?array {
    $user = getUserByEmail($email);
    if (!$user) return null;
    if (!$user['active']) return null;
    if (!password_verify($password, $user['password_hash'])) return null;
    return $user;
}
