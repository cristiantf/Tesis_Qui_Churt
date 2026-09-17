<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE juegos ADD COLUMN grado VARCHAR(10) DEFAULT NULL AFTER tema, ADD COLUMN paralelo VARCHAR(2) DEFAULT NULL AFTER grado");
    echo "Columnas grado y paralelo agregadas a tabla juegos.\n";
} catch (PDOException $e) {
    echo "Info: " . $e->getMessage() . "\n";
}
