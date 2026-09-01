<?php
/**
 * Mis Materias - Panel Estudiante
 */
$pageTitle = 'Mis Materias';
require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];

// Obtener las materias inscritas por el estudiante
$gradoEstudiante = intval($_SESSION['user_grado'] ?? 0);
$paraleloEstudiante = trim($_SESSION['user_paralelo'] ?? '');

$materias = [];
if ($gradoEstudiante > 0 && $paraleloEstudiante !== '') {
    $stmt = $db->prepare("
        SELECT m.* 
        FROM materias m 
        JOIN materia_curso mc ON m.id = mc.materia_id 
        WHERE mc.grado = ? AND mc.paralelo = ? AND m.estado = 1
    ");
    $stmt->execute([$gradoEstudiante, $paraleloEstudiante]);
    $materias = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-book me-2"></i>Mis Materias</h3>

    <?php if (empty($materias)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <i class="bi bi-journal-x"></i>
            <h5>Aún no estás inscrito en ninguna materia</h5>
            <p>Ponte en contacto con el administrador para que te asigne tus materias correspondientes.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="row g-4 justify-content-center">
        <?php foreach ($materias as $m): 
            $themeClass = getMateriaThemeClass($m['nombre']);
        ?>
        <div class="col-md-6 col-lg-5">
            <a href="<?php echo BASE_URL; ?>/estudiante/materia.php?id=<?php echo $m['id']; ?>" class="text-decoration-none">
                <div class="materia-btn-card <?php echo $themeClass; ?>">
                    <img src="<?php echo getMateriaImageUrl($m['imagen']); ?>" alt="<?php echo sanitize($m['nombre']); ?>">
                    <div class="materia-btn-overlay">
                        <span class="materia-btn-badge">
                            <i class="<?php echo getMateriaIcon($m['nombre']); ?> me-1"></i>Ingresar
                        </span>
                        <h4 class="materia-btn-title"><?php echo sanitize($m['nombre']); ?></h4>
                        <p class="materia-btn-desc"><?php echo sanitize($m['descripcion']); ?></p>
                    </div>
                </div>
            </a>
            <div class="d-flex gap-2 mt-3 justify-content-center">
                <a href="<?php echo BASE_URL; ?>/estudiante/actividades.php?materia_id=<?php echo $m['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-journal-text me-1"></i>Actividades
                </a>
                <a href="<?php echo BASE_URL; ?>/estudiante/recursos.php?materia_id=<?php echo $m['id']; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-collection me-1"></i>Recursos
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
