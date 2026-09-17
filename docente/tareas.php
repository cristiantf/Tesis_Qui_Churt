<?php
/**
 * Gestión de Tareas - Panel Docente
 * CRUD completo: Crear, Editar, Eliminar, Guardar tareas por curso y paralelo
 */
$pageTitle = 'Gestión de Tareas';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Materias del docente
$materias = $db->query("SELECT m.* FROM materias m JOIN materia_docente md ON m.id = md.materia_id WHERE md.docente_id = $userId")->fetchAll();

// Cursos y paralelos asignados al docente
$cursoParalelosAsignados = $db->prepare("SELECT grado, paralelo FROM docente_curso_paralelo WHERE docente_id = ? ORDER BY grado, paralelo");
$cursoParalelosAsignados->execute([$userId]);
$cursoParalelos = $cursoParalelosAsignados->fetchAll(PDO::FETCH_ASSOC);
$cursoParaleloOptions = [];
foreach ($cursoParalelos as $item) {
    $cursoParaleloOptions[] = [
        'value' => $item['grado'] . '|' . $item['paralelo'],
        'label' => $item['grado'] . '° - Paralelo ' . $item['paralelo'],
    ];
}

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    requireValidCsrfToken();
    $id = intval($_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM tareas WHERE id = ? AND docente_id = ?");
    $stmt->execute([$id, $userId]);
    setFlashMessage('success', 'Tarea eliminada correctamente.');
    header('Location: ' . BASE_URL . '/docente/tareas.php');
    exit;
}

// Procesar formulario (Crear o Editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    if (($_POST['accion'] ?? '') === 'eliminar') {
        exit;
    }
    $id          = intval($_POST['id'] ?? 0);
    $materia_id  = intval($_POST['materia_id']);
    $titulo      = trim($_POST['titulo']);
    $descripcion = sanitizeRichText($_POST['descripcion'] ?? '');
    $cursoParalelo = trim($_POST['curso_paralelo'] ?? '');
    $tipo        = $_POST['tipo'] ?? 'tarea';
    $fecha_entrega = $_POST['fecha_entrega'] ?: null;
    $puntaje     = floatval($_POST['puntaje'] ?? 10);
    $estado      = $_POST['estado'] ?? 'pendiente';

    $curso = '';
    $paralelo = '';
    if ($cursoParalelo) {
        $parts = explode('|', $cursoParalelo);
        if (count($parts) === 2) {
            $curso = trim($parts[0]);
            $paralelo = trim($parts[1]);
        }
    }

    $materiaIdsAsignadas = array_column($materias, 'id');
    $comboValores = array_column($cursoParaleloOptions, 'value');

    if (!in_array($materia_id, $materiaIdsAsignadas, true)) {
        setFlashMessage('danger', 'La materia seleccionada no está asignada a este docente.');
        header('Location: ' . BASE_URL . '/docente/tareas.php');
        exit;
    }
    if (empty($cursoParalelo) || !in_array($cursoParalelo, $comboValores, true)) {
        setFlashMessage('danger', 'Selecciona un curso y paralelo válidos asignados a ti.');
        header('Location: ' . BASE_URL . '/docente/tareas.php');
        exit;
    }

    if ($id > 0) {
        // Editar
        $stmt = $db->prepare("UPDATE tareas SET materia_id=?, titulo=?, descripcion=?, paralelo=?, curso=?, tipo=?, fecha_entrega=?, puntaje=?, estado=? WHERE id=? AND docente_id=?");
        $stmt->execute([$materia_id, $titulo, $descripcion, $paralelo, $curso, $tipo, $fecha_entrega, $puntaje, $estado, $id, $userId]);
        setFlashMessage('success', 'Tarea actualizada y guardada correctamente.');
    } else {
        // Crear
        $stmt = $db->prepare("INSERT INTO tareas (materia_id, docente_id, titulo, descripcion, paralelo, curso, tipo, fecha_entrega, puntaje, estado, fecha_creacion) VALUES (?,?,?,?,?,?,?,?,?,?, NOW())");
        $stmt->execute([$materia_id, $userId, $titulo, $descripcion, $paralelo, $curso, $tipo, $fecha_entrega, $puntaje, $estado]);
        setFlashMessage('success', 'Tarea creada correctamente.');
    }
    header('Location: ' . BASE_URL . '/docente/tareas.php');
    exit;
}

// Filtros
$filtroMateria = $_GET['materia'] ?? '';
$filtroCursoParalelo = $_GET['curso_paralelo'] ?? '';
$filtroEstado  = $_GET['estado'] ?? '';

// Verificar / crear tabla tareas si no exista
$db->exec("CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    docente_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    paralelo VARCHAR(10) DEFAULT '',
    curso VARCHAR(50) DEFAULT '',
    tipo ENUM('tarea','quiz','proyecto','examen') DEFAULT 'tarea',
    fecha_entrega DATE,
    puntaje DECIMAL(5,2) DEFAULT 10,
    estado ENUM('pendiente','activa','cerrada') DEFAULT 'pendiente',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$sql = "SELECT t.*, m.nombre as materia_nombre FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.docente_id = ?";
$params = [$userId];
if ($filtroMateria)  { $sql .= " AND t.materia_id = ?"; $params[] = $filtroMateria; }
if ($filtroCursoParalelo) {
    $parts = explode('|', $filtroCursoParalelo);
    if (count($parts) === 2) {
        $sql .= " AND t.curso = ? AND t.paralelo = ?";
        $params[] = trim($parts[0]);
        $params[] = trim($parts[1]);
    }
}
if ($filtroEstado)   { $sql .= " AND t.estado = ?";     $params[] = $filtroEstado; }
$sql .= " ORDER BY t.fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll();

// Obtener opciones de filtro curso/paralelo
$cursoParaleloFiltroOptions = $cursoParaleloOptions;

function getTipoBadge($tipo) {
    return match($tipo) {
        'tarea'    => 'primary',
        'quiz'     => 'purple',
        'proyecto' => 'success',
        'examen'   => 'danger',
        default    => 'secondary'
    };
}

function getEstadoTareaBadge($estado) {
    return match($estado) {
        'pendiente' => 'warning',
        'activa'    => 'success',
        'cerrada'   => 'secondary',
        default     => 'light'
    };
}

include __DIR__ . '/../includes/header.php';
?>

<style>
.tarea-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 0;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    cursor: pointer;
}
.tarea-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.13);
    transform: translateY(-2px);
}
.tarea-card.tarea   { border-left: 4px solid #4e73df; }
.tarea-card.quiz    { border-left: 4px solid #9b59b6; }
.tarea-card.proyecto{ border-left: 4px solid #1cc88a; }
.tarea-card.examen  { border-left: 4px solid #e74a3b; }

.btn-purple { background: #9b59b6; color: #fff; border: none; }
.btn-purple:hover { background: #8e44ad; color: #fff; }
.badge-purple { background-color: #9b59b6; color: #fff; }

.action-buttons-row .btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 10px 22px;
    transition: all 0.25s;
}
.action-buttons-row .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.18); }

.filter-card { background: var(--gray-100, #f8f9fa); border-radius: 14px; padding: 16px; margin-bottom: 24px; border: 1px solid rgba(0,0,0,0.06); }

.section-header {
    background: linear-gradient(135deg, #4e73df 0%, #36b9cc 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: #fff;
    margin-bottom: 28px;
}
.section-header p { opacity: 0.85; margin-bottom: 0; }

.empty-state-tareas {
    text-align: center;
    padding: 60px 20px;
    color: #adb5bd;
}
.empty-state-tareas i { font-size: 4rem; display: block; margin-bottom: 16px; }
</style>

<div class="container-fluid px-4 py-4">

    <!-- Header con botones principales -->
    <div class="section-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-list-task me-2"></i>Gestión de Tareas</h3>
            <p><?php echo count($tareas); ?> tareas registradas para tus cursos y paralelos</p>
        </div>
        <button class="btn btn-light fw-bold" id="btnCrearTarea"
                data-bs-toggle="modal" data-bs-target="#modalTarea" onclick="limpiarFormTarea()">
            <i class="bi bi-plus-circle me-2"></i>Crear Tarea
        </button>
    </div>

    <!-- Acceso rápido -->
    <div class="action-buttons-row d-flex flex-wrap gap-2 mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTarea" onclick="limpiarFormTarea()" <?php echo empty($cursoParaleloOptions) ? 'disabled' : ''; ?>>
            <i class="bi bi-plus-lg me-2"></i>Crear tarea
        </button>
        <a href="<?php echo BASE_URL; ?>/docente/actividades.php" class="btn btn-outline-secondary">
            <i class="bi bi-journal-text me-2"></i>Actividades
        </a>
    </div>

    <!-- Filtros -->
    <div class="filter-card">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Materia</label>
                <select name="materia" class="form-select form-select-sm">
                    <option value="">Todas las materias</option>
                    <?php foreach ($materias as $m): ?>
                    <option value="<?php echo $m['id']; ?>" <?php echo $filtroMateria==$m['id']?'selected':''; ?>>
                        <?php echo sanitize($m['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Curso / Paralelo</label>
                <select name="curso_paralelo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($cursoParaleloFiltroOptions as $opt): ?>
                    <option value="<?php echo sanitize($opt['value']); ?>" <?php echo $filtroCursoParalelo==$opt['value']?'selected':''; ?>>
                        <?php echo sanitize($opt['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $filtroEstado==='pendiente'?'selected':''; ?>>Pendiente</option>
                    <option value="activa"    <?php echo $filtroEstado==='activa'?'selected':''; ?>>Activa</option>
                    <option value="cerrada"   <?php echo $filtroEstado==='cerrada'?'selected':''; ?>>Cerrada</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-filter me-1"></i>Filtrar
                </button>
            </div>
            <div class="col-md-1">
                <a href="tareas.php" class="btn btn-outline-secondary btn-sm w-100" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Tareas -->
    <?php if (empty($cursoParaleloOptions)): ?>
    <div class="alert alert-warning">
        <strong>Atención:</strong> No tienes cursos y paralelos asignados. Pídele al administrador que te registre con un curso/paralelo válido para que puedas crear tareas.
    </div>
    <?php endif; ?>
    <?php if (empty($tareas)): ?>
    <div class="empty-state-tareas">
        <i class="bi bi-clipboard-x"></i>
        <h5>No hay tareas registradas</h5>
        <p>Usa el botón <strong>"Crear Tarea"</strong> para agregar tu primera tarea.</p>
        <button class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#modalTarea" onclick="limpiarFormTarea()">
            <i class="bi bi-plus-lg me-2"></i>Crear primera tarea
        </button>
    </div>
    <?php else: ?>
    <div class="row g-3" id="listaTareas">
        <?php foreach ($tareas as $t): ?>
        <div class="col-lg-6 col-xl-4 tarea-item" data-id="<?php echo $t['id']; ?>">
            <div class="tarea-card <?php echo $t['tipo']; ?>" onclick="seleccionarTarea(<?php echo $t['id']; ?>, this)">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-<?php echo getTipoBadge($t['tipo']); ?>">
                            <?php
                            $iconoTipo = ['tarea'=>'✏️','quiz'=>'❓','proyecto'=>'🚀','examen'=>'📝'];
                            echo ($iconoTipo[$t['tipo']] ?? '📌') . ' ' . ucfirst($t['tipo']);
                            ?>
                        </span>
                        <span class="badge bg-<?php echo getEstadoTareaBadge($t['estado']); ?>">
                            <?php echo ucfirst($t['estado']); ?>
                        </span>
                    </div>
                    <div class="d-flex gap-2" onclick="event.stopPropagation()">
                        <button class="btn btn-sm btn-outline-primary" type="button"
                                onclick="editarTarea(<?php echo htmlspecialchars(json_encode($t), ENT_QUOTES); ?>); event.stopPropagation();">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta tarea?')">
                            <?php echo csrfInput(); ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>

                <h5 class="fw-bold mb-1"><?php echo sanitize($t['titulo']); ?></h5>
                <p class="text-muted small mb-2"><?php echo sanitize(substr($t['descripcion'] ?? '', 0, 100)); ?><?php echo strlen($t['descripcion'] ?? '') > 100 ? '...' : ''; ?></p>

                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-primary"><i class="bi bi-book me-1"></i><?php echo sanitize($t['materia_nombre']); ?></span>
                    <?php if ($t['curso']): ?>
                    <span class="badge bg-info text-dark"><i class="bi bi-mortarboard me-1"></i>Curso: <?php echo sanitize($t['curso']); ?></span>
                    <?php endif; ?>
                    <?php if ($t['paralelo']): ?>
                    <span class="badge bg-light text-dark border"><i class="bi bi-grid-1x2 me-1"></i>Paralelo: <?php echo sanitize($t['paralelo']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top small text-muted">
                    <div>
                        <?php if ($t['fecha_entrega']): ?>
                        <i class="bi bi-calendar-event me-1"></i>Entrega: <?php echo date('d/m/Y', strtotime($t['fecha_entrega'])); ?>
                        <?php else: ?>
                        <i class="bi bi-calendar-x me-1"></i>Sin fecha límite
                        <?php endif; ?>
                    </div>
                    <div class="fw-bold text-primary">
                        <i class="bi bi-star me-1"></i><?php echo number_format($t['puntaje'], 1); ?> pts
                    </div>
                </div>

                <!-- Botones directos de acción -->
                <div class="d-flex gap-2 mt-3" onclick="event.stopPropagation()">
                    <button class="btn btn-sm btn-outline-warning flex-fill"
                            onclick="editarTarea(<?php echo htmlspecialchars(json_encode($t), ENT_QUOTES); ?>)">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </button>
                    <form method="POST" class="d-inline flex-fill" onsubmit="return confirm('¿Eliminar esta tarea?')">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Crear/Editar Tarea -->
<div class="modal fade" id="modalTarea" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:18px;">
            <form method="POST" id="formTarea">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="id" id="tareaId" value="0">
                <div class="modal-header border-0" style="background: linear-gradient(135deg,#4e73df,#36b9cc); border-radius:18px 18px 0 0; padding:20px 26px;">
                    <h5 class="modal-title text-white fw-bold" id="modalTareaTitle">
                        <i class="bi bi-plus-circle me-2"></i>Crear Nueva Tarea
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Materia -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Materia *</label>
                            <select name="materia_id" id="tareaMateria" class="form-select" required>
                                <?php foreach ($materias as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Tipo -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Tipo *</label>
                            <select name="tipo" id="tareaTipo" class="form-select" required>
                                <option value="tarea">✏️ Tarea</option>
                                <option value="quiz">❓ Quiz</option>
                                <option value="proyecto">🚀 Proyecto</option>
                                <option value="examen">📝 Examen</option>
                            </select>
                        </div>
                        <!-- Curso/Paralelo -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Curso / Paralelo *</label>
                            <select name="curso_paralelo" id="tareaCursoParalelo" class="form-select" required>
                                <option value="">Selecciona curso y paralelo</option>
                                <?php foreach ($cursoParaleloOptions as $opt): ?>
                                <option value="<?php echo sanitize($opt['value']); ?>"><?php echo sanitize($opt['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Estado -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Estado</label>
                            <select name="estado" id="tareaEstado" class="form-select">
                                <option value="pendiente">⏳ Pendiente</option>
                                <option value="activa">✅ Activa</option>
                                <option value="cerrada">🔒 Cerrada</option>
                            </select>
                        </div>
                        <!-- Título -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Título de la Tarea *</label>
                            <input type="text" name="titulo" id="tareaTitulo" class="form-control"
                                   placeholder="Escribe el título de la tarea..." required>
                        </div>
                        <!-- Descripción -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Descripción / Instrucciones</label>
                            <textarea name="descripcion" id="tareaDescripcion" class="form-control" rows="3" data-rich-text
                                      placeholder="Describe las instrucciones o detalles de la tarea..."></textarea>
                        </div>
                        <!-- Fecha y Puntaje -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Fecha de Entrega</label>
                            <input type="date" name="fecha_entrega" id="tareaFecha" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Puntaje / Calificación</label>
                            <div class="input-group">
                                <input type="number" name="puntaje" id="tareaPuntaje" class="form-control"
                                       value="10" step="0.5" min="0" max="100">
                                <span class="input-group-text">pts</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary px-4" id="btnGuardarModal">
                        <i class="bi bi-save me-2"></i>Guardar Tarea
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast de notificación -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toastSeleccion" class="toast" role="alert">
        <div class="toast-header">
            <i class="bi bi-info-circle-fill text-primary me-2"></i>
            <strong class="me-auto">Selección</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastBody">Tarea seleccionada.</div>
    </div>
</div>

<script>
let tareaSeleccionadaId = null;
let tareaSeleccionadaDatos = null;

// ─── Limpiar formulario para nueva tarea ───────────────────────────────
function limpiarFormTarea() {
    tareaSeleccionadaId = null;
    tareaSeleccionadaDatos = null;
    document.getElementById('tareaId').value = '0';
    document.getElementById('tareaMateria').selectedIndex = 0;
    document.getElementById('tareaTipo').value = 'tarea';
    document.getElementById('tareaCursoParalelo').selectedIndex = 0;
    document.getElementById('tareaEstado').value = 'pendiente';
    document.getElementById('tareaTitulo').value = '';
    document.getElementById('tareaDescripcion').value = '';
    document.getElementById('tareaFecha').value = '';
    document.getElementById('tareaPuntaje').value = '10';
    document.getElementById('modalTareaTitle').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Crear Nueva Tarea';
    document.getElementById('btnGuardarModal').innerHTML = '<i class="bi bi-save me-2"></i>Guardar Tarea';
    quitarSeleccionVisual();
}

// ─── Editar una tarea (desde el JSON de la fila) ──────────────────────
function editarTarea(t) {
    document.getElementById('tareaId').value        = t.id;
    document.getElementById('tareaMateria').value   = t.materia_id;
    document.getElementById('tareaTipo').value      = t.tipo;
    document.getElementById('tareaCursoParalelo').value = t.curso && t.paralelo ? (t.curso + '|' + t.paralelo) : '';
    document.getElementById('tareaEstado').value    = t.estado;
    document.getElementById('tareaTitulo').value    = t.titulo;
    RichTextEditor.setValue('#tareaDescripcion', t.descripcion || '');
    document.getElementById('tareaFecha').value     = t.fecha_entrega ? t.fecha_entrega.substring(0,10) : '';
    document.getElementById('tareaPuntaje').value   = t.puntaje;
    document.getElementById('modalTareaTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Editar Tarea';
    document.getElementById('btnGuardarModal').innerHTML = '<i class="bi bi-save-fill me-2"></i>Actualizar Tarea';
    tareaSeleccionadaId = t.id;
    new bootstrap.Modal(document.getElementById('modalTarea')).show();
}

// ─── Seleccionar tarjeta para editar/eliminar con botones de barra ─────
function seleccionarTarea(id, element) {
    quitarSeleccionVisual();
    element.style.outline = '3px solid #4e73df';
    element.style.outlineOffset = '2px';
    element.style.background = '#eef2ff';
    tareaSeleccionadaId = id;
    // Guardar datos de la tarea seleccionada
    const cards = document.querySelectorAll('.tarea-item');
    cards.forEach(card => {
        if (parseInt(card.dataset.id) === id) {
            // No necesitamos JSON extra, ya lo tenemos en el onclick del dropdown
        }
    });
    mostrarToast('Tarea #' + id + ' seleccionada. Usa los botones Editar o Eliminar.');
}

function quitarSeleccionVisual() {
    document.querySelectorAll('.tarea-card').forEach(c => {
        c.style.outline = '';
        c.style.outlineOffset = '';
        c.style.background = '';
    });
}

// ─── Botón EDITAR de la barra de acciones ─────────────────────────────
function solicitarEdicion() {
    if (!tareaSeleccionadaId) {
        mostrarToast('Primero haz clic en una tarjeta de tarea para seleccionarla, luego presiona Editar.');
        return;
    }
    // Buscar el botón Editar del dropdown de esa tarea y hacer click en él
    const items = document.querySelectorAll('.tarea-item');
    items.forEach(item => {
        if (parseInt(item.dataset.id) === tareaSeleccionadaId) {
            const editBtn = item.querySelector('.btn-outline-warning');
            if (editBtn) editBtn.click();
        }
    });
}

// ─── Botón ELIMINAR de la barra de acciones ───────────────────────────
function solicitarEliminacion() {
    if (!tareaSeleccionadaId) {
        mostrarToast('Primero selecciona una tarea haciendo clic sobre su tarjeta.');
        return;
    }
    if (confirm('¿Estás seguro de que deseas eliminar la tarea #' + tareaSeleccionadaId + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + document.querySelector('[name=csrf_token]').value + '">' +
            '<input type="hidden" name="accion" value="eliminar">' +
            '<input type="hidden" name="id" value="' + tareaSeleccionadaId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// ─── Botón GUARDAR de la barra de acciones ────────────────────────────
function guardarTareaActual() {
    if (!tareaSeleccionadaId) {
        // Si no hay selección, abrir modal para crear nueva
        limpiarFormTarea();
        new bootstrap.Modal(document.getElementById('modalTarea')).show();
    } else {
        // Si hay selección, abrir edición
        solicitarEdicion();
    }
}

// ─── Toast de avisos ─────────────────────────────────────────────────
function mostrarToast(msg) {
    document.getElementById('toastBody').textContent = msg;
    const toast = new bootstrap.Toast(document.getElementById('toastSeleccion'), { delay: 3500 });
    toast.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
