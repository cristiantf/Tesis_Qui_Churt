<?php
/**
 * Reportes Generales - Panel Administrador
 */
$pageTitle = 'Reportes';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

// Datos para reportes
$usuariosPorRol = $db->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol")->fetchAll();
$actividadesPorEstado = $db->query("SELECT estado, COUNT(*) as total FROM actividades GROUP BY estado")->fetchAll();
$entregasPorEstado = $db->query("SELECT estado, COUNT(*) as total FROM entregas GROUP BY estado")->fetchAll();
$promedioNotas = $db->query("SELECT AVG(nota) as promedio FROM calificaciones")->fetchColumn();
$actividadesPorMetodologia = $db->query("SELECT tipo_metodologia, COUNT(*) as total FROM actividades GROUP BY tipo_metodologia")->fetchAll();

$materiaStats = $db->query("
    SELECT m.nombre, 
           (SELECT COUNT(DISTINCT docente_id) FROM materia_curso mc WHERE mc.materia_id = m.id) as docentes,
           (SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN materia_curso mc ON u.grado = mc.grado AND u.paralelo COLLATE utf8mb4_unicode_ci = mc.paralelo COLLATE utf8mb4_unicode_ci WHERE mc.materia_id = m.id AND u.rol = 'estudiante' AND u.estado = 1) as estudiantes,
           (SELECT COUNT(*) FROM actividades a WHERE a.materia_id = m.id) as actividades,
           (SELECT COUNT(*) FROM recursos r WHERE r.materia_id = m.id) as recursos
    FROM materias m
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-graph-up me-2"></i>Reportes Generales</h3>

    <!-- Resumen rápido -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-primary">
                <div class="stat-number"><?php echo $promedioNotas ? number_format($promedioNotas, 1) : 'N/A'; ?></div>
                <div class="stat-label">Promedio General de Notas</div>
            </div>
        </div>
        <?php foreach ($materiaStats as $ms): ?>
        <div class="col-md-3">
            <div class="card p-3">
                <h6 class="fw-bold"><?php echo sanitize($ms['nombre']); ?></h6>
                <div class="d-flex gap-3 small text-muted">
                    <span><i class="bi bi-person-workspace"></i> <?php echo $ms['docentes']; ?> doc.</span>
                    <span><i class="bi bi-mortarboard"></i> <?php echo $ms['estudiantes']; ?> est.</span>
                    <span><i class="bi bi-journal"></i> <?php echo $ms['actividades']; ?> act.</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Gráfico usuarios por rol -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-people me-2"></i>Usuarios por Rol</div>
                <div class="card-body"><canvas id="chartRoles"></canvas></div>
            </div>
        </div>
        <!-- Gráfico actividades por estado -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-journal-text me-2"></i>Actividades por Estado</div>
                <div class="card-body"><canvas id="chartActividades"></canvas></div>
            </div>
        </div>
        <!-- Gráfico metodologías -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><i class="bi bi-lightbulb me-2"></i>Metodologías Usadas</div>
                <div class="card-body"><canvas id="chartMetodologias"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Tabla resumen por materia -->
    <div class="card mt-4">
        <div class="card-header"><i class="bi bi-table me-2"></i>Resumen por Materia</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gray-100);">
                        <tr>
                            <th class="border-0">Materia</th>
                            <th class="border-0 text-center">Docentes</th>
                            <th class="border-0 text-center">Estudiantes</th>
                            <th class="border-0 text-center">Actividades</th>
                            <th class="border-0 text-center">Recursos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiaStats as $ms): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo sanitize($ms['nombre']); ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?php echo $ms['docentes']; ?></span></td>
                            <td class="text-center"><span class="badge bg-success"><?php echo $ms['estudiantes']; ?></span></td>
                            <td class="text-center"><span class="badge bg-warning"><?php echo $ms['actividades']; ?></span></td>
                            <td class="text-center"><span class="badge bg-info"><?php echo $ms['recursos']; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Usuarios por rol
new Chart(document.getElementById('chartRoles'), {
    type: 'pie',
    data: {
        labels: [<?php echo implode(',', array_map(function($r) { return "'" . ucfirst($r['rol']) . "'"; }, $usuariosPorRol)); ?>],
        datasets: [{ data: [<?php echo implode(',', array_column($usuariosPorRol, 'total')); ?>], backgroundColor: ['#e74c3c','#1a4b8c','#27ae60'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Actividades por estado
new Chart(document.getElementById('chartActividades'), {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($a) { return "'" . ucfirst($a['estado']) . "'"; }, $actividadesPorEstado)); ?>],
        datasets: [{ label: 'Cantidad', data: [<?php echo implode(',', array_column($actividadesPorEstado, 'total')); ?>], backgroundColor: ['#6c757d','#27ae60','#e74c3c'], borderRadius: 8 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Metodologías
const metLabels = [<?php echo implode(',', array_map(function($m) { 
    $names = ['abp'=>'ABP','clase_invertida'=>'Clase Inv.','gamificacion'=>'Gamificación','colaborativo'=>'Colaborativo','estudio_caso'=>'Est. Caso','debate'=>'Debate'];
    return "'" . ($names[$m['tipo_metodologia']] ?? $m['tipo_metodologia']) . "'"; 
}, $actividadesPorMetodologia)); ?>];
new Chart(document.getElementById('chartMetodologias'), {
    type: 'doughnut',
    data: {
        labels: metLabels,
        datasets: [{ data: [<?php echo implode(',', array_column($actividadesPorMetodologia, 'total')); ?>], backgroundColor: ['#1a4b8c','#e8833a','#27ae60','#3498db','#f39c12','#e74c3c'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
