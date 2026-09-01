<?php
/**
 * Retroalimentación del Docente - Panel Estudiante
 */
$pageTitle = 'Retroalimentación';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];
$filtroMateria = $_GET['materia_id'] ?? '';
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

$sql = "
    SELECT c.*, a.titulo as actividad_titulo, a.puntaje_maximo, m.nombre as materia_nombre, m.id as materia_id,
           u.nombre as docente_nombre, u.apellido as docente_apellido
    FROM calificaciones c
    JOIN entregas e ON c.entrega_id = e.id
    JOIN actividades a ON e.actividad_id = a.id
    JOIN materias m ON a.materia_id = m.id
    JOIN usuarios u ON c.docente_id = u.id
    WHERE e.estudiante_id = ? AND c.retroalimentacion IS NOT NULL AND c.retroalimentacion != ''
";
$params = [$userId];

if ($filtroMateria) {
    $sql .= " AND a.materia_id = ?";
    $params[] = intval($filtroMateria);
}
$sql .= " ORDER BY c.fecha_calificacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$retroalimentaciones = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-chat-square-heart me-2"></i>Recibir Retroalimentación</h3>
            <p class="text-muted mb-0">Comentarios y orientaciones de tus docentes para mejorar tu aprendizaje.</p>
        </div>
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

    <?php if (empty($retroalimentaciones)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <i class="bi bi-chat-left-text"></i>
            <h5>Aún no tienes retroalimentación</h5>
            <p>Cuando tus docentes evalúen tus actividades, sus comentarios aparecerán aquí para guiar tu aprendizaje.</p>
            <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php" class="btn btn-primary btn-sm mt-2">
                <i class="bi bi-journal-text me-1"></i>Ver mis actividades
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($retroalimentaciones as $r): 
            $theme = getMateriaThemeClass($r['materia_nombre']);
        ?>
        <div class="col-md-6">
            <div class="card h-100 <?php echo $theme; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-1"><?php echo sanitize($r['actividad_titulo']); ?></h6>
                            <span class="badge bg-primary small"><?php echo sanitize($r['materia_nombre']); ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success"><?php echo number_format($r['nota'], 1); ?>/<?php echo $r['puntaje_maximo']; ?></span>
                        </div>
                    </div>
                    <div class="forum-reply mb-3">
                        <p class="mb-0 small">"<?php echo sanitize($r['retroalimentacion']); ?>"</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="bi bi-person-workspace me-1"></i>
                            <?php echo sanitize($r['docente_nombre'] . ' ' . $r['docente_apellido']); ?>
                        </small>
                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($r['fecha_calificacion'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
