<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plataforma_educativa;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query('DESCRIBE usuarios');
    foreach ($stmt as $row) {
        echo $row['Field'] . '\t' . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
