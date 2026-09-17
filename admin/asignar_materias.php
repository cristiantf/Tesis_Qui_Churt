<?php
/**
 * Asignación de Materias - Panel Administrador
 */
$pageTitle = 'Asignación de Clases';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

$activeTab = $_GET['tab'] ?? 'docentes';
if (!in_array($activeTab, ['docentes', 'estudiantes'], true)) {
    $activeTab = 'docentes';
}

// Procesar desasignaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'desasignar_docente') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM materia_curso WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Asignación de docente eliminada correctamente.');
        }
    }
    
    if ($accion === 'asignar_docente') {
        $docente_id = intval($_POST['docente_id'] ?? 0);
        $materia_id = intval($_POST['materia_id'] ?? 0);
        $grado = intval($_POST['grado'] ?? 0);
        $paralelo = strtoupper(trim($_POST['paralelo'] ?? ''));
        
        if ($docente_id > 0 && $materia_id > 0 && in_array($grado, GRADOS) && in_array($paralelo, PARALELOS)) {
            $stmt = $db->prepare("INSERT IGNORE INTO materia_curso (materia_id, docente_id, grado, paralelo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$materia_id, $docente_id, $grado, $paralelo]);
            if ($stmt->rowCount() > 0) {
                setFlashMessage('success', 'Clase asignada correctamente al docente.');
            } else {
                setFlashMessage('warning', 'Esta asignación ya existe.');
            }
        } else {
            setFlashMessage('danger', 'Por favor selecciona todos los campos correctamente.');
        }
    }
    
    header('Location: ' . BASE_URL . '/admin/asignar_materias.php?tab=' . $activeTab);
    exit;
}

// Obtener asignaciones actuales de docentes
$asignacionesDocentes = $db->query("
    SELECT mc.*, u.nombre, u.apellido, u.email, m.nombre as materia_nombre 
    FROM materia_curso mc 
    JOIN usuarios u ON mc.docente_id = u.id 
    JOIN materias m ON mc.materia_id = m.id 
    ORDER BY m.nombre, mc.grado, mc.paralelo, u.nombre
")->fetchAll();

// Listas para el formulario de asignación
$docentesList = $db->query("SELECT id, nombre, apellido FROM usuarios WHERE rol = 'docente' AND estado = 1 ORDER BY nombre, apellido")->fetchAll();
$materiasList = $db->query("SELECT id, nombre FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll();

// Obtener lista de estudiantes inscritos automáticamente
$asignacionesEstudiantes = $db->query("
    SELECT u.nombre, u.apellido, u.email, u.grado, u.paralelo, m.nombre as materia_nombre, u.id as estudiante_id
    FROM usuarios u
    JOIN materia_curso mc ON u.grado = mc.grado AND u.paralelo COLLATE utf8mb4_unicode_ci = mc.paralelo COLLATE utf8mb4_unicode_ci
    JOIN materias m ON mc.materia_id = m.id
    WHERE u.rol = 'estudiante' AND u.estado = 1
    ORDER BY m.nombre, u.grado, u.paralelo, u.nombre
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-journal-bookmark me-2"></i>Asignación de Clases</h3>
        <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="btn btn-primary">
            <i class="bi bi-person-gear me-2"></i>Ir a Gestión de Usuarios
        </a>
    </div>
    
    <div class="alert alert-info shadow-sm mb-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Nueva Arquitectura:</strong> Ahora las materias se asocian a un Docente para un Grado y Paralelo específico. Los estudiantes ven sus materias de manera automática dependiendo del grado y paralelo que tengan en su perfil.
        Para asignar materias a un Docente o cambiar el grado de un Estudiante, utiliza la <a href="usuarios.php" class="alert-link">Gestión de Usuarios</a>.
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'docentes' ? 'active' : ''; ?>" data-bs-toggle="pill" href="#tabDocentes">
                <i class="bi bi-person-workspace me-1"></i>Clases por Docente
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'estudiantes' ? 'active' : ''; ?>" data-bs-toggle="pill" href="#tabEstudiantes">
                <i class="bi bi-mortarboard me-1"></i>Inscripciones Automáticas
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Docentes -->
        <div class="tab-pane fade <?php echo $activeTab === 'docentes' ? 'show active' : ''; ?>" id="tabDocentes">
            
            <!-- Formulario de Nueva Asignación -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Asignación de Clase
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3 align-items-end">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="accion" value="asignar_docente">
                        
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Docente</label>
                            <select name="docente_id" class="form-select" required>
                                <option value="">Seleccionar docente...</option>
                                <?php foreach ($docentesList as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo sanitize($d['nombre'] . ' ' . $d['apellido']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Materia</label>
                            <select name="materia_id" class="form-select" required>
                                <option value="">Seleccionar materia...</option>
                                <?php foreach ($materiasList as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Grado</label>
                            <select name="grado" class="form-select" required>
                                <option value="">Grado...</option>
                                <?php foreach (GRADOS as $g): ?>
                                <option value="<?php echo $g; ?>"><?php echo $g; ?>° grado</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Paralelo</label>
                            <select name="paralelo" class="form-select" required>
                                <option value="">Paralelo...</option>
                                <?php foreach (PARALELOS as $p): ?>
                                <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Asignar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="bi bi-list me-2"></i>Asignaciones de Docentes a Clases</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--gray-100);">
                                <tr>
                                    <th class="border-0 small">Docente</th>
                                    <th class="border-0 small">Materia</th>
                                    <th class="border-0 small">Curso</th>
                                    <th class="border-0 small text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($asignacionesDocentes)): ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">No hay asignaciones</td></tr>
                                <?php else: foreach ($asignacionesDocentes as $a): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo sanitize($a['nombre'] . ' ' . $a['apellido']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo sanitize($a['materia_nombre']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $a['grado']; ?>° <?php echo $a['paralelo']; ?></span></td>
                                    <td class="text-center">
                                        <form method="POST" style="display:inline;">
                                            <?php echo csrfInput(); ?>
                                            <input type="hidden" name="accion" value="desasignar_docente">
                                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar esta asignación?')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Estudiantes -->
        <div class="tab-pane fade <?php echo $activeTab === 'estudiantes' ? 'show active' : ''; ?>" id="tabEstudiantes">
            <div class="card">
                <div class="card-header"><i class="bi bi-list me-2"></i>Estudiantes Inscritos Automáticamente</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--gray-100);">
                                <tr>
                                    <th class="border-0 small">Estudiante</th>
                                    <th class="border-0 small">Curso Actual</th>
                                    <th class="border-0 small">Materia que recibe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($asignacionesEstudiantes)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No hay estudiantes recibiendo clases</td></tr>
                                <?php else: foreach ($asignacionesEstudiantes as $a): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo sanitize($a['nombre'] . ' ' . $a['apellido']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $a['grado']; ?>° <?php echo $a['paralelo']; ?></span></td>
                                    <td><span class="badge bg-success"><?php echo sanitize($a['materia_nombre']); ?></span></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
