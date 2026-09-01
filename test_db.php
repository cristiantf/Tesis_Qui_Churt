<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = getDB();
    echo "Conexión a la base de datos EXITOSA.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
