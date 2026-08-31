<?php
/**
 * Seguimiento de Progreso - Panel Docente
 */
$pageTitle = 'Seguimiento de Progreso';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Materias del docente
$materias = $db->query("SELECT m.* FROM materias m JOIN materia_docente md ON m.id = md.materia_id WHERE md.docente_id = $userId")->fetchAll();

$materiaSeleccionada = isset($_GET['materia_id']) ? intval($_GET['materia_id']) : ($materias[0]['id'] ?? 0);

$alumnos = [];
if ($materiaSeleccionada > 0) {
    // Obtener estudiantes de la materia y su rendimiento/progreso
    $stmt = $db->prepare("
        SELECT u.id, u.nombre, u.apellido, u.email,
               COUNT(e.id) as entregas_realizadas,
               AVG(c.nota) as promedio_nota,
               (SELECT COUNT(*) FROM actividades WHERE materia_id = ? AND estado = 'publicada') as total_actividades
        FROM usuarios u
        JOIN materia_estudiante me ON u.id = me.estudiante_id
        LEFT JOIN entregas e ON u.id = e.estudiante_id AND e.actividad_id IN (SELECT id FROM actividades WHERE materia_id = ?)
        LEFT JOIN calificaciones c ON e.id = c.entrega_id
        WHERE me.materia_id = ? AND u.estado = 1
        GROUP BY u.id, u.nombre, u.apellido, u.email
        ORDER BY u.apellido, u.nombre
    ");
    $stmt->execute([$materiaSeleccionada, $materiaSeleccionada, $materiaSeleccionada]);
    $alumnos = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-bar-chart me-2"></i>Seguimiento de Progreso</h3>

    <!-- Selector de Materia -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Seleccionar Materia</label>
                    <select name="materia_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $materiaSeleccionada == $m['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($m['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Progreso de estudiantes -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-people me-2"></i>Rendimiento de los Estudiantes
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gray-100);">
                        <tr>
                            <th class="border-0">Estudiante</th>
                            <th class="border-0 text-center">Actividades Entregadas</th>
                            <th class="border-0">Progreso</th>
                            <th class="border-0 text-center">Promedio</th>
                            <th class="border-0">Nivel de Rendimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($alumnos)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay estudiantes inscritos en esta materia.</td></tr>
                        <?php else: foreach ($alumnos as $a): 
                            $totalAct = intval($a['total_actividades']);
                            $entregadas = intval($a['entregas_realizadas']);
                            $porcentaje = $totalAct > 0 ? round(($entregadas / $totalAct) * 100) : 0;
                            $promedio = $a['promedio_nota'] !== null ? floatval($a['promedio_nota']) : null;
                            
                            // Determinar nivel de rendimiento
                            $nivel = 'Sin Calificaciones';
                            $claseNivel = 'secondary';
                            if ($promedio !== null) {
                                if ($promedio >= 90) { $nivel = 'Excelente'; $claseNivel = 'success'; }
                                elseif ($promedio >= 75) { $nivel = 'Bueno'; $claseNivel = 'primary'; }
                                elseif ($promedio >= 60) { $nivel = 'Aceptable'; $claseNivel = 'warning'; }
                                else { $nivel = 'Necesita Refuerzo'; $claseNivel = 'danger'; }
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width:35px;height:35px;font-size:0.75rem;">
                                        <?php echo strtoupper(substr($a['nombre'],0,1).substr($a['apellido'],0,1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo sanitize($a['nombre'] . ' ' . $a['apellido']); ?></div>
                                        <div class="text-muted small"><?php echo sanitize($a['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold"><?php echo $entregadas; ?></span> / <?php echo $totalAct; ?>
                            </td>
                            <td style="width: 25%;">
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $porcentaje; ?>%;"></div>
                                    </div>
                                    <span class="small fw-bold"><?php echo $porcentaje; ?>%</span>
                                </div>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                <?php echo $promedio !== null ? number_format($promedio, 1) : '-'; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $claseNivel; ?>"><?php echo $nivel; ?></span>
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
