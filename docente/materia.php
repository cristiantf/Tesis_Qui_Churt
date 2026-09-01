<?php
/**
 * Panel de Materia - Docente
 */
$pageTitle = 'Panel de Materia';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];
$materiaId = intval($_GET['id'] ?? 0);
$materia = getMateriaById($materiaId);

if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/docente/index.php');
    exit;
}

$asignada = $db->prepare("SELECT id FROM materia_curso WHERE materia_id = ? AND docente_id = ? LIMIT 1");
$asignada->execute([$materiaId, $userId]);
$asignada = $asignada->fetch();

$theme = getMateriaThemeClass($materia['nombre']);

$totalActividades = $db->prepare("SELECT COUNT(*) FROM actividades WHERE materia_id = ? AND docente_id = ?");
$totalActividades->execute([$materiaId, $userId]);
$totalActividades = $totalActividades->fetchColumn();

$totalEstudiantes = $db->prepare("
    SELECT COUNT(DISTINCT u.id) 
    FROM usuarios u
    JOIN materia_curso mc ON u.grado = mc.grado AND u.paralelo COLLATE utf8mb4_unicode_ci = mc.paralelo COLLATE utf8mb4_unicode_ci
    WHERE mc.materia_id = ? AND mc.docente_id = ? AND u.rol = 'estudiante' AND u.estado = 1
");
$totalEstudiantes->execute([$materiaId, $userId]);
$totalEstudiantes = $totalEstudiantes->fetchColumn();

$entregasPendientes = $db->prepare("
    SELECT COUNT(*) FROM entregas e
    JOIN actividades a ON e.actividad_id = a.id
    WHERE a.materia_id = ? AND a.docente_id = ? AND e.estado = 'entregada'
");
$entregasPendientes->execute([$materiaId, $userId]);
$entregasPendientes = $entregasPendientes->fetchColumn();

$ultimasEntregasPendientes = $db->prepare("
    SELECT e.id, e.fecha_entrega, e.contenido, u.nombre, u.apellido, a.titulo AS actividad_titulo, a.puntaje_maximo
    FROM entregas e
    JOIN actividades a ON e.actividad_id = a.id
    JOIN usuarios u ON e.estudiante_id = u.id
    WHERE a.materia_id = ? AND a.docente_id = ? AND e.estado = 'entregada'
    ORDER BY e.fecha_entrega DESC
    LIMIT 6
");
$ultimasEntregasPendientes->execute([$materiaId, $userId]);
$ultimasEntregasPendientes = $ultimasEntregasPendientes->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4 <?php echo $theme; ?>">
    <div class="materia-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="<?php echo getMateriaIcon($materia['nombre']); ?> me-2"></i><?php echo sanitize($materia['nombre']); ?></h2>
                <p><?php echo sanitize($materia['descripcion']); ?></p>
                <?php if (!$asignada): ?>
                <span class="badge bg-warning text-dark mt-2">No tienes esta materia asignada oficialmente</span>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/docente/index.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver al panel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card stat-primary">
                <div class="stat-number"><?php echo $totalActividades; ?></div>
                <div class="stat-label">Mis actividades</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-success">
                <div class="stat-number"><?php echo $totalEstudiantes; ?></div>
                <div class="stat-label">Estudiantes inscritos</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-warning">
                <div class="stat-number"><?php echo $entregasPendientes; ?></div>
                <div class="stat-label">Entregas por calificar</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/docente/actividades.php?materia=<?php echo $materiaId; ?>&nueva=1" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-journal-plus"></i></div>
                    <h6>Crear Actividades</h6>
                    <small>Metodologías activas</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/docente/recursos.php?materia=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-cloud-upload"></i></div>
                    <h6>Subir Recursos Digitales</h6>
                    <small>Materiales de apoyo</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/docente/calificaciones.php?materia=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-clipboard-check"></i></div>
                    <h6>Evaluar Estudiantes</h6>
                    <small>Calificar entregas</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/docente/foro.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-chat-dots"></i></div>
                    <h6>Foro de Discusión</h6>
                    <small>Debate colaborativo</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/docente/progreso.php?materia_id=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-bar-chart-line"></i></div>
                    <h6>Seguimiento de Progreso</h6>
                    <small>Avance de estudiantes</small>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Entregas pendientes</h5>
                        <small class="text-muted">Accede rápidamente a las entregas que debes calificar para esta materia.</small>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/docente/calificaciones.php?materia=<?php echo $materiaId; ?>" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($ultimasEntregasPendientes)): ?>
                        <div class="empty-state">
                            <i class="bi bi-clipboard-x"></i>
                            <h5 class="mb-2">No hay entregas pendientes</h5>
                            <p class="small">Aún no hay trabajos entregados por calificar en esta materia.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($ultimasEntregasPendientes as $entrega): ?>
                                <a href="<?php echo BASE_URL; ?>/docente/calificaciones.php?materia=<?php echo $materiaId; ?>#entrega-<?php echo $entrega['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold"><?php echo sanitize($entrega['nombre'] . ' ' . $entrega['apellido']); ?></div>
                                        <div class="text-muted small"><?php echo sanitize($entrega['actividad_titulo']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary mb-1"><?php echo number_format($entrega['puntaje_maximo'], 0); ?> pts</span>
                                        <div class="small text-muted"><?php echo date('d/m H:i', strtotime($entrega['fecha_entrega'])); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
