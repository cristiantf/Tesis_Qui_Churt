<?php
/**
 * Middleware de autenticación y funciones helper
 */
session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'],
        'apellido' => $_SESSION['user_apellido'],
        'email' => $_SESSION['user_email'],
        'rol' => $_SESSION['user_rol'],
        'grado' => $_SESSION['user_grado'] ?? null,
        'paralelo' => $_SESSION['user_paralelo'] ?? null,
    ];
}

/**
 * Requerir autenticación - redirige al login si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Requerir un rol específico
 */
function requireRole($rol) {
    requireLogin();
    if ($_SESSION['user_rol'] !== $rol) {
        header('Location: ' . BASE_URL . '/login.php?error=acceso_denegado');
        exit;
    }
}

/**
 * Obtener nombre completo del usuario
 */
function getFullName() {
    if (!isLoggedIn()) return 'Invitado';
    return $_SESSION['user_nombre'] . ' ' . $_SESSION['user_apellido'];
}

/**
 * Obtener iniciales del usuario
 */
function getInitials() {
    if (!isLoggedIn()) return 'IN';
    return strtoupper(substr($_SESSION['user_nombre'], 0, 1) . substr($_SESSION['user_apellido'], 0, 1));
}

/**
 * Mostrar mensajes flash
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitizar entrada
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener el nombre legible de la metodología
 */
function getMetodologiaName($tipo) {
    $metodologias = [
        'abp' => 'Aprendizaje Basado en Problemas',
        'clase_invertida' => 'Clase Invertida',
        'gamificacion' => 'Gamificación',
        'colaborativo' => 'Aprendizaje Colaborativo',
        'estudio_caso' => 'Estudio de Caso',
        'debate' => 'Debate'
    ];
    return $metodologias[$tipo] ?? $tipo;
}

/**
 * Obtener ícono de la metodología
 */
function getMetodologiaIcon($tipo) {
    $iconos = [
        'abp' => 'bi-lightbulb',
        'clase_invertida' => 'bi-arrow-repeat',
        'gamificacion' => 'bi-controller',
        'colaborativo' => 'bi-people',
        'estudio_caso' => 'bi-search',
        'debate' => 'bi-chat-quote'
    ];
    return $iconos[$tipo] ?? 'bi-book';
}

/**
 * Obtener color badge del estado
 */
function getEstadoBadge($estado) {
    $badges = [
        'borrador' => 'secondary',
        'publicada' => 'success',
        'cerrada' => 'danger',
        'pendiente' => 'warning',
        'entregada' => 'info',
        'calificada' => 'primary'
    ];
    return $badges[$estado] ?? 'secondary';
}

/**
 * Obtener materia por ID
 */
function getMateriaById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM materias WHERE id = ? AND estado = 1");
    $stmt->execute([intval($id)]);
    return $stmt->fetch();
}

/**
 * Clase CSS temática según la materia
 */
function getMateriaThemeClass($nombre) {
    return stripos($nombre, 'Ciencias') !== false ? 'theme-cn' : 'theme-es';
}

/**
 * Ícono representativo de la materia
 */
function getMateriaIcon($nombre) {
    return stripos($nombre, 'Ciencias') !== false ? 'bi-flower1' : 'bi-globe-americas';
}

// Helpers de rutas definidos en config/app.php:
// getRoleFolder(), getDashboardUrl(), redirectToDashboard(), assetUrl(), getMateriaImageUrl(), getLogoUrl()
