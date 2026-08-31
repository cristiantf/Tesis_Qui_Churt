<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plataforma_educativa;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ids = [1,3,5,2,4,6];
    $tables = ['actividades','foro_temas','materia_docente','materia_estudiante','recursos','tareas'];
    foreach ($ids as $id) {
        echo "Materia ID $id:\n";
        foreach ($tables as $table) {
            $cnt = $pdo->query("SELECT COUNT(*) FROM $table WHERE materia_id = $id")->fetchColumn();
            echo "  $table: $cnt\n";
        }
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}
