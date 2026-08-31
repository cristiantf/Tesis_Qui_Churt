<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plataforma_educativa;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query('SELECT id, nombre FROM materias ORDER BY nombre');
    foreach ($stmt as $row) {
        echo $row['id'] . "\t" . $row['nombre'] . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
