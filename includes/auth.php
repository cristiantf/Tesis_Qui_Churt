<?php
/**
 * Middleware de autenticación y funciones helper
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/../config/database.php';

/**
 * Verificar si el usuario está autenticado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function requireValidCsrfToken() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo nuevamente.');
    }
}

function requireValidCsrfHeader() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Solicitud no válida']);
        exit;
    }
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

/** Permite solo formato editorial seguro proveniente del editor visual. */
function sanitizeRichText($html) {
    $html = trim((string) $html);
    if ($html === '') return '';
    $allowed = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h3', 'h4', 'span', 'a'];
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementsByTagName('div')->item(0);
    $nodes = [];
    foreach ($doc->getElementsByTagName('*') as $node) $nodes[] = $node;
    foreach ($nodes as $node) {
        if (!in_array(strtolower($node->nodeName), $allowed, true)) {
            while ($node->firstChild) $node->parentNode->insertBefore($node->firstChild, $node);
            $node->parentNode->removeChild($node);
            continue;
        }
        $attrs = [];
        foreach ($node->attributes as $attr) $attrs[] = $attr->nodeName;
        foreach ($attrs as $name) {
            $value = $node->getAttribute($name);
            $keep = ($node->nodeName === 'a' && in_array($name, ['href', 'target', 'rel'], true)) || ($node->nodeName === 'span' && $name === 'style');
            if (!$keep) $node->removeAttribute($name);
            if ($name === 'href' && !preg_match('#^https?://#i', $value)) $node->removeAttribute($name);
            if ($name === 'target' && $value !== '_blank') $node->removeAttribute($name);
            if ($name === 'style' && !preg_match('/^\s*color\s*:\s*(#[0-9a-f]{3,8}|rgb\([^)]+\)|[a-z]+)\s*;?\s*$/i', $value)) $node->removeAttribute($name);
        }
        if ($node->nodeName === 'a' && $node->getAttribute('target') === '_blank') $node->setAttribute('rel', 'noopener noreferrer');
    }
    $result = '';
    foreach ($root->childNodes as $child) $result .= $doc->saveHTML($child);
    return $result;
}

function renderRichText($html) {
    return sanitizeRichText($html);
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
