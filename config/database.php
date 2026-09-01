<?php
/**
 * Configuración de conexión a la base de datos
 * Plataforma Educativa - Metodologías Activas
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'plataforma_educativa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Ruta base del proyecto
define('BASE_URL', '/10_DE_AGOSTO');
define('BASE_PATH', __DIR__ . '/..');

require_once __DIR__ . '/app.php';

/**
 * Obtener conexión PDO a la base de datos
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Se especifica el puerto 3306 de tu MySQL
            $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>