<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = getDB();
    $columns = [];
    $stmt = $db->query("SHOW COLUMNS FROM usuarios");
    foreach ($stmt as $row) {
        $columns[] = $row['Field'];
    }

    if (!in_array('grado', $columns, true)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN grado TINYINT UNSIGNED DEFAULT NULL AFTER rol");
        echo "Added column grado\n";
    } else {
        echo "Column grado already exists\n";
    }

    if (!in_array('paralelo', $columns, true)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN paralelo ENUM('A','B','C','D') DEFAULT NULL AFTER grado");
        echo "Added column paralelo\n";
    } else {
        echo "Column paralelo already exists\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
