<?php
/**
 * Calificaciones - Panel Docente
 */
$pageTitle = 'Calificaciones';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

$materiaId = intval($_GET['materia'] ?? 0);

// Calificar entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrega_id = intval($_POST['entrega_id']);
    $nota = floatval($_POST['nota']);
    $retroalimentacion = trim($_POST['retroalimentacion'] ?? '');
    
    // Verificar que la entrega pertenece a una actividad del docente
    $check = $db->prepare("SELECT e.id FROM entregas e JOIN actividades a ON e.actividad_id = a.id WHERE e.id = ? AND a.docente_id = ?");
    $check->execute([$entrega_id, $userId]);
    
    if ($check->fetch()) {
        // Verificar si ya existe calificación
        $existing = $db->prepare("SELECT id FROM calificaciones WHERE entrega_id = ?");
        $existing->execute([$entrega_id]);
        
        if ($existing->fetch()) {
            $stmt = $db->prepare("UPDATE calificaciones SET nota = ?, retroalimentacion = ? WHERE entrega_id = ?");
            $stmt->execute([$nota, $retroalimentacion, $entrega_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO calificaciones (entrega_id, docente_id, nota, retroalimentacion) VALUES (?,?,?,?)");
            $stmt->execute([$entrega_id, $userId, $nota, $retroalimentacion]);
        }
        
        // Actualizar estado de la entrega
        $db->prepare("UPDATE entregas SET estado = 'calificada' WHERE id = ?")->execute([$entrega_id]);
        
        setFlashMessage('success', 'Calificación guardada correctamente.');
    }

    $redirectUrl = BASE_URL . '/docente/calificaciones.php';
    if ($materiaId) {
        $redirectUrl .= '?materia=' . $materiaId;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$whereMateria = '';
if ($materiaId) {
    $whereMateria = ' AND a.materia_id = ' . intval($materiaId);
}

// Obtener entregas (incluye grado/paralelo del estudiante)
$entregas = $db->query("
    SELECT e.*, u.nombre, u.apellido, u.email, u.grado AS estudiante_grado, u.paralelo AS estudiante_paralelo,
           a.titulo as actividad_titulo, a.puntaje_maximo,
           m.nombre as materia_nombre,
           c.nota, c.retroalimentacion, c.id as calificacion_id
    FROM entregas e
    JOIN actividades a ON e.actividad_id = a.id
    JOIN usuarios u ON e.estudiante_id = u.id
    JOIN materias m ON a.materia_id = m.id
    LEFT JOIN calificaciones c ON e.id = c.entrega_id
    WHERE a.docente_id = $userId $whereMateria
    ORDER BY e.estado ASC, e.fecha_entrega DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-clipboard-check me-2"></i>Calificaciones</h3>

    <?php if (empty($entregas)): ?>
    <div class="card"><div class="card-body empty-state"><i class="bi bi-clipboard-x"></i><h5>Sin entregas</h5><p>Aún no hay entregas de estudiantes.</p></div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-primary); color: white;">
                        <tr>
                            <th class="border-0">Estudiante</th>
                            <th class="border-0">Curso / Paralelo</th>
                            <th class="border-0">Contenido</th>
                            <th class="border-0">Actividad</th>
                            <th class="border-0">Materia</th>
                            <th class="border-0">Fecha Entrega</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0">Nota</th>
                            <th class="border-0 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entregas as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width:30px;height:30px;font-size:0.6rem;">
                                        <?php echo strtoupper(substr($e['nombre'],0,1).substr($e['apellido'],0,1)); ?>
                                    </div>
                                    <div>
                                        <strong class="small"><?php echo sanitize($e['nombre'] . ' ' . $e['apellido']); ?></strong>
                                        <?php if (empty($e['estudiante_grado']) && empty($e['estudiante_paralelo'])): ?>
                                            <div class="small text-danger">(Falta curso/paralelo)</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="small text-center">
                                <?php if (!empty($e['estudiante_grado']) || !empty($e['estudiante_paralelo'])): ?>
                                    <span class="badge bg-info text-dark">
                                        <?php echo intval($e['estudiante_grado']) ? intval($e['estudiante_grado']) . '°' : ''; ?> <?php echo sanitize($e['estudiante_paralelo'] ?? ''); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-truncate" style="max-width:260px;">
                                <?php echo sanitize(mb_strimwidth($e['contenido'] ?? '', 0, 120, '...')); ?>
                            </td>
                            <td class="small"><?php echo sanitize($e['actividad_titulo']); ?></td>
                            <td><span class="badge bg-primary small"><?php echo sanitize($e['materia_nombre']); ?></span></td>
                            <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($e['fecha_entrega'])); ?></td>
                            <td><span class="badge bg-<?php echo getEstadoBadge($e['estado']); ?>"><?php echo ucfirst($e['estado']); ?></span></td>
                            <td>
                                <?php if ($e['nota'] !== null): ?>
                                <span class="fw-bold"><?php echo number_format($e['nota'], 1); ?>/<?php echo $e['puntaje_maximo']; ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCalificar<?php echo $e['id']; ?>">
                                    <i class="bi bi-pencil me-1"></i><?php echo $e['nota'] !== null ? 'Editar' : 'Calificar'; ?>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Modal Calificar -->
                        <div class="modal fade" id="modalCalificar<?php echo $e['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="entrega_id" value="<?php echo $e['id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>Calificar Entrega</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3 p-3 rounded" style="background: var(--gray-100);">
                                                <strong><?php echo sanitize($e['nombre'] . ' ' . $e['apellido']); ?></strong><br>
                                                <small class="text-muted"><?php echo sanitize($e['actividad_titulo']); ?></small>
                                                <?php if ($e['contenido']): ?>
                                                <hr><p class="small mb-0"><?php echo nl2br(sanitize($e['contenido'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Nota (máx: <?php echo $e['puntaje_maximo']; ?>)</label>
                                                <input type="number" name="nota" class="form-control" min="0" max="<?php echo $e['puntaje_maximo']; ?>" 
                                                       step="0.5" value="<?php echo $e['nota'] ?? ''; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Retroalimentación</label>
                                                <textarea name="retroalimentacion" class="form-control" rows="3" placeholder="Escribe una retroalimentación constructiva..."><?php echo sanitize($e['retroalimentacion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
