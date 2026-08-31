<?php
/**
 * Configuración general de la aplicación
 * Plataforma Educativa — Unidad Educativa 10 de Agosto
 */

define('APP_NAME', 'Unidad Educativa Fiscomisional 10 de Agosto');
define('APP_DESCRIPTION', 'Plataforma educativa para estudiantes de 5° a 10° grado — Ciencias Naturales y Estudios Sociales');
define('APP_VERSION', '1.1.0');

define('GRADOS', [5, 6, 7, 8, 9, 10]);
define('PARALELOS', ['A', 'B', 'C', 'D']);

/**
 * Mapeo rol de base de datos → carpeta del módulo
 */
define('ROLE_FOLDERS', [
    'administrador' => 'admin',
    'docente'       => 'docente',
    'estudiante'    => 'estudiante',
]);

/**
 * Obtener carpeta del módulo según el rol
 */
function getRoleFolder($rol) {
    return ROLE_FOLDERS[$rol] ?? $rol;
}

/**
 * URL del panel principal según rol
 */
function getDashboardUrl($rol) {
    return BASE_URL . '/' . getRoleFolder($rol) . '/index.php';
}

/**
 * Redirigir al panel del usuario autenticado
 */
function redirectToDashboard($rol) {
    header('Location: ' . getDashboardUrl($rol));
    exit;
}

/**
 * URL de un asset estático (css, js, imágenes)
 */
function assetUrl($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * URL de imagen de materia
 */
function getMateriaImageUrl($filename) {
    if (empty($filename)) {
        return assetUrl('images/materias/default.svg');
    }

    $legacyMap = [
        'Ciencias Naturales.jpg' => 'ciencias-naturales.jpg',
        'Estudios Sociales.jpg'  => 'estudios-sociales.jpg',
        'ciencias-naturales.svg' => 'ciencias-naturales.jpg',
        'estudios-sociales.svg'  => 'estudios-sociales.jpg',
    ];
    if (isset($legacyMap[$filename])) {
        $filename = $legacyMap[$filename];
    }

    if (strpos($filename, 'assets/') === 0 || strpos($filename, 'http') === 0) {
        return BASE_URL . '/' . ltrim($filename, '/');
    }
    return assetUrl('images/materias/' . $filename);
}

/**
 * URL del logo institucional
 */
function getLogoUrl() {
    return assetUrl('images/logo.png');
}

/**
 * Etiqueta legible de grado y paralelo
 */
function getGradoParaleloLabel($grado, $paralelo) {
    if (empty($grado) || empty($paralelo)) {
        return null;
    }
    return intval($grado) . '° grado — Paralelo ' . strtoupper($paralelo);
}

/**
 * Materias disponibles en la pantalla pública
 */
function getPublicMaterias() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    try {
        $db = getDB();
        $rows = $db->query("SELECT * FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll();
        if ($rows) {
            $cache = $rows;
            return $cache;
        }
    } catch (Exception $e) {
        // Base de datos no disponible aún
    }

    $cache = [
        [
            'id' => 1,
            'nombre' => 'Ciencias Naturales',
            'descripcion' => 'Explora la vida, la Tierra y todo lo que nos rodea.',
            'imagen' => 'ciencias-naturales.jpg',
        ],
        [
            'id' => 2,
            'nombre' => 'Estudios Sociales',
            'descripcion' => 'Conoce la historia, las culturas y el mundo que nos rodea.',
            'imagen' => 'estudios-sociales.jpg',
        ],
    ];

    return $cache;
}

/**
 * Acciones disponibles dentro de cada materia
 */
function getMateriaAcciones() {
    return [
        ['id' => 'crear_actividades', 'label' => 'Crear Actividades', 'icon' => 'bi-plus-circle-fill', 'class' => 'action-create'],
        ['id' => 'quiz', 'label' => 'Quiz', 'icon' => 'bi-patch-question-fill', 'class' => 'action-quiz'],
        ['id' => 'subir_videos', 'label' => 'Subir Videos', 'icon' => 'bi-camera-video-fill', 'class' => 'action-video'],
        ['id' => 'editar', 'label' => 'Editar', 'icon' => 'bi-pencil-square', 'class' => 'action-edit'],
        ['id' => 'eliminar', 'label' => 'Eliminar', 'icon' => 'bi-trash-fill', 'class' => 'action-delete'],
        ['id' => 'guardar', 'label' => 'Guardar', 'icon' => 'bi-save-fill', 'class' => 'action-save'],
    ];
}

function getAccionLabel($accionId) {
    foreach (getMateriaAcciones() as $accion) {
        if ($accion['id'] === $accionId) {
            return $accion['label'];
        }
    }
    return 'Acceder';
}

function getMateriaByIdPublic($id) {
    foreach (getPublicMaterias() as $materia) {
        if (intval($materia['id']) === intval($id)) {
            return $materia;
        }
    }
    return null;
}

function getLoginUrlForAction($materiaId, $accion) {
    return BASE_URL . '/login.php?materia_id=' . intval($materiaId) . '&accion=' . urlencode($accion);
}

function getActionUrl($rol, $materiaId, $accion) {
    $m = intval($materiaId);
    $routes = [
        'docente' => [
            'crear_actividades' => BASE_URL . "/docente/actividades.php?materia=$m&nueva=1",
            'quiz' => BASE_URL . "/docente/actividades.php?materia=$m&tipo=quiz",
            'subir_videos' => BASE_URL . "/docente/recursos.php?materia=$m&subir=1&tipo=video",
            'editar' => BASE_URL . "/docente/actividades.php?materia=$m",
            'eliminar' => BASE_URL . "/docente/actividades.php?materia=$m",
            'guardar' => BASE_URL . "/docente/actividades.php?materia=$m&nueva=1",
        ],
        'estudiante' => [
            'crear_actividades' => BASE_URL . "/estudiante/actividades.php?materia_id=$m",
            'quiz' => BASE_URL . "/estudiante/actividades.php?materia_id=$m&tipo=quiz",
            'subir_videos' => BASE_URL . "/estudiante/recursos.php?materia_id=$m",
            'editar' => BASE_URL . "/estudiante/materia.php?id=$m",
            'eliminar' => BASE_URL . "/estudiante/actividades.php?materia_id=$m",
            'guardar' => BASE_URL . "/estudiante/actividades.php?materia_id=$m",
        ],
        'administrador' => [
            'crear_actividades' => BASE_URL . "/admin/materia.php?id=$m",
            'quiz' => BASE_URL . "/admin/materia.php?id=$m",
            'subir_videos' => BASE_URL . "/admin/materia.php?id=$m",
            'editar' => BASE_URL . "/admin/materia.php?id=$m",
            'eliminar' => BASE_URL . "/admin/materia.php?id=$m",
            'guardar' => BASE_URL . "/admin/materia.php?id=$m",
        ],
    ];

    return $routes[$rol][$accion] ?? null;
}

function redirectAfterLogin($rol, $materiaId = null, $accion = null) {
    if ($materiaId && $accion) {
        $url = getActionUrl($rol, $materiaId, $accion);
        if ($url) {
            header('Location: ' . $url);
            exit;
        }
    }

    redirectToDashboard($rol);
}

function getMateriaActionHref($materiaId, $accionId, $public = true) {
    if ($public || !isLoggedIn()) {
        return getLoginUrlForAction($materiaId, $accionId);
    }

    $url = getActionUrl(getCurrentUser()['rol'], $materiaId, $accionId);
    return $url ?: getDashboardUrl(getCurrentUser()['rol']);
}
