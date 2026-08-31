<?php
/**
 * Gestión de Actividades - Panel Docente
 */
$pageTitle = 'Actividades';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Materias del docente
$materias = $db->query("SELECT m.* FROM materias m JOIN materia_docente md ON m.id = md.materia_id WHERE md.docente_id = $userId")->fetchAll();

// Procesar eliminación
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $db->prepare("DELETE FROM actividades WHERE id = ? AND docente_id = ?");
    $stmt->execute([$id, $userId]);
    setFlashMessage('success', 'Actividad eliminada.');
    header('Location: ' . BASE_URL . '/docente/actividades.php');
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $materia_id = intval($_POST['materia_id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $tipo_metodologia = $_POST['tipo_metodologia'];
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?: null;
    $fecha_limite = $_POST['fecha_limite'] ?: null;
    $puntaje_maximo = floatval($_POST['puntaje_maximo'] ?? 100);
    $estado = $_POST['estado'];
    
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE actividades SET materia_id=?, titulo=?, descripcion=?, tipo_metodologia=?, instrucciones=?, fecha_inicio=?, fecha_limite=?, puntaje_maximo=?, estado=? WHERE id=? AND docente_id=?");
        $stmt->execute([$materia_id, $titulo, $descripcion, $tipo_metodologia, $instrucciones, $fecha_inicio, $fecha_limite, $puntaje_maximo, $estado, $id, $userId]);
        setFlashMessage('success', 'Actividad actualizada.');
    } else {
        $stmt = $db->prepare("INSERT INTO actividades (materia_id, docente_id, titulo, descripcion, tipo_metodologia, instrucciones, fecha_inicio, fecha_limite, puntaje_maximo, estado) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$materia_id, $userId, $titulo, $descripcion, $tipo_metodologia, $instrucciones, $fecha_inicio, $fecha_limite, $puntaje_maximo, $estado]);
        setFlashMessage('success', 'Actividad creada.');
    }
    header('Location: ' . BASE_URL . '/docente/actividades.php');
    exit;
}

// Filtros
$filtroMateria = $_GET['materia'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$sql = "SELECT a.*, m.nombre as materia_nombre, (SELECT COUNT(*) FROM entregas WHERE actividad_id = a.id) as total_entregas FROM actividades a JOIN materias m ON a.materia_id = m.id WHERE a.docente_id = ?";
$params = [$userId];

if ($filtroMateria) { $sql .= " AND a.materia_id = ?"; $params[] = $filtroMateria; }
if ($filtroEstado) { $sql .= " AND a.estado = ?"; $params[] = $filtroEstado; }
$sql .= " ORDER BY a.fecha_creacion DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$actividades = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-journal-text me-2"></i>Mis Actividades</h3>
            <p class="text-muted mb-0"><?php echo count($actividades); ?> actividades creadas</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/docente/tareas.php" class="btn btn-outline-primary">
                <i class="bi bi-list-check me-1"></i>Tareas
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalActividad" onclick="limpiarForm()">
                <i class="bi bi-plus-lg me-2"></i>Crear actividad
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <select name="materia" class="form-select">
                        <option value="">Todas las materias</option>
                        <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['id']; ?>" <?php echo $filtroMateria==$m['id']?'selected':''; ?>><?php echo sanitize($m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="borrador" <?php echo $filtroEstado==='borrador'?'selected':''; ?>>Borrador</option>
                        <option value="publicada" <?php echo $filtroEstado==='publicada'?'selected':''; ?>>Publicada</option>
                        <option value="cerrada" <?php echo $filtroEstado==='cerrada'?'selected':''; ?>>Cerrada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de actividades -->
    <?php if (empty($actividades)): ?>
    <div class="card"><div class="card-body empty-state"><i class="bi bi-journal-x"></i><h5>No hay actividades</h5><p>Crea tu primera actividad usando el botón de arriba.</p></div></div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($actividades as $a): ?>
        <div class="col-lg-6">
            <div class="activity-card <?php echo $a['tipo_metodologia']; ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-<?php echo getEstadoBadge($a['estado']); ?> mb-1"><?php echo ucfirst($a['estado']); ?></span>
                        <h5 class="fw-bold mb-1"><?php echo sanitize($a['titulo']); ?></h5>
                        <span class="badge bg-primary small"><?php echo sanitize($a['materia_nombre']); ?></span>
                        <span class="badge bg-light text-dark small"><i class="<?php echo getMetodologiaIcon($a['tipo_metodologia']); ?> me-1"></i><?php echo getMetodologiaName($a['tipo_metodologia']); ?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick='editarActividad(<?php echo json_encode($a); ?>)'><i class="bi bi-pencil"></i></button>
                        <a class="btn btn-sm btn-outline-danger" href="?eliminar=<?php echo $a['id']; ?>" onclick="return confirm('¿Eliminar actividad?')"><i class="bi bi-trash"></i></a>
                    </div>
                </div>
                <p class="text-muted small mb-2"><?php echo sanitize(substr($a['descripcion'], 0, 120)); ?>...</p>
                <div class="d-flex justify-content-between align-items-center small">
                    <div class="text-muted">
                        <?php if ($a['fecha_limite']): ?>
                        <i class="bi bi-calendar me-1"></i>Límite: <?php echo date('d/m/Y', strtotime($a['fecha_limite'])); ?>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-info"><?php echo $a['total_entregas']; ?> entregas</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Actividad -->
<div class="modal fade" id="modalActividad" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="id" id="actId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalActTitle"><i class="bi bi-plus-lg me-2"></i>Nueva Actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Materia *</label>
                            <select name="materia_id" id="actMateria" class="form-select" required>
                                <?php foreach ($materias as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Metodología *</label>
                            <select name="tipo_metodologia" id="actMetodologia" class="form-select" required>
                                <option value="abp">🧠 Aprendizaje Basado en Problemas</option>
                                <option value="clase_invertida">🔄 Clase Invertida</option>
                                <option value="gamificacion">🎮 Gamificación</option>
                                <option value="colaborativo">👥 Aprendizaje Colaborativo</option>
                                <option value="estudio_caso">🔍 Estudio de Caso</option>
                                <option value="debate">💬 Debate</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Título *</label>
                            <input type="text" name="titulo" id="actTitulo" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Descripción *</label>
                            <textarea name="descripcion" id="actDescripcion" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Instrucciones</label>
                            <textarea name="instrucciones" id="actInstrucciones" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" id="actFechaInicio" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Fecha Límite</label>
                            <input type="date" name="fecha_limite" id="actFechaLimite" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Puntaje Máx.</label>
                            <input type="number" name="puntaje_maximo" id="actPuntaje" class="form-control" value="100" step="0.5">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Estado</label>
                            <select name="estado" id="actEstado" class="form-select">
                                <option value="borrador">Borrador</option>
                                <option value="publicada">Publicada</option>
                                <option value="cerrada">Cerrada</option>
                            </select>
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
function limpiarForm() {
    document.getElementById('actId').value = '0';
    document.getElementById('actTitulo').value = '';
    document.getElementById('actDescripcion').value = '';
    document.getElementById('actInstrucciones').value = '';
    document.getElementById('actFechaInicio').value = '';
    document.getElementById('actFechaLimite').value = '';
    document.getElementById('actPuntaje').value = '100';
    document.getElementById('actEstado').value = 'borrador';
    document.getElementById('modalActTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Nueva Actividad';
}

function editarActividad(a) {
    document.getElementById('actId').value = a.id;
    document.getElementById('actMateria').value = a.materia_id;
    document.getElementById('actMetodologia').value = a.tipo_metodologia;
    document.getElementById('actTitulo').value = a.titulo;
    document.getElementById('actDescripcion').value = a.descripcion;
    document.getElementById('actInstrucciones').value = a.instrucciones || '';
    document.getElementById('actFechaInicio').value = a.fecha_inicio || '';
    document.getElementById('actFechaLimite').value = a.fecha_limite || '';
    document.getElementById('actPuntaje').value = a.puntaje_maximo;
    document.getElementById('actEstado').value = a.estado;
    document.getElementById('modalActTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Actividad';
    new bootstrap.Modal(document.getElementById('modalActividad')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
