<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

try {
    $db->exec("
    CREATE TABLE IF NOT EXISTS juegos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        materia_id INT NOT NULL,
        docente_id INT NOT NULL,
        titulo VARCHAR(200) NOT NULL,
        asignatura VARCHAR(100) NOT NULL,
        tema VARCHAR(200) NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
        FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");
    echo "juegos created\n";
} catch (PDOException $e) { echo "juegos error: " . $e->getMessage() . "\n"; }

try {
    $db->exec("
    CREATE TABLE IF NOT EXISTS juegos_preguntas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        juego_id INT NOT NULL,
        pregunta TEXT NOT NULL,
        opcion_a VARCHAR(255) NOT NULL,
        opcion_b VARCHAR(255) NOT NULL,
        opcion_c VARCHAR(255) NOT NULL,
        opcion_d VARCHAR(255) NOT NULL,
        respuesta_correcta ENUM('A', 'B', 'C', 'D') NOT NULL,
        FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");
    echo "juegos_preguntas created\n";
} catch (PDOException $e) { echo "juegos_preguntas error: " . $e->getMessage() . "\n"; }

try {
    $db->exec("
    CREATE TABLE IF NOT EXISTS juegos_sesiones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        juego_id INT NOT NULL,
        actividad_id INT NOT NULL,
        grado TINYINT UNSIGNED,
        paralelo ENUM('A', 'B', 'C', 'D'),
        fecha_activacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (juego_id) REFERENCES juegos(id) ON DELETE CASCADE,
        FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");
    echo "juegos_sesiones created\n";
} catch (PDOException $e) { echo "juegos_sesiones error: " . $e->getMessage() . "\n"; }

try {
    $db->exec("
    CREATE TABLE IF NOT EXISTS juegos_intentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sesion_id INT NOT NULL,
        estudiante_id INT NOT NULL,
        puntaje DECIMAL(5,2) NOT NULL,
        fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sesion_id) REFERENCES juegos_sesiones(id) ON DELETE CASCADE,
        FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ");
    echo "juegos_intentos created\n";
} catch (PDOException $e) { echo "juegos_intentos error: " . $e->getMessage() . "\n"; }

echo "Migración terminada.\n";
