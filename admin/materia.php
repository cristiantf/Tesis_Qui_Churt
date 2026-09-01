<?php
/**
 * Panel de Materia - Administrador
 */
$pageTitle = 'Gestión de Materia';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();
$materiaId = intval($_GET['id'] ?? 0);
$materia = getMateriaById($materiaId);

if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$theme = getMateriaThemeClass($materia['nombre']);
$totalEstudiantes = $db->prepare("
    SELECT COUNT(DISTINCT u.id) 
    FROM usuarios u
    JOIN materia_curso mc ON u.grado = mc.grado AND u.paralelo COLLATE utf8mb4_unicode_ci = mc.paralelo COLLATE utf8mb4_unicode_ci
    WHERE mc.materia_id = ? AND u.rol = 'estudiante' AND u.estado = 1
");
$totalEstudiantes->execute([$materiaId]);
$totalEstudiantes = $totalEstudiantes->fetchColumn();

$totalDocentes = $db->prepare("SELECT COUNT(DISTINCT docente_id) FROM materia_curso WHERE materia_id = ?");
$totalDocentes->execute([$materiaId]);
$totalDocentes = $totalDocentes->fetchColumn();

$totalActividades = $db->prepare("SELECT COUNT(*) FROM actividades WHERE materia_id = ?");
$totalActividades->execute([$materiaId]);
$totalActividades = $totalActividades->fetchColumn();

$totalRecursos = $db->prepare("SELECT COUNT(*) FROM recursos WHERE materia_id = ?");
$totalRecursos->execute([$materiaId]);
$totalRecursos = $totalRecursos->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4 <?php echo $theme; ?>">
    <div class="materia-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="<?php echo getMateriaIcon($materia['nombre']); ?> me-2"></i><?php echo sanitize($materia['nombre']); ?></h2>
                <p><?php echo sanitize($materia['descripcion']); ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver al panel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card stat-success">
                <div class="stat-number"><?php echo $totalEstudiantes; ?></div>
                <div class="stat-label">Estudiantes inscritos</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-primary">
                <div class="stat-number"><?php echo $totalDocentes; ?></div>
                <div class="stat-label">Docentes asignados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-warning">
                <div class="stat-number"><?php echo $totalActividades; ?></div>
                <div class="stat-label">Actividades creadas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-info">
                <div class="stat-number"><?php echo $totalRecursos; ?></div>
                <div class="stat-label">Recursos disponibles</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="<?php echo BASE_URL; ?>/admin/asignar_materias.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-journal-bookmark"></i></div>
                    <h6>Asignación de Materias</h6>
                    <small>Gestionar docentes y estudiantes</small>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?php echo BASE_URL; ?>/admin/reportes.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-graph-up"></i></div>
                    <h6>Reportes de la Materia</h6>
                    <small>Rendimiento y estadísticas</small>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php?rol=estudiante" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-people"></i></div>
                    <h6>Gestión de Usuarios</h6>
                    <small>Control de accesos</small>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
