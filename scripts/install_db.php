<?php
/**
 * Instalador de base de datos
 * Ejecutar una sola vez después de configurar XAMPP (Apache + MySQL)
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$sqlFile = BASE_PATH . '/database/schema.sql';
if (!file_exists($sqlFile)) {
    $sqlFile = BASE_PATH . '/database.sql';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: Inter, Arial, sans-serif; }
        pre { background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; max-height: 400px; overflow: auto; }
    </style>
</head>
<body class="py-5">
<div class="container col-md-8">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold mb-3">Instalación de base de datos</h1>
            <p class="text-muted">Este script crea la base de datos <code><?php echo DB_NAME; ?></code> e inserta los datos iniciales.</p>
            <pre><?php

try {
    $pdo = new PDO('mysql:host=' . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    if (!file_exists($sqlFile)) {
        throw new Exception('No se encontró database/schema.sql ni database.sql');
    }

    $sqlContent = file_get_contents($sqlFile);
    $sqlContent = preg_replace('/--.*$/m', '', $sqlContent);
    $queries = array_filter(array_map('trim', explode(';', $sqlContent)));

    echo "Conexión a MySQL: OK\n";
    echo "Archivo SQL: " . basename($sqlFile) . "\n\n";

    foreach ($queries as $query) {
        if ($query === '') continue;
        try {
            $pdo->exec($query);
            echo "✓ Consulta ejecutada\n";
        } catch (Exception $e) {
            echo "⚠ " . $e->getMessage() . "\n";
        }
    }

    echo "\n--- Migraciones adicionales ---\n";
    $migrations = [
        "ALTER TABLE plataforma_educativa.usuarios ADD COLUMN grado TINYINT UNSIGNED DEFAULT NULL AFTER rol",
        "ALTER TABLE plataforma_educativa.usuarios ADD COLUMN paralelo ENUM('A','B','C','D') DEFAULT NULL AFTER grado",
        "UPDATE plataforma_educativa.materias SET imagen = 'ciencias-naturales.jpg' WHERE nombre LIKE '%Ciencias%'",
        "UPDATE plataforma_educativa.materias SET imagen = 'estudios-sociales.jpg' WHERE nombre LIKE '%Estudios%'",
    ];
    foreach ($migrations as $migration) {
        try {
            $pdo->exec($migration);
            echo "✓ Migración aplicada\n";
        } catch (Exception $e) {
            echo "⚠ " . $e->getMessage() . "\n";
        }
    }

    echo "\nInstalación completada.\n";
    echo "Accede a: " . BASE_URL . "/login.php\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

            ?></pre>
            <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary">Ir al inicio</a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-secondary">Iniciar sesión</a>
        </div>
    </div>
</div>
</body>
</html>
