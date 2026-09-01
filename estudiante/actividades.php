<?php
/**
 * Actividades - Panel Estudiante
 */
$pageTitle = 'Actividades';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];

$gradoEstudiante = intval($_SESSION['user_grado'] ?? 0);
$paraleloEstudiante = trim($_SESSION['user_paralelo'] ?? '');

$materias = [];
if ($gradoEstudiante > 0 && $paraleloEstudiante !== '') {
    $stmt = $db->prepare("
        SELECT m.* 
        FROM materias m 
        JOIN materia_curso mc ON m.id = mc.materia_id 
        WHERE mc.grado = ? AND mc.paralelo = ?
    ");
    $stmt->execute([$gradoEstudiante, $paraleloEstudiante]);
    $materias = $stmt->fetchAll();
}
$materiasIds = array_column($materias, 'id');
$inClause = $materiasIds ? implode(',', $materiasIds) : '0';

// Procesar entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actividad_id'])) {
    $actividad_id = intval($_POST['actividad_id']);
    $contenido = trim($_POST['contenido'] ?? '');
    
    // Verificar que la actividad exista y esté publicada
    $actCheck = $db->prepare("SELECT id FROM actividades WHERE id = ? AND materia_id IN ($inClause) AND grado = ? AND paralelo = ? AND estado = 'publicada'");
    $actCheck->execute([$actividad_id, $gradoEstudiante, $paraleloEstudiante]);
    
    if ($actCheck->fetch()) {
        // Verificar si ya entregó
        $entCheck = $db->prepare("SELECT id FROM entregas WHERE actividad_id = ? AND estudiante_id = ?");
        $entCheck->execute([$actividad_id, $userId]);
        
        if ($entCheck->fetch()) {
            setFlashMessage('warning', 'Ya has realizado una entrega para esta actividad.');
        } else {
            // Guardar entrega
            $stmt = $db->prepare("INSERT INTO entregas (actividad_id, estudiante_id, contenido) VALUES (?, ?, ?)");
            $stmt->execute([$actividad_id, $userId, $contenido]);
            setFlashMessage('success', 'Actividad entregada correctamente de manera virtual.');
        }
    }
    header('Location: ' . BASE_URL . '/estudiante/actividades.php');
    exit;
}

// Ver actividad específica para realizarla
$verActId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$actividad = null;
$entrega = null;

if ($verActId) {
    $stmt = $db->prepare("
        SELECT a.*, m.nombre as materia_nombre
        FROM actividades a
        JOIN materias m ON a.materia_id = m.id
        WHERE a.id = ? AND a.materia_id IN ($inClause) AND a.grado = ? AND a.paralelo = ? AND a.estado = 'publicada'
    ");
    $stmt->execute([$verActId, $gradoEstudiante, $paraleloEstudiante]);
    $actividad = $stmt->fetch();
    
    if ($actividad) {
        $entStmt = $db->prepare("
            SELECT e.*, c.nota, c.retroalimentacion
            FROM entregas e
            LEFT JOIN calificaciones c ON e.id = c.entrega_id
            WHERE e.actividad_id = ? AND e.estudiante_id = ?
        ");
        $entStmt->execute([$verActId, $userId]);
        $entrega = $entStmt->fetch();
    }
}

// Filtrar por materia
$filtroMateria = $_GET['materia_id'] ?? '';

// Listar todas las actividades publicadas de sus materias
$sql = "
    SELECT a.*, m.nombre as materia_nombre, 
           e.estado as entrega_estado, e.id as entrega_id, c.nota
    FROM actividades a
    JOIN materias m ON a.materia_id = m.id
    LEFT JOIN entregas e ON a.id = e.actividad_id AND e.estudiante_id = ?
    LEFT JOIN calificaciones c ON e.id = c.entrega_id
    WHERE a.materia_id IN ($inClause) AND a.grado = ? AND a.paralelo = ? AND a.estado = 'publicada'
";
$params = [$userId, $gradoEstudiante, $paraleloEstudiante];

if ($filtroMateria) {
    $sql .= " AND a.materia_id = ?";
    $params[] = $filtroMateria;
}
$sql .= " ORDER BY a.fecha_limite ASC, a.fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$actividades = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <?php if ($verActId && $actividad): ?>
    <!-- Vista detalle de Actividad -->
    <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left me-1"></i>Volver a la lista</a>
    
    <div class="card mb-4" style="border-left: 5px solid var(--primary);">
        <div class="card-body">
            <span class="badge bg-primary mb-2"><?php echo sanitize($actividad['materia_nombre']); ?></span>
            <span class="badge bg-light text-dark mb-2"><i class="<?php echo getMetodologiaIcon($actividad['tipo_metodologia']); ?> me-1"></i><?php echo getMetodologiaName($actividad['tipo_metodologia']); ?></span>
            
            <h3 class="fw-bold mb-3"><?php echo sanitize($actividad['titulo']); ?></h3>
            <div class="row mb-3 font-monospace small">
                <div class="col-sm-6 text-muted">
                    <i class="bi bi-calendar-event me-1"></i>Inicio: <?php echo $actividad['fecha_inicio'] ? date('d/m/Y', strtotime($actividad['fecha_inicio'])) : '-'; ?>
                </div>
                <div class="col-sm-6 text-muted text-sm-end">
                    <i class="bi bi-calendar-check me-1"></i>Límite: <?php echo $actividad['fecha_limite'] ? date('d/m/Y', strtotime($actividad['fecha_limite'])) : '-'; ?>
                </div>
            </div>
            
            <h6 class="fw-bold"><i class="bi bi-card-text me-1"></i>Descripción de la Actividad:</h6>
            <div class="p-3 bg-light rounded mb-3">
                <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?php echo sanitize($actividad['descripcion']); ?></p>
            </div>
            
            <?php if ($actividad['instrucciones']): ?>
            <h6 class="fw-bold"><i class="bi bi-list-check me-1"></i>Instrucciones de Metodología Activa:</h6>
            <div class="p-3 bg-light rounded mb-3 border-start border-3 border-warning">
                <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?php echo sanitize($actividad['instrucciones']); ?></p>
            </div>
            <?php endif; ?>

            <div class="text-end">
                <span class="badge bg-secondary p-2">Puntaje Máximo: <?php echo $actividad['puntaje_maximo']; ?></span>
            </div>
        </div>
    </div>

    <!-- Sección de entrega -->
    <div class="card">
        <div class="card-header"><i class="bi bi-cloud-arrow-up me-2"></i>Mi Entrega</div>
        <div class="card-body">
            <?php if ($entrega): ?>
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="alert alert-success d-flex align-items-center mb-0">
                            <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Actividad Entregada</h6>
                                <small>Entregado el: <?php echo date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])); ?></small>
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-light rounded">
                            <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?php echo sanitize($entrega['contenido']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center border-start">
                        <?php if ($entrega['nota'] !== null): ?>
                            <div class="text-muted small mb-1">Tu calificación</div>
                            <div class="grade-display mx-auto grade-excellent mb-2 pulse">
                                <?php echo number_format($entrega['nota'], 0); ?>
                            </div>
                            <span class="small fw-semibold text-muted">/<?php echo $actividad['puntaje_maximo']; ?></span>
                            <?php if ($entrega['retroalimentacion']): ?>
                            <div class="p-2 mt-2 bg-light rounded small text-start">
                                <strong>Retroalimentación:</strong><br>
                                <span class="italic text-muted">“<?php echo sanitize($entrega['retroalimentacion']); ?>”</span>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-muted small">Estado de evaluación</div>
                            <span class="badge bg-warning text-dark mt-2 p-2"><i class="bi bi-hourglass-split me-1"></i>Pendiente de Calificar</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="actividad_id" value="<?php echo $actividad['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tu Respuesta / Desarrollo de la metodología active:</label>
                        <textarea name="contenido" class="form-control" rows="5" placeholder="Escribe aquí tu entrega o desarrollo..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Enviar Actividad</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <!-- Lista de Actividades -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-journal-text me-2"></i>Mis Actividades</h3>
        
        <!-- Filtro rápido -->
        <form method="GET" class="d-flex gap-2">
            <select name="materia_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todas las materias</option>
                <?php foreach ($materias as $m): ?>
                <option value="<?php echo $m['id']; ?>" <?php echo $filtroMateria == $m['id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($m['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($actividades)): ?>
    <div class="card"><div class="card-body empty-state"><i class="bi bi-journal-x"></i><h5>Sin actividades por el momento</h5><p>¡Buen trabajo! Disfruta de tu tiempo libre.</p></div></div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($actividades as $a): ?>
        <div class="col-lg-6">
            <div class="activity-card <?php echo $a['tipo_metodologia']; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-primary mb-1"><?php echo sanitize($a['materia_nombre']); ?></span>
                        <span class="badge bg-light text-dark mb-1"><i class="<?php echo getMetodologiaIcon($a['tipo_metodologia']); ?> me-1"></i><?php echo getMetodologiaName($a['tipo_metodologia']); ?></span>
                        <h5 class="fw-bold text-dark mt-1 mb-1"><?php echo sanitize($a['titulo']); ?></h5>
                        <p class="text-muted small mb-2"><?php echo sanitize(substr($a['descripcion'], 0, 100)); ?>...</p>
                    </div>
                    <div>
                        <?php if ($a['entrega_estado'] === 'calificada'): ?>
                            <span class="badge bg-success mb-1">Nota: <?php echo number_format($a['nota'], 1); ?></span>
                        <?php elseif ($a['entrega_estado'] === 'entregada'): ?>
                            <span class="badge bg-info mb-1"><i class="bi bi-check-lg"></i> Entregada</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark mb-1"><i class="bi bi-hourglass"></i> Pendiente</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
                    <span class="text-muted">
                        <?php if ($a['fecha_limite']): ?>
                        <i class="bi bi-calendar me-1"></i>Límite: <?php echo date('d/m/Y', strtotime($a['fecha_limite'])); ?>
                        <?php endif; ?>
                    </span>
                    <a href="?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <?php echo $a['entrega_id'] ? 'Ver Detalles' : 'Realizar Actividad'; ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
