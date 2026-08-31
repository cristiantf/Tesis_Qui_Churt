<?php
/**
 * Gestión de Recursos Digitales - Panel Docente
 */
$pageTitle = 'Recursos Digitales';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

$materias = $db->query("SELECT m.* FROM materias m JOIN materia_docente md ON m.id = md.materia_id WHERE md.docente_id = $userId")->fetchAll();

// Eliminar recurso
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $recurso = $db->prepare("SELECT archivo FROM recursos WHERE id = ? AND docente_id = ?");
    $recurso->execute([$id, $userId]);
    $rec = $recurso->fetch();
    if ($rec) {
        $filepath = BASE_PATH . '/uploads/recursos/' . $rec['archivo'];
        if (file_exists($filepath)) unlink($filepath);
        $db->prepare("DELETE FROM recursos WHERE id = ?")->execute([$id]);
        setFlashMessage('success', 'Recurso eliminado.');
    }
    header('Location: ' . BASE_URL . '/docente/recursos.php');
    exit;
}

// Subir recurso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materia_id = intval($_POST['materia_id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = $_POST['tipo'];
    
    $archivo = '';
    if ($tipo === 'enlace') {
        $archivo = trim($_POST['enlace'] ?? '');
    } elseif (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === 0) {
        $uploadDir = BASE_PATH . '/uploads/recursos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $archivo = uniqid('rec_') . '.' . $ext;
        move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadDir . $archivo);
    }
    
    if ($titulo && $archivo) {
        $stmt = $db->prepare("INSERT INTO recursos (materia_id, docente_id, titulo, descripcion, archivo, tipo) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$materia_id, $userId, $titulo, $descripcion, $archivo, $tipo]);
        setFlashMessage('success', 'Recurso subido correctamente.');
    }
    header('Location: ' . BASE_URL . '/docente/recursos.php');
    exit;
}

$recursos = $db->query("
    SELECT r.*, m.nombre as materia_nombre 
    FROM recursos r 
    JOIN materias m ON r.materia_id = m.id 
    WHERE r.docente_id = $userId 
    ORDER BY r.fecha_creacion DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-folder me-2"></i>Recursos Digitales</h3>
            <p class="text-muted mb-0"><?php echo count($recursos); ?> recursos disponibles</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRecurso">
            <i class="bi bi-cloud-upload me-2"></i>Subir Recurso
        </button>
    </div>

    <div class="row g-3">
        <?php if (empty($recursos)): ?>
        <div class="col-12"><div class="card"><div class="card-body empty-state"><i class="bi bi-folder-x"></i><h5>Sin recursos</h5><p>Sube tu primer recurso digital.</p></div></div></div>
        <?php else: foreach ($recursos as $r): ?>
        <div class="col-md-6 col-lg-4">
            <div class="resource-card h-100">
                <div class="d-flex align-items-start">
                    <div class="resource-icon <?php echo $r['tipo']; ?> me-3">
                        <i class="bi bi-<?php echo $r['tipo']==='documento'?'file-earmark-text':($r['tipo']==='video'?'play-circle':($r['tipo']==='imagen'?'image':($r['tipo']==='enlace'?'link-45deg':($r['tipo']==='presentacion'?'easel':'file-earmark')))); ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1"><?php echo sanitize($r['titulo']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo sanitize(substr($r['descripcion'], 0, 80)); ?></p>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="badge bg-primary small"><?php echo sanitize($r['materia_nombre']); ?></span>
                            <span class="badge bg-light text-dark small"><?php echo ucfirst($r['tipo']); ?></span>
                            <span class="text-muted" style="font-size:0.7rem;"><?php echo date('d/m/Y', strtotime($r['fecha_creacion'])); ?></span>
                        </div>
                    </div>
                    <button class="btn btn-outline-danger btn-sm ms-2" onclick="confirmarEliminar('?eliminar=<?php echo $r['id']; ?>','<?php echo sanitize($r['titulo']); ?>')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Modal Subir Recurso -->
<div class="modal fade" id="modalRecurso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Subir Recurso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Materia *</label>
                        <select name="materia_id" class="form-select" required>
                            <?php foreach ($materias as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Título *</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tipo *</label>
                        <select name="tipo" id="tipoRecurso" class="form-select" required onchange="toggleArchivoEnlace()">
                            <option value="documento">📄 Documento</option>
                            <option value="video">🎬 Video</option>
                            <option value="imagen">🖼️ Imagen</option>
                            <option value="presentacion">📊 Presentación</option>
                            <option value="enlace">🔗 Enlace</option>
                            <option value="otro">📎 Otro</option>
                        </select>
                    </div>
                    <div class="mb-3" id="divArchivo">
                        <label class="form-label fw-semibold small">Archivo *</label>
                        <input type="file" name="archivo" class="form-control">
                    </div>
                    <div class="mb-3 d-none" id="divEnlace">
                        <label class="form-label fw-semibold small">URL del Enlace *</label>
                        <input type="url" name="enlace" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Subir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleArchivoEnlace() {
    const tipo = document.getElementById('tipoRecurso').value;
    document.getElementById('divArchivo').classList.toggle('d-none', tipo === 'enlace');
    document.getElementById('divEnlace').classList.toggle('d-none', tipo !== 'enlace');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
