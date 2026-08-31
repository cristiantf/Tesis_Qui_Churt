<?php
/**
 * Dashboard del Estudiante
 */
$pageTitle = 'Dashboard Estudiante';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];

// Materias inscritas
$materias = $db->query("
    SELECT m.* FROM materias m 
    JOIN materia_estudiante me ON m.id = me.materia_id 
    WHERE me.estudiante_id = $userId
")->fetchAll();

$materiasIds = array_column($materias, 'id');
$inClause = $materiasIds ? implode(',', $materiasIds) : '0';

// Actividades pendientes
$actividadesPendientes = $db->query("
    SELECT a.*, m.nombre as materia_nombre
    FROM actividades a
    JOIN materias m ON a.materia_id = m.id
    WHERE a.materia_id IN ($inClause) AND a.estado = 'publicada'
      AND a.id NOT IN (SELECT actividad_id FROM entregas WHERE estudiante_id = $userId)
    ORDER BY a.fecha_limite ASC LIMIT 5
")->fetchAll();

// Estadísticas del estudiante
$totalMaterias = count($materias);
$totalEntregadas = $db->query("SELECT COUNT(*) FROM entregas WHERE estudiante_id = $userId")->fetchColumn();
$totalEvaluadas = $db->query("SELECT COUNT(*) FROM entregas WHERE estudiante_id = $userId AND estado = 'calificada'")->fetchColumn();
$promedioNotas = $db->query("
    SELECT AVG(c.nota) FROM calificaciones c 
    JOIN entregas e ON c.entrega_id = e.id 
    WHERE e.estudiante_id = $userId
")->fetchColumn();

// Últimas calificaciones obtenidas
$calificacionesRecientes = $db->query("
    SELECT c.*, a.titulo as actividad_titulo, a.puntaje_maximo, m.nombre as materia_nombre
    FROM calificaciones c
    JOIN entregas e ON c.entrega_id = e.id
    JOIN actividades a ON e.actividad_id = a.id
    JOIN materias m ON a.materia_id = m.id
    WHERE e.estudiante_id = $userId
    ORDER BY c.fecha_calificacion DESC LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Header con logo institucional -->
    <div class="dashboard-header student-welcome-header">
        <div class="row align-items-center">
            <div class="col-auto d-none d-md-block">
                <img src="<?php echo getLogoUrl(); ?>" alt="Logo institucional" class="institution-logo">
            </div>
            <div class="col">
                <h2>¡Hola, <?php echo sanitize($_SESSION['user_nombre']); ?>!</h2>
                <p>Bienvenido a tu panel de estudio. Explora tus materias y completa tus actividades pendientes.</p>
                <?php $gradoLabel = getGradoParaleloLabel($_SESSION['user_grado'] ?? null, $_SESSION['user_paralelo'] ?? null); ?>
                <?php if ($gradoLabel): ?>
                <span class="badge bg-light text-dark mt-2 px-3 py-2">
                    <i class="bi bi-mortarboard me-1"></i><?php echo sanitize($gradoLabel); ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="col-auto">
                <span class="badge bg-light text-dark fs-6 px-3 py-2">
                    <i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalMaterias; ?></div>
                        <div class="stat-label">Materias Inscritas</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-book"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalEntregadas; ?></div>
                        <div class="stat-label">Actividades Entregadas</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalEvaluadas; ?></div>
                        <div class="stat-label">Evaluadas</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-award"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $promedioNotas ? number_format($promedioNotas, 1) : '-'; ?></div>
                        <div class="stat-label">Mi Promedio General</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Actividades Pendientes -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-hourglass-split me-2"></i>Actividades Pendientes</span>
                    <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php" class="btn btn-sm btn-light">Ver todas</a>
                </div>
                <div class="card-body">
                    <?php if (empty($actividadesPendientes)): ?>
                    <div class="empty-state">
                        <i class="bi bi-check2-circle text-success" style="font-size: 3rem;"></i>
                        <h5>¡Al día!</h5>
                        <p class="small">No tienes actividades pendientes por el momento.</p>
                    </div>
                    <?php else: foreach ($actividadesPendientes as $a): ?>
                    <div class="activity-card <?php echo $a['tipo_metodologia']; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1"><?php echo sanitize($a['titulo']); ?></h6>
                                <span class="badge bg-primary small me-1"><?php echo sanitize($a['materia_nombre']); ?></span>
                                <span class="badge bg-light text-dark small"><i class="<?php echo getMetodologiaIcon($a['tipo_metodologia']); ?> me-1"></i><?php echo getMetodologiaName($a['tipo_metodologia']); ?></span>
                            </div>
                            <div class="text-end">
                                <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-primary">Realizar</a>
                                <?php if ($a['fecha_limite']): ?>
                                <div class="text-danger small mt-1 font-monospace" style="font-size:0.7rem;">
                                    Límite: <?php echo date('d/m', strtotime($a['fecha_limite'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Calificaciones Recientes -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-award me-2"></i>Calificaciones Recientes
                </div>
                <div class="card-body">
                    <?php if (empty($calificacionesRecientes)): ?>
                    <div class="empty-state">
                        <i class="bi bi-star"></i>
                        <h5>Sin calificaciones aún</h5>
                        <p class="small">Tus calificaciones aparecerán aquí cuando tus docentes te evalúen.</p>
                    </div>
                    <?php else: foreach ($calificacionesRecientes as $c): ?>
                    <div class="d-flex align-items-center justify-content-between p-3 mb-2 rounded" style="background: var(--gray-100);">
                        <div>
                            <h6 class="fw-bold mb-1"><?php echo sanitize($c['actividad_titulo']); ?></h6>
                            <span class="badge bg-success small"><?php echo sanitize($c['materia_nombre']); ?></span>
                            <?php if ($c['retroalimentacion']): ?>
                            <div class="text-muted small mt-1 italic">“<?php echo sanitize(substr($c['retroalimentacion'],0,60)); ?>...”</div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <span class="fs-5 fw-bold text-success"><?php echo number_format($c['nota'], 1); ?></span>
                            <span class="text-muted small">/<?php echo $c['puntaje_maximo']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de funciones estudiante -->
    <div class="mb-3 mt-2">
        <h5 class="section-title"><i class="bi bi-grid me-2"></i>Mi Aprendizaje</h5>
        <p class="section-subtitle">Explora tus herramientas para un aprendizaje autónomo y colaborativo.</p>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/materias.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-book"></i></div>
                    <h6>Acceder a Materias</h6>
                    <small>Tus asignaturas</small>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-journal-text"></i></div>
                    <h6>Realizar Actividades</h6>
                    <small>Tareas pendientes</small>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/calificaciones.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-award"></i></div>
                    <h6>Consultar Calificaciones</h6>
                    <small>Tus notas y promedios</small>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/retroalimentacion.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-chat-square-heart"></i></div>
                    <h6>Recibir Retroalimentación</h6>
                    <small>Comentarios del docente</small>
                </div>
            </a>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/recursos.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-collection-play"></i></div>
                    <h6>Explorar Recursos</h6>
                    <small>Materiales interactivos</small>
                </div>
            </a>
        </div>
    </div>

    <!-- Selector de materia actual -->
    <?php if (!empty($materias)): ?>
    <div class="card mb-4">
        <div class="card-body d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
            <div class="flex-grow-1">
                <div class="fw-semibold mb-2">Materia actual</div>
                <select id="currentMateriaSelect" class="form-select">
                    <?php foreach ($materias as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-lg" onclick="goToMateria()">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar a materia
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Acceso directo por materia -->
    <?php $todasMateriasEst = $db->query("SELECT * FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll(); ?>
    <?php if (!empty($todasMateriasEst)): ?>
    <div class="mb-3">
        <h5 class="section-title"><i class="bi bi-mortarboard me-2"></i>Mis Materias</h5>
        <p class="section-subtitle">Selecciona una materia para ingresar a tus actividades, recursos y evaluaciones.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($todasMateriasEst as $m): 
            $inscrito = in_array($m['id'], $materiasIds);
            $themeClass = getMateriaThemeClass($m['nombre']);
        ?>
        <div class="col-md-6">
            <a href="<?php echo BASE_URL; ?>/estudiante/materia.php?id=<?php echo $m['id']; ?>" class="text-decoration-none">
                <div class="materia-btn-card <?php echo $themeClass; ?>">
                    <img src="<?php echo getMateriaImageUrl($m['imagen']); ?>" alt="<?php echo sanitize($m['nombre']); ?>">
                    <div class="materia-btn-overlay">
                        <span class="materia-btn-badge">
                            <i class="<?php echo getMateriaIcon($m['nombre']); ?> me-1"></i>
                            <?php echo $inscrito ? 'Ingresar' : 'Ver materia'; ?>
                        </span>
                        <h4 class="materia-btn-title"><?php echo sanitize($m['nombre']); ?></h4>
                        <p class="materia-btn-desc"><?php echo sanitize($m['descripcion']); ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function goToMateria() {
    var materiaId = document.getElementById('currentMateriaSelect').value;
    if (materiaId) {
        window.location.href = '<?php echo BASE_URL; ?>/estudiante/materia.php?id=' + materiaId;
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
