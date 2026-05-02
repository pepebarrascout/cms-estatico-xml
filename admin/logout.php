<?php
/**
 * CMS Estático XML - Cerrar sesión
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

logout();
redirect('/admin/login', 'Sesión cerrada correctamente', 'info');
