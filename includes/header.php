<?php
/**
 * Header compartido - Navbar y Head
 */
$user = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plataforma web educativa basada en metodologías activas para Ciencias Naturales y Estudios Sociales">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Plataforma Educativa 10 de Agosto</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Navbar para usuarios autenticados -->
<nav class="navbar navbar-expand-lg navbar-dark main-navbar sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo getDashboardUrl($user['rol']); ?>">
            <img src="<?php echo getLogoUrl(); ?>" alt="Logo" height="40" class="me-2">
            <span class="brand-text">Plataforma Educativa</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user['rol'] === 'administrador'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'usuarios.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/usuarios.php">
                            <i class="bi bi-people me-1"></i>Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'asignar_materias.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/asignar_materias.php">
                            <i class="bi bi-journal-bookmark me-1"></i>Materias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'reportes.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reportes.php">
                            <i class="bi bi-graph-up me-1"></i>Reportes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'configuracion.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/configuracion.php">
                            <i class="bi bi-gear me-1"></i>Configuración
                        </a>
                    </li>
                <?php elseif ($user['rol'] === 'docente'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' && strpos($_SERVER['PHP_SELF'], 'docente') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'actividades.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/actividades.php">
                            <i class="bi bi-journal-text me-1"></i>Actividades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'recursos.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/recursos.php">
                            <i class="bi bi-folder me-1"></i>Recursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'calificaciones.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/calificaciones.php">
                            <i class="bi bi-clipboard-check me-1"></i>Calificaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'foro.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/foro.php">
                            <i class="bi bi-chat-dots me-1"></i>Foro
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'progreso.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/docente/progreso.php">
                            <i class="bi bi-bar-chart me-1"></i>Progreso
                        </a>
                    </li>
                <?php elseif ($user['rol'] === 'estudiante'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'index.php' && strpos($_SERVER['PHP_SELF'], 'estudiante') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/index.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'materias.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/materias.php">
                            <i class="bi bi-book me-1"></i>Materias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'actividades.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/actividades.php">
                            <i class="bi bi-journal-text me-1"></i>Actividades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'calificaciones.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/calificaciones.php">
                            <i class="bi bi-award me-1"></i>Calificaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'retroalimentacion.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/retroalimentacion.php">
                            <i class="bi bi-chat-square-heart me-1"></i>Retroalimentación
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'recursos.php' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/estudiante/recursos.php">
                            <i class="bi bi-collection me-1"></i>Recursos
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Usuario dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <div class="avatar-circle me-2"><?php echo getInitials(); ?></div>
                    <span class="d-none d-md-inline"><?php echo sanitize($user['nombre']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><span class="dropdown-item-text text-muted small"><?php echo ucfirst($user['rol']); ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Flash Messages -->
<?php $flash = getFlashMessage(); if ($flash): ?>
<div class="container-fluid px-4 mt-3">
    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo $flash['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
