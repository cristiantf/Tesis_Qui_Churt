<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$r = $db->query('DESCRIBE juegos');
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Default'] ?? 'NULL') . "\n";
}
