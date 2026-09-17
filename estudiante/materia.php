<?php
/**
 * Panel de Materia - Estudiante
 */
$pageTitle = 'Mi Materia';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];
$materiaId = intval($_GET['id'] ?? 0);
$materia = getMateriaById($materiaId);

if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/estudiante/index.php');
    exit;
}

$gradoEstudiante = intval($_SESSION['user_grado'] ?? 0);
$paraleloEstudiante = trim($_SESSION['user_paralelo'] ?? '');

$inscrito = $db->prepare("SELECT id FROM materia_curso WHERE materia_id = ? AND grado = ? AND paralelo = ? LIMIT 1");
$inscrito->execute([$materiaId, $gradoEstudiante, $paraleloEstudiante]);
$inscrito = $inscrito->fetch();

$theme = getMateriaThemeClass($materia['nombre']);

$actividadesPendientes = $db->prepare("
    SELECT COUNT(*) FROM actividades a
    WHERE a.materia_id = ? AND a.grado = ? AND a.paralelo = ? AND a.estado = 'publicada'
      AND a.id NOT IN (SELECT actividad_id FROM entregas WHERE estudiante_id = ?)
");
$actividadesPendientes->execute([$materiaId, $gradoEstudiante, $paraleloEstudiante, $userId]);
$actividadesPendientes = $actividadesPendientes->fetchColumn();

$totalRecursos = $db->prepare("SELECT COUNT(*) FROM recursos WHERE materia_id = ? AND grado = ? AND paralelo = ?");
$totalRecursos->execute([$materiaId, $gradoEstudiante, $paraleloEstudiante]);
$totalRecursos = $totalRecursos->fetchColumn();

$promedio = $db->prepare("
    SELECT AVG(c.nota) FROM calificaciones c
    JOIN entregas e ON c.entrega_id = e.id
    JOIN actividades a ON e.actividad_id = a.id
    WHERE e.estudiante_id = ? AND a.materia_id = ?
");
$promedio->execute([$userId, $materiaId]);
$promedio = $promedio->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4 <?php echo $theme; ?>">
    <div class="materia-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="<?php echo getMateriaIcon($materia['nombre']); ?> me-2"></i><?php echo sanitize($materia['nombre']); ?></h2>
                <p><?php echo sanitize($materia['descripcion']); ?></p>
                <?php if (!$inscrito): ?>
                <span class="badge bg-warning text-dark mt-2">No estás inscrito en esta materia</span>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <img src="<?php echo getMateriaImageUrl($materia['imagen']); ?>" alt="" 
                     style="width:120px;height:80px;object-fit:cover;border-radius:12px;border:3px solid rgba(255,255,255,0.3);">
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card stat-warning">
                <div class="stat-number"><?php echo $actividadesPendientes; ?></div>
                <div class="stat-label">Actividades pendientes</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-info">
                <div class="stat-number"><?php echo $totalRecursos; ?></div>
                <div class="stat-label">Recursos disponibles</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card stat-success">
                <div class="stat-number"><?php echo $promedio ? number_format($promedio, 1) : '-'; ?></div>
                <div class="stat-label">Mi promedio en la materia</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/materias.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-book"></i></div>
                    <h6>Acceder a Materias</h6>
                    <small>Ver todas mis asignaturas</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php?materia_id=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-journal-text"></i></div>
                    <h6>Realizar Actividades</h6>
                    <small>Tareas de esta materia</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/calificaciones.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-award"></i></div>
                    <h6>Consultar Calificaciones</h6>
                    <small>Mis notas y promedios</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/retroalimentacion.php?materia_id=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-chat-square-heart"></i></div>
                    <h6>Recibir Retroalimentación</h6>
                    <small>Comentarios del docente</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/recursos.php?materia_id=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-collection-play"></i></div>
                    <h6>Explorar Recursos Interactivos</h6>
                    <small>Materiales de estudio</small>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/jugar.php?materia_id=<?php echo $materiaId; ?>" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-controller"></i></div>
                    <h6>Juegos Educativos</h6>
                    <small>Laberintos de conocimiento</small>
                </div>
            </a>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="<?php echo BASE_URL; ?>/estudiante/index.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>Volver a mi panel
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
