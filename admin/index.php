<?php
/**
 * Dashboard del Administrador
 */
$pageTitle = 'Dashboard Administrador';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

// Estadísticas
$totalUsuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalDocentes = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'docente'")->fetchColumn();
$totalEstudiantes = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'")->fetchColumn();
$totalMaterias = $db->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$totalActividades = $db->query("SELECT COUNT(*) FROM actividades")->fetchColumn();
$totalRecursos = $db->query("SELECT COUNT(*) FROM recursos")->fetchColumn();

// Usuarios recientes
$recientes = $db->query("SELECT * FROM usuarios ORDER BY fecha_creacion DESC LIMIT 5")->fetchAll();

// Actividades por materia
$actPorMateria = $db->query("
    SELECT m.nombre, COUNT(a.id) as total 
    FROM materias m 
    LEFT JOIN actividades a ON m.id = a.materia_id 
    GROUP BY m.id, m.nombre
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col">
                <h2><i class="bi bi-shield-check me-2"></i>Panel de Administrador</h2>
                <p>Bienvenido, <?php echo sanitize(getFullName()); ?>. Gestiona la plataforma educativa desde aquí.</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-light text-dark fs-6 px-3 py-2">
                    <i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalUsuarios; ?></div>
                        <div class="stat-label">Usuarios Total</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-secondary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalDocentes; ?></div>
                        <div class="stat-label">Docentes</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalEstudiantes; ?></div>
                        <div class="stat-label">Estudiantes</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-mortarboard-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalMaterias; ?></div>
                        <div class="stat-label">Materias</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalActividades; ?></div>
                        <div class="stat-label">Actividades</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card stat-danger">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number"><?php echo $totalRecursos; ?></div>
                        <div class="stat-label">Recursos</div>
                    </div>
                    <div class="stat-icon"><i class="bi bi-folder-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Gráfico de actividades por materia -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-bar-chart me-2"></i>Actividades por Materia
                </div>
                <div class="card-body">
                    <canvas id="chartMaterias" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Usuarios Recientes -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clock-history me-2"></i>Usuarios Recientes</span>
                    <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="btn btn-sm btn-light">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--gray-100);">
                                <tr>
                                    <th class="border-0 small text-muted">Usuario</th>
                                    <th class="border-0 small text-muted">Rol</th>
                                    <th class="border-0 small text-muted">Fecha</th>
                                    <th class="border-0 small text-muted">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recientes as $u): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width:30px;height:30px;font-size:0.65rem;">
                                                <?php echo strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold small"><?php echo sanitize($u['nombre'] . ' ' . $u['apellido']); ?></div>
                                                <div class="text-muted" style="font-size:0.75rem;"><?php echo sanitize($u['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-<?php echo $u['rol']==='administrador'?'danger':($u['rol']==='docente'?'primary':'success'); ?>"><?php echo ucfirst($u['rol']); ?></span></td>
                                    <td class="small text-muted"><?php echo date('d/m/Y', strtotime($u['fecha_creacion'])); ?></td>
                                    <td><span class="badge bg-<?php echo $u['estado']?'success':'secondary'; ?>"><?php echo $u['estado']?'Activo':'Inactivo'; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="mb-3">
        <h5 class="section-title"><i class="bi bi-grid me-2"></i>Funciones de Administración</h5>
        <p class="section-subtitle">Controla el acceso y supervisa el rendimiento global de la plataforma.</p>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-people-fill"></i></div>
                    <h6>Gestión de Usuarios</h6>
                    <small>Crear, editar y controlar accesos</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/admin/asignar_materias.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-journal-bookmark"></i></div>
                    <h6>Asignación de Materias</h6>
                    <small>Docentes y estudiantes por asignatura</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/admin/reportes.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-graph-up"></i></div>
                    <h6>Reportes Generales</h6>
                    <small>Estadísticas y métricas globales</small>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="<?php echo BASE_URL; ?>/admin/configuracion.php" class="text-decoration-none">
                <div class="action-btn-card">
                    <div class="action-icon"><i class="bi bi-gear"></i></div>
                    <h6>Configuración de la Plataforma</h6>
                    <small>Ajustes generales del sistema</small>
                </div>
            </a>
        </div>
    </div>

    <!-- Acceso directo por materia -->
    <?php $materiasAdmin = $db->query("SELECT * FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll(); ?>
    <div class="mb-3">
        <h5 class="section-title"><i class="bi bi-book me-2"></i>Acceso por Materia</h5>
        <p class="section-subtitle">Supervisa el rendimiento de cada asignatura de forma diferenciada.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($materiasAdmin as $m): ?>
        <div class="col-md-6">
            <a href="<?php echo BASE_URL; ?>/admin/materia.php?id=<?php echo $m['id']; ?>" class="text-decoration-none">
                <div class="subject-access-card">
                    <img src="<?php echo getMateriaImageUrl($m['imagen']); ?>" alt="<?php echo sanitize($m['nombre']); ?>">
                    <div class="subject-overlay">
                        <h5 class="fw-bold mb-1"><i class="<?php echo getMateriaIcon($m['nombre']); ?> me-2"></i><?php echo sanitize($m['nombre']); ?></h5>
                        <small>Ver estadísticas y gestión de la materia</small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Chart de actividades por materia
const ctx = document.getElementById('chartMaterias').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['nombre'] . "'"; }, $actPorMateria)); ?>],
        datasets: [{
            data: [<?php echo implode(',', array_column($actPorMateria, 'total')); ?>],
            backgroundColor: ['#1a4b8c', '#e8833a', '#27ae60', '#3498db'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 20, font: { family: 'Inter' } } }
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
