<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=plataforma_educativa;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Consolidando materias duplicadas...\n";

    $tables = ['materia_estudiante', 'materia_docente', 'actividades', 'recursos', 'foro_temas', 'tareas'];
    foreach ($tables as $table) {
        $sql = "UPDATE $table t
            JOIN materias m ON t.materia_id = m.id
            JOIN (SELECT nombre, MIN(id) AS keep_id FROM materias GROUP BY nombre) mm ON m.nombre = mm.nombre
            SET t.materia_id = mm.keep_id
            WHERE t.materia_id != mm.keep_id";
        $stmt = $pdo->exec($sql);
        echo "$table actualizados: " . ($stmt === false ? 0 : $stmt) . "\n";
    }

    $deleteSql = "DELETE m1 FROM materias m1 JOIN materias m2 ON m1.nombre = m2.nombre AND m1.id > m2.id";
    $deleted = $pdo->exec($deleteSql);
    echo "Materias duplicadas eliminadas: " . ($deleted === false ? 0 : $deleted) . "\n";

    $pdo->exec("ALTER TABLE materias ADD UNIQUE INDEX uniq_materia_nombre (nombre)");
    echo "Índice único agregado a materias(nombre).\n";

    echo "Hecho.\n";
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
