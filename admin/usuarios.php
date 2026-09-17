<?php
/**
 * Gestión de Usuarios - Panel Administrador
 */
$pageTitle = 'Gestión de Usuarios';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

// Asegurar columnas grado/paralelo para este formulario
try {
    $columns = $db->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('grado', $columns, true)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN grado TINYINT UNSIGNED DEFAULT NULL AFTER rol");
    }
    if (!in_array('paralelo', $columns, true)) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN paralelo ENUM('A','B','C','D') DEFAULT NULL AFTER grado");
    }
} catch (PDOException $e) {
    // Si no se puede modificar la tabla, seguimos con la lógica normal y dejamos que el error se muestre.
}

// Asegurar tabla para asignaciones (si no existiera, aunque fue en el script)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS materia_curso (
        id INT AUTO_INCREMENT PRIMARY KEY,
        materia_id INT NOT NULL,
        docente_id INT NOT NULL,
        grado TINYINT UNSIGNED NOT NULL,
        paralelo ENUM('A','B','C','D') NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
        FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY unique_materia_curso (materia_id, grado, paralelo)
    ) ENGINE=InnoDB;");
} catch (PDOException $e) {
    // ignore
}

// Procesar acciones que cambian estado (solo POST con CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    requireValidCsrfToken();
    $accion = $_POST['accion'];
    $id = intval($_POST['id'] ?? 0);
    
    if ($accion === 'eliminar' && $id > 0 && $id !== $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ? AND id != ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        setFlashMessage('success', 'Usuario eliminado correctamente.');
        header('Location: ' . BASE_URL . '/admin/usuarios.php');
        exit;
    }
    
    if ($accion === 'toggleEstado' && $id > 0) {
        $stmt = $db->prepare("UPDATE usuarios SET estado = NOT estado WHERE id = ? AND id != ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        setFlashMessage('success', 'Estado del usuario actualizado.');
        header('Location: ' . BASE_URL . '/admin/usuarios.php');
        exit;
    }
}

// Procesar formulario de crear/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['accion'])) {
    requireValidCsrfToken();
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? 'estudiante';
    $password = $_POST['password'] ?? '';
    $grado = null;
    $paralelo = null;
    $materiasSeleccionadas = [];
    $materiasDocenteSeleccionadas = [];
    $docenteCombos = [];

    if ($rol === 'estudiante') {
        $grado = intval($_POST['grado'] ?? 0);
        $paralelo = strtoupper(trim($_POST['paralelo'] ?? ''));
        $materiasSeleccionadas = array_filter(array_map('intval', $_POST['materias'] ?? []));
    }
    if ($rol === 'docente') {
        $raw = $_POST['docente_combos'] ?? [];
        foreach (array_unique(array_filter(array_map('strval', (array)$raw))) as $combo) {
            [$gradoCombo, $paraleloCombo] = array_pad(explode('|', $combo, 2), 2, '');
            $gradoCombo = intval($gradoCombo);
            $paraleloCombo = strtoupper(trim($paraleloCombo));
            if (in_array($gradoCombo, GRADOS, true) && in_array($paraleloCombo, PARALELOS, true)) {
                $docenteCombos[] = [$gradoCombo, $paraleloCombo];
            }
        }
    }

    if (empty($nombre) || empty($apellido) || empty($email)) {
        setFlashMessage('danger', 'Nombre, apellido y email son obligatorios.');
    } elseif ($rol === 'estudiante' && (empty($grado) || empty($paralelo))) {
        setFlashMessage('danger', 'Selecciona grado y paralelo para estudiantes.');
    } elseif ($rol === 'estudiante' && (!in_array($grado, GRADOS, true) || !in_array($paralelo, PARALELOS, true))) {
        setFlashMessage('danger', 'Grado o paralelo no válidos.');
    } elseif ($rol === 'docente' && empty($docenteCombos)) {
        setFlashMessage('danger', 'Asigna al menos un curso y paralelo al docente.');
    } else {
        if ($id > 0) {
            // Editar
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET nombre=?, apellido=?, email=?, rol=?, grado=?, paralelo=?, password=? WHERE id=?");
                $stmt->execute([$nombre, $apellido, $email, $rol, $grado, $paralelo, $hash, $id]);
            } else {
                $stmt = $db->prepare("UPDATE usuarios SET nombre=?, apellido=?, email=?, rol=?, grado=?, paralelo=? WHERE id=?");
                $stmt->execute([$nombre, $apellido, $email, $rol, $grado, $paralelo, $id]);
            }
            $db->prepare("DELETE FROM docente_curso_paralelo WHERE docente_id = ?")->execute([$id]);
            if ($rol === 'docente') {
                $asignarCurso = $db->prepare("INSERT INTO docente_curso_paralelo (docente_id, grado, paralelo) VALUES (?, ?, ?)");
                foreach ($docenteCombos as [$gradoDocente, $paraleloDocente]) {
                    $asignarCurso->execute([$id, $gradoDocente, $paraleloDocente]);
                }
            }
            setFlashMessage('success', 'Usuario actualizado correctamente.');
        } else {
            // Crear
            if (empty($password)) {
                setFlashMessage('danger', 'La contraseña es obligatoria para nuevos usuarios.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol, grado, paralelo) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$nombre, $apellido, $email, $hash, $rol, $grado, $paralelo]);
                $newUserId = $db->lastInsertId();
                if ($rol === 'estudiante') {
                    foreach ($materiasSeleccionadas as $materiaId) {
                        $stmt = $db->prepare("INSERT IGNORE INTO materia_estudiante (materia_id, estudiante_id) VALUES (?, ?)");
                        $stmt->execute([$materiaId, $newUserId]);
                    }
                }
                if ($rol === 'docente') {
                    $asignarCurso = $db->prepare("INSERT INTO docente_curso_paralelo (docente_id, grado, paralelo) VALUES (?, ?, ?)");
                    foreach ($docenteCombos as [$gradoDocente, $paraleloDocente]) {
                        $asignarCurso->execute([$newUserId, $gradoDocente, $paraleloDocente]);
                    }
                }
                setFlashMessage('success', 'Usuario creado correctamente.');
            }
        }
        header('Location: ' . BASE_URL . '/admin/usuarios.php');
        exit;
    }
}

// Filtros
$filtroRol = $_GET['rol'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($filtroRol) {
    $sql .= " AND rol = ?";
    $params[] = $filtroRol;
}
if ($buscar) {
    $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}
$sql .= " ORDER BY fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
$materias = $db->query("SELECT * FROM materias WHERE estado = 1 ORDER BY nombre")->fetchAll();
$materiasAsignadas = $db->query("SELECT estudiante_id, materia_id FROM materia_estudiante")->fetchAll();
$materiasAsignadasByStudent = [];
foreach ($materiasAsignadas as $asignacion) {
    $materiasAsignadasByStudent[$asignacion['estudiante_id']][] = intval($asignacion['materia_id']);
}

$materiasDocenteAsignadas = $db->query("SELECT DISTINCT docente_id, materia_id FROM materia_curso")->fetchAll();
$materiasDocenteByDocente = [];
foreach ($materiasDocenteAsignadas as $asignacion) {
    $materiasDocenteByDocente[$asignacion['docente_id']][] = intval($asignacion['materia_id']);
}

// Obtener asignaciones de docente (grado + paralelo)
$docenteAsignaciones = $db->query("SELECT docente_id, grado, paralelo FROM docente_curso_paralelo ORDER BY grado, paralelo")->fetchAll();
// construir combos 'grado|paralelo' por docente para el JS
$docenteCombosById = [];
foreach ($docenteAsignaciones as $d) {
    $docenteCombosById[$d['docente_id']][] = intval($d['grado']) . '|' . $d['paralelo'];
}
$docenteComboLabelsById = [];
foreach ($docenteCombosById as $docenteId => $combos) {
    $docenteComboLabelsById[$docenteId] = implode(', ', array_map(function ($combo) {
        [$grado, $paralelo] = explode('|', $combo);
        return intval($grado) . '°' . $paralelo;
    }, array_unique($combos)));
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h3>
            <p class="text-muted mb-0"><?php echo count($usuarios); ?> usuarios registrados</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario" onclick="limpiarFormulario()">
            <i class="bi bi-person-plus me-2"></i>Nuevo Usuario
        </button>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o email..." 
                           value="<?php echo sanitize($buscar); ?>">
                </div>
                <div class="col-md-3">
                    <select name="rol" class="form-select">
                        <option value="">Todos los roles</option>
                        <option value="administrador" <?php echo $filtroRol==='administrador'?'selected':''; ?>>Administrador</option>
                        <option value="docente" <?php echo $filtroRol==='docente'?'selected':''; ?>>Docente</option>
                        <option value="estudiante" <?php echo $filtroRol==='estudiante'?'selected':''; ?>>Estudiante</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo BASE_URL; ?>/admin/usuarios.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: var(--gradient-primary); color: white;">
                        <tr>
                            <th class="border-0">Usuario</th>
                            <th class="border-0">Email</th>
                            <th class="border-0">Rol</th>
                            <th class="border-0">Asignaciones</th>
                            <th class="border-0">Grado</th>
                            <th class="border-0">Paralelo</th>
                            <th class="border-0">Contraseña</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0">Registro</th>
                            <th class="border-0 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted">No se encontraron usuarios.</td></tr>
                        <?php else: foreach ($usuarios as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2" style="width:35px;height:35px;font-size:0.7rem;">
                                        <?php echo strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1)); ?>
                                    </div>
                                    <strong><?php echo sanitize($u['nombre'] . ' ' . $u['apellido']); ?></strong>
                                </div>
                            </td>
                            <td class="text-muted small"><?php echo sanitize($u['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $u['rol']==='administrador'?'danger':($u['rol']==='docente'?'primary':'success'); ?>">
                                    <?php echo ucfirst($u['rol']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['rol'] === 'docente'): ?>
                                    <?php echo isset($materiasDocenteByDocente[$u['id']]) ? count($materiasDocenteByDocente[$u['id']]) . ' materias' : '0 materias'; ?><br>
                                    <small><?php echo isset($docenteComboLabelsById[$u['id']]) ? sanitize($docenteComboLabelsById[$u['id']]) : '-'; ?></small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $u['rol'] === 'estudiante' ? (intval($u['grado']) . '°') : '-'; ?></td>
                            <td><?php echo $u['rol'] === 'estudiante' ? strtoupper($u['paralelo']) : '-'; ?></td>
                            <td class="small text-muted">••••••</td>
                            <td>
                                <span class="badge bg-<?php echo $u['estado']?'success':'secondary'; ?>">
                                    <?php echo $u['estado']?'Activo':'Inactivo'; ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('d/m/Y', strtotime($u['fecha_creacion'])); ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u)); ?>)" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="accion" value="toggleEstado">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-outline-<?php echo $u['estado']?'warning':'success'; ?>" title="<?php echo $u['estado']?'Desactivar':'Activar'; ?>">
                                        <i class="bi bi-<?php echo $u['estado']?'pause':'play'; ?>"></i>
                                        </button>
                                    </form>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                                        <?php echo csrfInput(); ?>
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="id" id="userId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Nombre</label>
                            <input type="text" name="nombre" id="userNombre" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Apellido</label>
                            <input type="text" name="apellido" id="userApellido" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" name="email" id="userEmail" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Rol</label>
                            <select name="rol" id="userRol" class="form-select" onchange="toggleEstudianteFields()">
                                <option value="estudiante">Estudiante</option>
                                <option value="docente">Docente</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                        <div class="col-6 estudiante-fields">
                            <label class="form-label fw-semibold small">Grado</label>
                            <select name="grado" id="userGrado" class="form-select">
                                <option value="">Selecciona grado</option>
                                <?php foreach (GRADOS as $g): ?>
                                <option value="<?php echo $g; ?>"><?php echo $g; ?>° grado</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 estudiante-fields">
                            <label class="form-label fw-semibold small">Paralelo</label>
                            <select name="paralelo" id="userParalelo" class="form-select">
                                <option value="">Selecciona paralelo</option>
                                <?php foreach (PARALELOS as $p): ?>
                                <option value="<?php echo $p; ?>">Paralelo <?php echo $p; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 estudiante-fields">
                            <label class="form-label fw-semibold small">Materias</label>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="toggleMateriasPanel()">
                                <i class="bi bi-list-check me-1"></i>Seleccionar Materias
                            </button>
                            <div id="userMaterias" class="d-flex flex-column gap-2">
                                <?php foreach ($materias as $m): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="materias[]" id="materia-<?php echo $m['id']; ?>" value="<?php echo $m['id']; ?>">
                                    <label class="form-check-label" for="materia-<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Selecciona una o ambas materias para este estudiante.</small>
                        </div>
                        <div class="col-12 docente-fields" style="display:none;">
                            <label class="form-label fw-semibold small">Cursos y paralelos asignados</label>
                            <div class="input-group mb-2">
                                <select id="docenteGrado" class="form-select"><option value="">Curso...</option><?php foreach (GRADOS as $g): ?><option value="<?php echo $g; ?>"><?php echo $g; ?>° grado</option><?php endforeach; ?></select>
                                <select id="docenteParalelo" class="form-select"><option value="">Paralelo...</option><?php foreach (PARALELOS as $p): ?><option value="<?php echo $p; ?>"><?php echo $p; ?></option><?php endforeach; ?></select>
                                <button type="button" class="btn btn-outline-primary" onclick="agregarCursoDocente()"><i class="bi bi-plus-lg"></i> Agregar</button>
                            </div>
                            <div id="docenteCursosAsignados" class="d-flex flex-wrap gap-2"></div>
                            <small class="text-muted d-block mt-2">Estos cursos habilitan la creación de tareas. Las materias se asignan desde <a href="asignar_materias.php">Asignación de Clases</a>.</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Contraseña</label>
                            <input type="password" name="password" id="userPassword" class="form-control" placeholder="Dejar vacío para no cambiar">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function limpiarFormulario() {
    document.getElementById('userId').value = '0';
    document.getElementById('userNombre').value = '';
    document.getElementById('userApellido').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userRol').value = 'estudiante';
    document.getElementById('userGrado').value = '';
    document.getElementById('userParalelo').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Nuevo Usuario';
    var materiasContainer = document.getElementById('userMaterias');
    if (materiasContainer) {
        Array.from(materiasContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) { opt.checked = false; });
    }
    renderCursosDocente([]);
    toggleEstudianteFields();
}

function toggleMateriasPanel() {
    var panel = document.getElementById('userMaterias');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function toggleEstudianteFields() {
    var role = document.getElementById('userRol').value;
    var show = role === 'estudiante';
    document.querySelectorAll('.estudiante-fields').forEach(function(el) {
        el.style.display = show ? 'block' : 'none';
    });
    document.getElementById('userGrado').required = show;
    document.getElementById('userParalelo').required = show;

    // mostrar campos para docente
    var showDocente = role === 'docente';
    document.querySelectorAll('.docente-fields').forEach(function(el) {
        el.style.display = showDocente ? 'block' : 'none';
    });
}

function renderCursosDocente(combos) {
    var container = document.getElementById('docenteCursosAsignados');
    if (!container) return;
    container.innerHTML = '';
    combos.forEach(function(combo) {
        var parts = combo.split('|');
        if (parts.length !== 2) return;
        var badge = document.createElement('span');
        badge.className = 'badge bg-primary d-inline-flex align-items-center gap-1 p-2';
        badge.innerHTML = parts[0] + '° ' + parts[1] + '<input type="hidden" name="docente_combos[]" value="' + combo + '"><button type="button" class="btn-close btn-close-white ms-1" aria-label="Quitar"></button>';
        badge.querySelector('button').addEventListener('click', function() { badge.remove(); });
        container.appendChild(badge);
    });
}

function agregarCursoDocente() {
    var grado = document.getElementById('docenteGrado').value;
    var paralelo = document.getElementById('docenteParalelo').value;
    if (!grado || !paralelo) { alert('Selecciona un curso y un paralelo.'); return; }
    var combo = grado + '|' + paralelo;
    var actuales = Array.from(document.querySelectorAll('#docenteCursosAsignados input')).map(function(input) { return input.value; });
    if (actuales.includes(combo)) { alert('Este curso y paralelo ya está asignado.'); return; }
    actuales.push(combo);
    renderCursosDocente(actuales);
    document.getElementById('docenteGrado').value = '';
    document.getElementById('docenteParalelo').value = '';
}

function editarUsuario(user) {
    document.getElementById('userId').value = user.id;
    document.getElementById('userNombre').value = user.nombre;
    document.getElementById('userApellido').value = user.apellido;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRol').value = user.rol;
    document.getElementById('userGrado').value = user.grado || '';
    document.getElementById('userParalelo').value = user.paralelo || '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Usuario';
    toggleEstudianteFields();
    var selectedMaterias = window.materiasAsignadasByStudent[user.id] || [];
    var materiasContainer = document.getElementById('userMaterias');
    if (materiasContainer) {
        Array.from(materiasContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) {
            opt.checked = selectedMaterias.includes(parseInt(opt.value, 10));
        });
    }
    renderCursosDocente(window.docenteAsignacionesById[user.id] || []);
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}
var materiasAsignadasByStudent = <?php echo json_encode($materiasAsignadasByStudent); ?>;
var docenteAsignacionesById = <?php echo json_encode($docenteCombosById); ?>;
var materiasDocenteByDocente = <?php echo json_encode($materiasDocenteByDocente); ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
