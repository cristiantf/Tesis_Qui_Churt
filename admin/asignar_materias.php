<?php
/**
 * Asignación de Materias - Panel Administrador
 */
$pageTitle = 'Asignación de Materias';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

$activeTab = $_GET['tab'] ?? 'docentes';
if (!in_array($activeTab, ['docentes', 'estudiantes'], true)) {
    $activeTab = 'docentes';
}
$selectedStudentId = intval($_GET['usuario_id'] ?? 0);
$selectedMateriaId = intval($_GET['materia_id'] ?? 0);

// Procesar asignaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $materia_id = intval($_POST['materia_id'] ?? 0);
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    
    if ($accion === 'asignar' && $materia_id > 0 && $usuario_id > 0) {
        $tabla = ($tipo === 'docente') ? 'materia_docente' : 'materia_estudiante';
        $campo = ($tipo === 'docente') ? 'docente_id' : 'estudiante_id';
        
        $stmt = $db->prepare("INSERT IGNORE INTO $tabla (materia_id, $campo) VALUES (?, ?)");
        $stmt->execute([$materia_id, $usuario_id]);
        setFlashMessage('success', 'Asignación realizada correctamente.');
    }
    
    if ($accion === 'desasignar' && $materia_id > 0 && $usuario_id > 0) {
        $tabla = ($tipo === 'docente') ? 'materia_docente' : 'materia_estudiante';
        $campo = ($tipo === 'docente') ? 'docente_id' : 'estudiante_id';
        
        $stmt = $db->prepare("DELETE FROM $tabla WHERE materia_id = ? AND $campo = ?");
        $stmt->execute([$materia_id, $usuario_id]);
        setFlashMessage('success', 'Asignación eliminada correctamente.');
    }
    
    header('Location: ' . BASE_URL . '/admin/asignar_materias.php');
    exit;
}

// Obtener datos
$materias = $db->query("SELECT * FROM materias ORDER BY nombre")->fetchAll();
$docentes = $db->query("SELECT * FROM usuarios WHERE rol = 'docente' AND estado = 1 ORDER BY nombre")->fetchAll();
$estudiantes = $db->query("SELECT * FROM usuarios WHERE rol = 'estudiante' AND estado = 1 ORDER BY nombre")->fetchAll();

// Obtener asignaciones actuales
$asignacionesDocentes = $db->query("
    SELECT md.*, u.nombre, u.apellido, u.email, m.nombre as materia_nombre 
    FROM materia_docente md 
    JOIN usuarios u ON md.docente_id = u.id 
    JOIN materias m ON md.materia_id = m.id 
    ORDER BY m.nombre, u.nombre
")->fetchAll();

$asignacionesEstudiantes = $db->query("
    SELECT me.*, u.nombre, u.apellido, u.email, m.nombre as materia_nombre 
    FROM materia_estudiante me 
    JOIN usuarios u ON me.estudiante_id = u.id 
    JOIN materias m ON me.materia_id = m.id 
    ORDER BY m.nombre, u.nombre
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-journal-bookmark me-2"></i>Asignación de Materias</h3>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'docentes' ? 'active' : ''; ?>" data-bs-toggle="pill" href="#tabDocentes">
                <i class="bi bi-person-workspace me-1"></i>Docentes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'estudiantes' ? 'active' : ''; ?>" data-bs-toggle="pill" href="#tabEstudiantes">
                <i class="bi bi-mortarboard me-1"></i>Estudiantes
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab Docentes -->
        <div class="tab-pane fade <?php echo $activeTab === 'docentes' ? 'show active' : ''; ?>" id="tabDocentes">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Asignar Docente a Materia</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="accion" value="asignar">
                                <input type="hidden" name="tipo" value="docente">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Materia</label>
                                    <select name="materia_id" class="form-select" required>
                                        <option value="">Seleccionar materia...</option>
                                        <?php foreach ($materias as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Docente</label>
                                    <select name="usuario_id" class="form-select" required>
                                        <option value="">Seleccionar docente...</option>
                                        <?php foreach ($docentes as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo sanitize($d['nombre'] . ' ' . $d['apellido']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Asignar</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-list me-2"></i>Asignaciones de Docentes</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: var(--gray-100);">
                                        <tr>
                                            <th class="border-0 small">Docente</th>
                                            <th class="border-0 small">Materia</th>
                                            <th class="border-0 small">Fecha</th>
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
                                            <td class="small text-muted"><?php echo date('d/m/Y', strtotime($a['fecha_asignacion'])); ?></td>
                                            <td class="text-center">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="desasignar">
                                                    <input type="hidden" name="tipo" value="docente">
                                                    <input type="hidden" name="materia_id" value="<?php echo $a['materia_id']; ?>">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $a['docente_id']; ?>">
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
            </div>
        </div>

        <!-- Tab Estudiantes -->
        <div class="tab-pane fade <?php echo $activeTab === 'estudiantes' ? 'show active' : ''; ?>" id="tabEstudiantes">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Inscribir Estudiante</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="accion" value="asignar">
                                <input type="hidden" name="tipo" value="estudiante">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Materia</label>
                                    <select name="materia_id" class="form-select" required>
                                        <option value="">Seleccionar materia...</option>
                                        <?php foreach ($materias as $m): ?>
                                        <option value="<?php echo $m['id']; ?>" <?php echo $m['id'] === $selectedMateriaId ? 'selected' : ''; ?>><?php echo sanitize($m['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Estudiante</label>
                                    <select name="usuario_id" class="form-select" required>
                                        <option value="">Seleccionar estudiante...</option>
                                        <?php foreach ($estudiantes as $e): ?>
                                        <option value="<?php echo $e['id']; ?>" <?php echo $e['id'] === $selectedStudentId ? 'selected' : ''; ?>><?php echo sanitize($e['nombre'] . ' ' . $e['apellido']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Inscribir</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-list me-2"></i>Inscripciones de Estudiantes</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: var(--gray-100);">
                                        <tr>
                                            <th class="border-0 small">Estudiante</th>
                                            <th class="border-0 small">Materia</th>
                                            <th class="border-0 small">Fecha</th>
                                            <th class="border-0 small text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($asignacionesEstudiantes)): ?>
                                        <tr><td colspan="4" class="text-center py-3 text-muted">No hay inscripciones</td></tr>
                                        <?php else: foreach ($asignacionesEstudiantes as $a): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo sanitize($a['nombre'] . ' ' . $a['apellido']); ?></td>
                                            <td><span class="badge bg-success"><?php echo sanitize($a['materia_nombre']); ?></span></td>
                                            <td class="small text-muted"><?php echo date('d/m/Y', strtotime($a['fecha_inscripcion'])); ?></td>
                                            <td class="text-center">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="desasignar">
                                                    <input type="hidden" name="tipo" value="estudiante">
                                                    <input type="hidden" name="materia_id" value="<?php echo $a['materia_id']; ?>">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $a['estudiante_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar esta inscripción?')">
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
