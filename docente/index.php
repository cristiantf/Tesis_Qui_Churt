<?php
/**
 * Dashboard del Docente
 */
$pageTitle = 'Dashboard Docente';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Materias asignadas
$materias = $db->query("
    SELECT DISTINCT m.* 
    FROM materias m 
    JOIN materia_curso mc ON m.id = mc.materia_id 
    WHERE mc.docente_id = $userId
")->fetchAll();

$materiasIds = array_column($materias, 'id');
$inClause = $materiasIds ? implode(',', $materiasIds) : '0';

// Cursos y paralelos asignados
$cursoParalelosData = $db->prepare("SELECT DISTINCT grado, paralelo FROM materia_curso WHERE docente_id = ? ORDER BY grado, paralelo");
$cursoParalelosData->execute([$userId]);
$cursoParalelos = $cursoParalelosData->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$totalActividades = $db->query("SELECT COUNT(*) FROM actividades WHERE docente_id = $userId")->fetchColumn();
$actividadesActivas = $db->query("SELECT COUNT(*) FROM actividades WHERE docente_id = $userId AND estado = 'publicada'")->fetchColumn();
$totalEntregas = $db->query("SELECT COUNT(*) FROM entregas e JOIN actividades a ON e.actividad_id = a.id WHERE a.docente_id = $userId")->fetchColumn();
$entregasPendientes = $db->query("SELECT COUNT(*) FROM entregas e JOIN actividades a ON e.actividad_id = a.id WHERE a.docente_id = $userId AND e.estado = 'entregada'")->fetchColumn();
$totalEstudiantes = $db->query("SELECT COUNT(DISTINCT u.id) FROM usuarios u JOIN materia_curso mc ON u.grado = mc.grado AND u.paralelo COLLATE utf8mb4_unicode_ci = mc.paralelo COLLATE utf8mb4_unicode_ci WHERE mc.docente_id = $userId AND u.rol = 'estudiante' AND u.estado = 1")->fetchColumn();
$totalRecursos = $db->query("SELECT COUNT(*) FROM recursos WHERE docente_id = $userId")->fetchColumn();

// Últimas entregas pendientes
$ultimasEntregas = $db->query("
    SELECT e.*, u.nombre, u.apellido, a.titulo as actividad_titulo, m.nombre as materia_nombre
    FROM entregas e
    JOIN actividades a ON e.actividad_id = a.id
    JOIN usuarios u ON e.estudiante_id = u.id
    JOIN materias m ON a.materia_id = m.id
    WHERE a.docente_id = $userId AND e.estado = 'entregada'
    ORDER BY e.fecha_entrega DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col">
                <h2><i class="bi bi-person-workspace me-2"></i>Panel del Docente</h2>
                <p>Bienvenido, <?php echo sanitize(getFullName()); ?>. Gestiona tus actividades y estudiantes.</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/docente/actividades.php?nueva=1" class="btn btn-secondary">
                    <i class="bi bi-plus-lg me-1"></i>Nueva Actividad
                </a>
                <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo count($materias); ?></div><div class="stat-label">Materias</div></div>
                    <div class="stat-icon"><i class="bi bi-journal-bookmark"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-secondary">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo $totalActividades; ?></div><div class="stat-label">Actividades</div></div>
                    <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo $totalEstudiantes; ?></div><div class="stat-label">Estudiantes</div></div>
                    <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo count($cursoParalelos); ?></div><div class="stat-label">Cursos/Paralelos</div></div>
                    <div class="stat-icon"><i class="bi bi-grid-1x2"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo $entregasPendientes; ?></div><div class="stat-label">Por Calificar</div></div>
                    <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo $totalRecursos; ?></div><div class="stat-label">Recursos</div></div>
                    <div class="stat-icon"><i class="bi bi-folder"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-danger">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-number"><?php echo $actividadesActivas; ?></div><div class="stat-label">Activas</div></div>
                    <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Materias y cursos asignados -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-bookmark me-2"></i>Mis materias y cursos</span>
                </div>
                <div class="card-body">
                    <?php if (empty($materias)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h5>Sin materias asignadas</h5>
                        <p class="small">El administrador debe asignarte materias.</p>
                    </div>
                    <?php else: ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($materias as $m): ?>
                        <a href="<?php echo BASE_URL; ?>/docente/materia.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-book me-1"></i><?php echo sanitize($m['nombre']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($cursoParalelos)): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>No tienes cursos/paralelos asignados aún.
                    </div>
                    <?php else: ?>
                    <div class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-2"><i class="bi bi-grid-1x2 me-2"></i>Paralelos asignados</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($cursoParalelos as $cp): ?>
                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                <?php echo intval($cp['grado']) . '° - ' . sanitize($cp['paralelo']); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Entregas pendientes -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-inbox me-2"></i>Entregas por revisar</span>
                    <a href="<?php echo BASE_URL; ?>/docente/calificaciones.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($ultimasEntregas)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>Sin entregas pendientes</h5>
                        <p class="small">No hay entregas por calificar.</p>
                    </div>
                    <?php else: foreach ($ultimasEntregas as $e): ?>
                    <div class="activity-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo sanitize($e['nombre'] . ' ' . $e['apellido']); ?></h6>
                                <p class="text-muted small mb-1"><?php echo sanitize($e['actividad_titulo']); ?></p>
                                <span class="badge bg-primary small"><?php echo sanitize($e['materia_nombre']); ?></span>
                            </div>
                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($e['fecha_entrega'])); ?></small>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Acceso directo por materia -->
    <?php $todasMaterias = $db->query("SELECT * FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll(); ?>
    <?php if (!empty($todasMaterias)): ?>
    <div class="mb-3">
        <h5 class="section-title"><i class="bi bi-book me-2"></i>Acceso por materia</h5>
        <p class="section-subtitle">Cada asignatura ofrece recursos visuales, actividades y seguimiento para impulsar la participación estudiantil.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($todasMaterias as $m): 
            $asignada = in_array($m['id'], $materiasIds);
            $descripcionMateria = !empty(trim($m['descripcion'])) ? sanitize($m['descripcion']) : 'Explora recursos, evaluaciones y tareas para fortalecer el aprendizaje en esta asignatura.';
            $tituloMateria = sanitize($m['nombre']);
        ?>
        <div class="col-md-6">
            <a href="<?php echo BASE_URL; ?>/docente/materia.php?id=<?php echo $m['id']; ?>" class="text-decoration-none">
                <div class="subject-access-card">
                    <img src="<?php echo getMateriaImageUrl($m['imagen']); ?>" alt="<?php echo $tituloMateria; ?>">
                    <div class="subject-overlay">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold mb-0"><i class="<?php echo getMateriaIcon($m['nombre']); ?> me-2"></i><?php echo $tituloMateria; ?></h5>
                            <span class="badge bg-light text-dark"><?php echo $asignada ? 'Asignada' : 'Vista'; ?></span>
                        </div>
                        <p class="small mb-2" style="max-width: 90%;"><?php echo $descripcionMateria; ?></p>
                        <small><?php echo $asignada ? 'Gestiona clases, tareas y seguimiento desde aquí.' : 'Revisa el contenido y prepara actividades para tus estudiantes.'; ?></small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
