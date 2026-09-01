<?php
/**
 * Explorar Recursos - Panel Estudiante
 */
$pageTitle = 'Recursos de Estudio';
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

// Filtrar por materia
$filtroMateria = $_GET['materia_id'] ?? '';

$sql = "
    SELECT r.*, m.nombre as materia_nombre, u.nombre as docente_nombre, u.apellido as docente_apellido
    FROM recursos r
    JOIN materias m ON r.materia_id = m.id
    JOIN usuarios u ON r.docente_id = u.id
    WHERE r.materia_id IN ($inClause) AND r.grado = ? AND r.paralelo = ? AND r.estado = 1
";
$params = [$gradoEstudiante, $paraleloEstudiante];

if ($filtroMateria) {
    $sql .= " AND r.materia_id = ?";
    $params[] = $filtroMateria;
}
$sql .= " ORDER BY r.fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$recursos = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-collection me-2"></i>Recursos de Estudio</h3>
        
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

    <div class="row g-3">
        <?php if (empty($recursos)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body empty-state">
                    <i class="bi bi-folder-x"></i>
                    <h5>No hay recursos subidos</h5>
                    <p>Tus docentes aún no han subido recursos para tus materias asignadas.</p>
                </div>
            </div>
        </div>
        <?php else: foreach ($recursos as $r): ?>
        <div class="col-md-6 col-lg-4">
            <div class="resource-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start mb-3">
                    <div class="resource-icon <?php echo $r['tipo']; ?> me-3">
                        <i class="bi bi-<?php echo $r['tipo']==='documento'?'file-earmark-text':($r['tipo']==='video'?'play-circle':($r['tipo']==='imagen'?'image':($r['tipo']==='enlace'?'link-45deg':($r['tipo']==='presentacion'?'easel':'file-earmark')))); ?>"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1"><?php echo sanitize($r['titulo']); ?></h6>
                        <p class="text-muted small mb-0"><?php echo sanitize($r['descripcion']); ?></p>
                        <small class="text-muted" style="font-size:0.75rem;">Subido por: <?php echo sanitize($r['docente_nombre'] . ' ' . $r['docente_apellido']); ?></small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div>
                        <span class="badge bg-primary small"><?php echo sanitize($r['materia_nombre']); ?></span>
                    </div>
                    <?php if ($r['tipo'] === 'enlace'): ?>
                    <a href="<?php echo sanitize($r['archivo']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Visitar Link
                    </a>
                    <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/uploads/recursos/<?php echo $r['archivo']; ?>" download class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Descargar
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
