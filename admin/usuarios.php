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

// Asegurar tabla para asignaciones de docente (grado + paralelo)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS docente_curso_paralelo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        docente_id INT NOT NULL,
        grado TINYINT UNSIGNED NOT NULL,
        paralelo ENUM('A','B','C','D') NOT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
} catch (PDOException $e) {
    // ignore
}

// Procesar acciones
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $id = intval($_GET['id'] ?? 0);
    
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $docenteCombos = array_filter(array_map('strval', (array)$raw));
        $materiasDocenteSeleccionadas = array_filter(array_map('intval', $_POST['materias_docente'] ?? []));
    }

    if (empty($nombre) || empty($apellido) || empty($email)) {
        setFlashMessage('danger', 'Nombre, apellido y email son obligatorios.');
    } elseif ($rol === 'estudiante' && (empty($grado) || empty($paralelo))) {
        setFlashMessage('danger', 'Selecciona grado y paralelo para estudiantes.');
    } elseif ($rol === 'estudiante' && (!in_array($grado, GRADOS, true) || !in_array($paralelo, PARALELOS, true))) {
        setFlashMessage('danger', 'Grado o paralelo no válidos.');
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
            // Guardar asignaciones de docente (si aplica)
            $db->prepare("DELETE FROM docente_curso_paralelo WHERE docente_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM materia_docente WHERE docente_id = ?")->execute([$id]);
            if ($rol === 'docente') {
                foreach ($docenteCombos as $combo) {
                    $parts = explode('|', $combo);
                    if (count($parts) === 2) {
                        $g = intval($parts[0]);
                        $p = strtoupper(trim($parts[1]));
                        if (in_array($g, GRADOS, true) && in_array($p, PARALELOS, true)) {
                            $stmt = $db->prepare("INSERT INTO docente_curso_paralelo (docente_id, grado, paralelo) VALUES (?, ?, ?)");
                            $stmt->execute([$id, $g, $p]);
                        }
                    }
                }
                foreach ($materiasDocenteSeleccionadas as $materiaId) {
                    $stmt = $db->prepare("INSERT IGNORE INTO materia_docente (materia_id, docente_id) VALUES (?, ?)");
                    $stmt->execute([$materiaId, $id]);
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
                    foreach ($docenteCombos as $combo) {
                        $parts = explode('|', $combo);
                        if (count($parts) === 2) {
                            $g = intval($parts[0]);
                            $p = strtoupper(trim($parts[1]));
                            if (in_array($g, GRADOS, true) && in_array($p, PARALELOS, true)) {
                                $stmt = $db->prepare("INSERT INTO docente_curso_paralelo (docente_id, grado, paralelo) VALUES (?, ?, ?)");
                                $stmt->execute([$newUserId, $g, $p]);
                            }
                        }
                    }
                    foreach ($materiasDocenteSeleccionadas as $materiaId) {
                        $stmt = $db->prepare("INSERT IGNORE INTO materia_docente (materia_id, docente_id) VALUES (?, ?)");
                        $stmt->execute([$materiaId, $newUserId]);
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

$materiasDocenteAsignadas = $db->query("SELECT docente_id, materia_id FROM materia_docente")->fetchAll();
$materiasDocenteByDocente = [];
foreach ($materiasDocenteAsignadas as $asignacion) {
    $materiasDocenteByDocente[$asignacion['docente_id']][] = intval($asignacion['materia_id']);
}

// Obtener asignaciones de docente (grado + paralelo)
$docenteAsignaciones = $db->query("SELECT docente_id, grado, paralelo FROM docente_curso_paralelo")->fetchAll();
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
                                    <a href="?accion=toggleEstado&id=<?php echo $u['id']; ?>" class="btn btn-outline-<?php echo $u['estado']?'warning':'success'; ?>" title="<?php echo $u['estado']?'Desactivar':'Activar'; ?>">
                                        <i class="bi bi-<?php echo $u['estado']?'pause':'play'; ?>"></i>
                                    </a>
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <button class="btn btn-outline-danger" onclick="confirmarEliminar('?accion=eliminar&id=<?php echo $u['id']; ?>', '<?php echo sanitize($u['nombre']); ?>')" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                            <label class="form-label fw-semibold small">Cursos y Paralelos (donde dará clases)</label>
                            <div id="docenteCombos" class="d-flex flex-wrap gap-2">
                                <?php foreach (GRADOS as $g): ?>
                                    <?php foreach (PARALELOS as $p): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="docente_combos[]" id="docente-<?php echo $g; ?>-<?php echo $p; ?>" value="<?php echo $g . '|' . $p; ?>">
                                        <label class="form-check-label" for="docente-<?php echo $g; ?>-<?php echo $p; ?>"><?php echo $g; ?>° - Paralelo <?php echo $p; ?></label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Marca los cursos/paralelos donde el docente podrá impartir clases.</small>
                        </div>
                        <div class="col-12 docente-fields" style="display:none;">
                            <label class="form-label fw-semibold small">Materias que dará</label>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="toggleDocenteMateriasPanel()">
                                <i class="bi bi-list-check me-1"></i>Seleccionar Materias
                            </button>
                            <div id="userMateriasDocente" class="d-flex flex-column gap-2">
                                <?php foreach ($materias as $m): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="materias_docente[]" id="materia-docente-<?php echo $m['id']; ?>" value="<?php echo $m['id']; ?>">
                                    <label class="form-check-label" for="materia-docente-<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Selecciona las materias que el docente podrá impartir.</small>
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
    var materiasDocenteContainer = document.getElementById('userMateriasDocente');
    if (materiasDocenteContainer) {
        Array.from(materiasDocenteContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) { opt.checked = false; });
    }
    var docenteContainer = document.getElementById('docenteCombos');
    if (docenteContainer) {
        Array.from(docenteContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) { opt.checked = false; });
    }
    toggleEstudianteFields();
}

function toggleDocenteMateriasPanel() {
    var panel = document.getElementById('userMateriasDocente');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
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
    // preseleccionar combos docente
    var selectedDocente = window.docenteAsignacionesById && window.docenteAsignacionesById[user.id] ? window.docenteAsignacionesById[user.id] : [];
    var docenteContainer = document.getElementById('docenteCombos');
    if (docenteContainer) {
        Array.from(docenteContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) {
            opt.checked = selectedDocente.includes(opt.value);
        });
    }
    var selectedMateriasDocente = window.materiasDocenteByDocente[user.id] || [];
    var materiasDocenteContainer = document.getElementById('userMateriasDocente');
    if (materiasDocenteContainer) {
        Array.from(materiasDocenteContainer.querySelectorAll('input[type="checkbox"]')).forEach(function(opt) {
            opt.checked = selectedMateriasDocente.includes(parseInt(opt.value, 10));
        });
    }
    new bootstrap.Modal(document.getElementById('modalUsuario')).show();
}
var materiasAsignadasByStudent = <?php echo json_encode($materiasAsignadasByStudent); ?>;
var docenteAsignacionesById = <?php echo json_encode($docenteCombosById); ?>;
var materiasDocenteByDocente = <?php echo json_encode($materiasDocenteByDocente); ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
