<?php
/**
 * Logout — Cerrar sesión
 */
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

requireValidCsrfToken();
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/login.php');
exit;
