<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    // 1. Create materia_curso table
    $db->exec("
    CREATE TABLE IF NOT EXISTS materia_curso (
        id INT AUTO_INCREMENT PRIMARY KEY,
        materia_id INT NOT NULL,
        docente_id INT NOT NULL,
        grado TINYINT UNSIGNED NOT NULL,
        paralelo ENUM('A', 'B', 'C', 'D') NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
        FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY unique_materia_curso (materia_id, grado, paralelo)
    ) ENGINE=InnoDB;
    ");
    echo "materia_curso created.\n";

    // 2. Add columns to actividades
    try {
        $db->exec("ALTER TABLE actividades ADD COLUMN grado TINYINT UNSIGNED NULL, ADD COLUMN paralelo ENUM('A', 'B', 'C', 'D') NULL");
        echo "actividades updated.\n";
    } catch (PDOException $e) { echo "actividades error: " . $e->getMessage() . "\n"; }

    // 3. Add columns to recursos
    try {
        $db->exec("ALTER TABLE recursos ADD COLUMN grado TINYINT UNSIGNED NULL, ADD COLUMN paralelo ENUM('A', 'B', 'C', 'D') NULL");
        echo "recursos updated.\n";
    } catch (PDOException $e) { echo "recursos error: " . $e->getMessage() . "\n"; }

    $db->exec("INSERT IGNORE INTO materia_curso (materia_id, docente_id, grado, paralelo)
               SELECT 1, id, 8, 'A' FROM usuarios WHERE email = 'docente@plataforma.com'");
    $db->exec("INSERT IGNORE INTO materia_curso (materia_id, docente_id, grado, paralelo)
               SELECT 2, id, 8, 'A' FROM usuarios WHERE email = 'docente@plataforma.com'");
               
    echo "Migración completada exitosamente.";
} catch (PDOException $e) {
    echo "Error en la migración: " . $e->getMessage();
}
