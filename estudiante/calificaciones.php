<?php
/**
 * Consultar Calificaciones - Panel Estudiante
 */
$pageTitle = 'Mis Calificaciones';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];

// Obtener todas las calificaciones del estudiante
$calificaciones = $db->query("
    SELECT c.*, a.titulo as actividad_titulo, a.puntaje_maximo, m.nombre as materia_nombre
    FROM calificaciones c
    JOIN entregas e ON c.entrega_id = e.id
    JOIN actividades a ON e.actividad_id = a.id
    JOIN materias m ON a.materia_id = m.id
    WHERE e.estudiante_id = $userId
    ORDER BY c.fecha_calificacion DESC
")->fetchAll();

// Promedio por materia
$promediosMateria = $db->query("
    SELECT m.nombre as materia_nombre, AVG(c.nota) as promedio
    FROM calificaciones c
    JOIN entregas e ON c.entrega_id = e.id
    JOIN actividades a ON e.actividad_id = a.id
    JOIN materias m ON a.materia_id = m.id
    WHERE e.estudiante_id = $userId
    GROUP BY m.id, m.nombre
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-award me-2"></i>Mis Calificaciones</h3>

    <!-- Resumen de promedios -->
    <?php if (!empty($promediosMateria)): ?>
    <div class="row g-3 mb-4">
        <?php foreach ($promediosMateria as $pm): 
            $prom = floatval($pm['promedio']);
            $clase = 'success';
            if ($prom < 60) $clase = 'danger';
            elseif ($prom < 75) $clase = 'warning';
            elseif ($prom < 90) $clase = 'primary';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card p-3 d-flex flex-row justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-1"><?php echo sanitize($pm['materia_nombre']); ?></h6>
                    <small class="text-muted">Promedio de la asignatura</small>
                </div>
                <div class="grade-display grade-<?php echo $clase === 'primary' ? 'good' : ($clase === 'success' ? 'excellent' : ($clase === 'warning' ? 'average' : 'poor')); ?>">
                    <?php echo number_format($prom, 1); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Listado detallado -->
    <div class="card">
        <div class="card-header"><i class="bi bi-list-check me-2"></i>Detalle de Evaluaciones</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gray-100);">
                        <tr>
                            <th class="border-0">Actividad</th>
                            <th class="border-0">Materia</th>
                            <th class="border-0">Fecha Evaluación</th>
                            <th class="border-0 text-end">Nota Obtenida</th>
                            <th class="border-0">Retroalimentación del Docente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($calificaciones)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Aún no cuentas con actividades calificadas.</td></tr>
                        <?php else: foreach ($calificaciones as $c): ?>
                        <tr>
                            <td class="fw-semibold small"><?php echo sanitize($c['actividad_titulo']); ?></td>
                            <td><span class="badge bg-primary small"><?php echo sanitize($c['materia_nombre']); ?></span></td>
                            <td class="small text-muted"><?php echo date('d/m/Y', strtotime($c['fecha_calificacion'])); ?></td>
                            <td class="text-end fw-bold text-success fs-5">
                                <?php echo number_format($c['nota'], 1); ?> <span class="text-muted small fw-normal">/<?php echo $c['puntaje_maximo']; ?></span>
                            </td>
                            <td class="small text-muted italic">
                                <?php echo $c['retroalimentacion'] ? '“' . sanitize($c['retroalimentacion']) . '”' : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
