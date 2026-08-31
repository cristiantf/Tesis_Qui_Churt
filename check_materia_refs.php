<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plataforma_educativa;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $sql = "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'plataforma_educativa' AND COLUMN_NAME = 'materia_id'";
    $stmt = $pdo->query($sql);
    foreach ($stmt as $row) {
        echo $row['TABLE_NAME'] . "\n";
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
