<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE juegos ADD COLUMN fecha_apertura DATETIME DEFAULT NULL AFTER paralelo, ADD COLUMN fecha_fin DATETIME DEFAULT NULL AFTER fecha_apertura");
    echo "Columnas fecha_apertura y fecha_fin agregadas a tabla juegos.\n";
} catch (PDOException $e) {
    echo "Info: " . $e->getMessage() . "\n";
}
