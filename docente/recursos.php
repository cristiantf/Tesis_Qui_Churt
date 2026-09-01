<?php
/**
 * Gestión de Recursos Digitales - Panel Docente
 */
$pageTitle = 'Recursos Digitales';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Clases del docente
$clases = $db->query("
    SELECT mc.*, m.nombre as materia_nombre 
    FROM materia_curso mc 
    JOIN materias m ON mc.materia_id = m.id 
    WHERE mc.docente_id = $userId
    ORDER BY m.nombre, mc.grado, mc.paralelo
")->fetchAll();

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
    $claseSeleccionada = explode('_', $_POST['clase_id']);
    $materia_id = intval($claseSeleccionada[0] ?? 0);
    $grado = intval($claseSeleccionada[1] ?? 0);
    $paralelo = trim($claseSeleccionada[2] ?? '');
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
    
    if ($titulo) {
        if (isset($_POST['id']) && intval($_POST['id']) > 0) {
            $id = intval($_POST['id']);
            if ($archivo) {
                $stmt = $db->prepare("UPDATE recursos SET materia_id=?, grado=?, paralelo=?, titulo=?, descripcion=?, archivo=?, tipo=? WHERE id=? AND docente_id=?");
                $stmt->execute([$materia_id, $grado, $paralelo, $titulo, $descripcion, $archivo, $tipo, $id, $userId]);
            } else {
                $stmt = $db->prepare("UPDATE recursos SET materia_id=?, grado=?, paralelo=?, titulo=?, descripcion=?, tipo=? WHERE id=? AND docente_id=?");
                $stmt->execute([$materia_id, $grado, $paralelo, $titulo, $descripcion, $tipo, $id, $userId]);
            }
            setFlashMessage('success', 'Recurso actualizado correctamente.');
        } else {
            if ($archivo) {
                $stmt = $db->prepare("INSERT INTO recursos (materia_id, docente_id, grado, paralelo, titulo, descripcion, archivo, tipo) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$materia_id, $userId, $grado, $paralelo, $titulo, $descripcion, $archivo, $tipo]);
                setFlashMessage('success', 'Recurso subido correctamente.');
            }
        }
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
                            <span class="badge bg-primary small"><?php echo sanitize($r['materia_nombre']); ?> (<?php echo intval($r['grado']) ? $r['grado'].'° ' : ''; ?><?php echo sanitize($r['paralelo']); ?>)</span>
                            <span class="badge bg-light text-dark small"><?php echo ucfirst($r['tipo']); ?></span>
                            <span class="text-muted" style="font-size:0.7rem;"><?php echo date('d/m/Y', strtotime($r['fecha_creacion'])); ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2 ms-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" title="Exportar">
                                <i class="bi bi-download"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="#" onclick='exportarRecursoPDF(<?php echo htmlspecialchars(json_encode($r)); ?>)'><i class="bi bi-file-pdf text-danger me-2"></i>Exportar PDF</a></li>
                                <li><a class="dropdown-item" href="#" onclick='exportarATXT(<?php echo htmlspecialchars(json_encode($r['titulo'])); ?>, <?php echo htmlspecialchars(json_encode($r['descripcion'])); ?>, <?php echo htmlspecialchars(json_encode($r['tipo'] === "enlace" ? "Enlace: " . $r['archivo'] : "")); ?>, "Recurso_<?php echo $r['id']; ?>")'><i class="bi bi-file-text text-primary me-2"></i>Exportar TXT</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick='editarRecurso(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8"); ?>)' title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="confirmarEliminar('?eliminar=<?php echo $r['id']; ?>','<?php echo sanitize($r['titulo']); ?>')" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
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
                <input type="hidden" name="id" id="recId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRecTitle"><i class="bi bi-cloud-upload me-2"></i>Subir Recurso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Clase (Materia - Curso) *</label>
                        <select name="clase_id" id="recClase" class="form-select" required>
                            <?php foreach ($clases as $c): 
                                $val = $c['materia_id'].'_'.$c['grado'].'_'.$c['paralelo'];
                            ?>
                            <option value="<?php echo $val; ?>">
                                <?php echo sanitize($c['materia_nombre']) . ' - ' . $c['grado'] . '° ' . $c['paralelo']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Título *</label>
                        <input type="text" name="titulo" id="recTitulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Descripción</label>
                        <textarea name="descripcion" id="recDescripcion" class="form-control" rows="2"></textarea>
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
                        <label class="form-label fw-semibold small">Archivo <span id="lblArchivoReq">*</span></label>
                        <input type="file" name="archivo" id="recArchivo" class="form-control">
                        <small class="text-muted d-none" id="lblArchivoInfo">Deja en blanco para mantener el archivo actual.</small>
                    </div>
                    <div class="mb-3 d-none" id="divEnlace">
                        <label class="form-label fw-semibold small">URL del Enlace *</label>
                        <input type="url" name="enlace" id="recEnlace" class="form-control" placeholder="https://...">
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

document.querySelector('[data-bs-target="#modalRecurso"]').addEventListener('click', function() {
    document.getElementById('recId').value = '0';
    document.getElementById('recTitulo').value = '';
    document.getElementById('recDescripcion').value = '';
    document.getElementById('tipoRecurso').value = 'documento';
    document.getElementById('recEnlace').value = '';
    document.getElementById('lblArchivoInfo').classList.add('d-none');
    document.getElementById('lblArchivoReq').innerText = '*';
    document.getElementById('modalRecTitle').innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Subir Recurso';
    toggleArchivoEnlace();
});

function editarRecurso(r) {
    document.getElementById('recId').value = r.id;
    let claseVal = r.materia_id + '_' + r.grado + '_' + r.paralelo;
    document.getElementById('recClase').value = claseVal;
    document.getElementById('recTitulo').value = r.titulo;
    document.getElementById('recDescripcion').value = r.descripcion;
    document.getElementById('tipoRecurso').value = r.tipo;
    
    if (r.tipo === 'enlace') {
        document.getElementById('recEnlace').value = r.archivo;
    } else {
        document.getElementById('lblArchivoInfo').classList.remove('d-none');
        document.getElementById('lblArchivoReq').innerText = '';
    }
    
    document.getElementById('modalRecTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Recurso';
    toggleArchivoEnlace();
    new bootstrap.Modal(document.getElementById('modalRecurso')).show();
}

function exportarRecursoPDF(r) {
    const tempDiv = document.createElement('div');
    tempDiv.style.padding = '30px';
    tempDiv.style.fontFamily = 'Arial, sans-serif';
    tempDiv.innerHTML = `
        <h2 style="color: #0d6efd; margin-bottom: 5px;">${r.titulo}</h2>
        <p style="color: #6c757d; font-size: 14px; border-bottom: 2px solid #dee2e6; padding-bottom: 10px;">
            <strong>Materia:</strong> ${r.materia_nombre} | <strong>Grado:</strong> ${r.grado}° ${r.paralelo} <br>
            <strong>Tipo:</strong> ${r.tipo.toUpperCase()}
        </p>
        <h4 style="margin-top: 20px;">Descripción</h4>
        <p style="white-space: pre-wrap; font-size: 15px;">${r.descripcion || 'Sin descripción'}</p>
        ${r.tipo === 'enlace' ? `<h4 style="margin-top: 20px;">Enlace Adjunto</h4><p style="white-space: pre-wrap; font-size: 15px;"><a href="${r.archivo}">${r.archivo}</a></p>` : ''}
    `;
    
    const opt = {
        margin:       15,
        filename:     'Recurso_' + r.id + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(tempDiv).save();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
