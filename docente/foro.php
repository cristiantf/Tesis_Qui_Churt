<?php
/**
 * Foro de Discusión - Panel Docente
 */
$pageTitle = 'Foro de Discusión';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];

// Obtener materias del docente
$materias = $db->query("SELECT m.* FROM materias m JOIN materia_docente md ON m.id = md.materia_id WHERE md.docente_id = $userId")->fetchAll();
$materiasIds = array_column($materias, 'id');
$inClause = $materiasIds ? implode(',', $materiasIds) : '0';

// Crear tema o responder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear_tema') {
        $stmt = $db->prepare("INSERT INTO foro_temas (materia_id, usuario_id, titulo, contenido) VALUES (?,?,?,?)");
        $stmt->execute([intval($_POST['materia_id']), $userId, trim($_POST['titulo']), trim($_POST['contenido'])]);
        setFlashMessage('success', 'Tema creado correctamente.');
    } elseif ($_POST['accion'] === 'responder') {
        $stmt = $db->prepare("INSERT INTO foro_respuestas (tema_id, usuario_id, contenido) VALUES (?,?,?)");
        $stmt->execute([intval($_POST['tema_id']), $userId, trim($_POST['contenido'])]);
        setFlashMessage('success', 'Respuesta publicada.');
    } elseif ($_POST['accion'] === 'editar_tema') {
        $stmt = $db->prepare("UPDATE foro_temas SET titulo = ?, contenido = ? WHERE id = ? AND usuario_id = ?");
        $stmt->execute([trim($_POST['titulo']), trim($_POST['contenido']), intval($_POST['tema_id']), $userId]);
        setFlashMessage('success', 'Tema actualizado correctamente.');
    }
    header('Location: ' . BASE_URL . '/docente/foro.php' . (isset($_POST['tema_id']) ? '?tema=' . $_POST['tema_id'] : ''));
    exit;
}

// Eliminar tema
if (isset($_GET['eliminar_tema'])) {
    $id = intval($_GET['eliminar_tema']);
    $stmt = $db->prepare("DELETE FROM foro_temas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $userId]);
    setFlashMessage('success', 'Tema eliminado.');
    header('Location: ' . BASE_URL . '/docente/foro.php');
    exit;
}

// Ver tema específico
$verTema = isset($_GET['tema']) ? intval($_GET['tema']) : 0;
$tema = null;
$respuestas = [];

if ($verTema) {
    $stmt = $db->prepare("
        SELECT ft.*, u.nombre, u.apellido, u.rol, m.nombre as materia_nombre
        FROM foro_temas ft 
        JOIN usuarios u ON ft.usuario_id = u.id 
        JOIN materias m ON ft.materia_id = m.id
        WHERE ft.id = ?
    ");
    $stmt->execute([$verTema]);
    $tema = $stmt->fetch();
    
    if ($tema) {
        $respStmt = $db->prepare("
            SELECT fr.*, u.nombre, u.apellido, u.rol 
            FROM foro_respuestas fr 
            JOIN usuarios u ON fr.usuario_id = u.id 
            WHERE fr.tema_id = ? 
            ORDER BY fr.fecha_creacion ASC
        ");
        $respStmt->execute([$verTema]);
        $respuestas = $respStmt->fetchAll();
    }
}

// Listar temas
$temas = $db->query("
    SELECT ft.*, u.nombre, u.apellido, m.nombre as materia_nombre,
           (SELECT COUNT(*) FROM foro_respuestas fr WHERE fr.tema_id = ft.id) as total_respuestas
    FROM foro_temas ft 
    JOIN usuarios u ON ft.usuario_id = u.id 
    JOIN materias m ON ft.materia_id = m.id
    WHERE ft.materia_id IN ($inClause)
    ORDER BY ft.fijado DESC, ft.fecha_creacion DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if ($verTema && $tema): ?>
    <!-- Vista de tema -->
    <a href="<?php echo BASE_URL; ?>/docente/foro.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left me-1"></i>Volver al foro</a>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge bg-primary mb-2"><?php echo sanitize($tema['materia_nombre']); ?></span>
                    <h4 class="fw-bold"><?php echo sanitize($tema['titulo']); ?></h4>
                    <p class="text-muted small">
                        <i class="bi bi-person me-1"></i><?php echo sanitize($tema['nombre'] . ' ' . $tema['apellido']); ?> 
                        (<?php echo ucfirst($tema['rol']); ?>) · 
                        <i class="bi bi-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($tema['fecha_creacion'])); ?>
                    </p>
                </div>
                <?php if ($tema['usuario_id'] == $userId): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick='editarTema(<?php echo json_encode($tema); ?>)'><i class="bi bi-pencil"></i></button>
                    <a class="btn btn-sm btn-outline-danger" href="?eliminar_tema=<?php echo $tema['id']; ?>" onclick="return confirm('¿Eliminar este tema y todas sus respuestas?')"><i class="bi bi-trash"></i></a>
                </div>
                <?php endif; ?>
            </div>
            <hr>
            <p><?php echo nl2br(sanitize($tema['contenido'])); ?></p>
        </div>
    </div>

    <!-- Respuestas -->
    <h5 class="fw-bold mb-3"><i class="bi bi-chat-dots me-2"></i><?php echo count($respuestas); ?> Respuestas</h5>
    <?php foreach ($respuestas as $r): ?>
    <div class="forum-reply">
        <div class="d-flex justify-content-between">
            <strong class="small">
                <div class="avatar-circle d-inline-flex me-1" style="width:24px;height:24px;font-size:0.55rem;">
                    <?php echo strtoupper(substr($r['nombre'],0,1).substr($r['apellido'],0,1)); ?>
                </div>
                <?php echo sanitize($r['nombre'] . ' ' . $r['apellido']); ?>
                <span class="badge bg-<?php echo $r['rol']==='docente'?'primary':'success'; ?> ms-1"><?php echo ucfirst($r['rol']); ?></span>
            </strong>
            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($r['fecha_creacion'])); ?></small>
        </div>
        <p class="mt-2 mb-0"><?php echo nl2br(sanitize($r['contenido'])); ?></p>
    </div>
    <?php endforeach; ?>

    <!-- Responder -->
    <div class="card mt-3">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="accion" value="responder">
                <input type="hidden" name="tema_id" value="<?php echo $verTema; ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tu respuesta</label>
                    <textarea name="contenido" class="form-control" rows="3" required placeholder="Escribe tu respuesta..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Responder</button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- Lista de temas -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2"></i>Foro de Discusión</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTema">
            <i class="bi bi-plus-lg me-2"></i>Nuevo Tema
        </button>
    </div>

    <?php if (empty($temas)): ?>
    <div class="card"><div class="card-body empty-state"><i class="bi bi-chat-square-text"></i><h5>Sin temas</h5><p>Inicia una discusión creando un nuevo tema.</p></div></div>
    <?php else: foreach ($temas as $t): ?>
    <a href="?tema=<?php echo $t['id']; ?>" class="text-decoration-none">
        <div class="forum-topic">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <?php if ($t['fijado']): ?><span class="badge bg-warning text-dark me-1"><i class="bi bi-pin-angle"></i></span><?php endif; ?>
                    <h6 class="fw-bold text-dark mb-1"><?php echo sanitize($t['titulo']); ?></h6>
                    <div class="d-flex gap-2 align-items-center small text-muted">
                        <span><i class="bi bi-person"></i> <?php echo sanitize($t['nombre'] . ' ' . $t['apellido']); ?></span>
                        <span class="badge bg-primary"><?php echo sanitize($t['materia_nombre']); ?></span>
                        <span><i class="bi bi-clock"></i> <?php echo date('d/m/Y', strtotime($t['fecha_creacion'])); ?></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-secondary rounded-pill"><?php echo $t['total_respuestas']; ?> <i class="bi bi-chat"></i></span>
                    <?php if ($t['usuario_id'] == $userId): ?>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-danger" href="?eliminar_tema=<?php echo $t['id']; ?>" onclick="return confirm('¿Eliminar este tema?'); event.stopPropagation();" title="Eliminar"><i class="bi bi-trash"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; endif; ?>

    <!-- Modal Nuevo Tema -->
    <div class="modal fade" id="modalTema" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formTema">
                    <input type="hidden" name="accion" id="temaAccion" value="crear_tema">
                    <input type="hidden" name="tema_id" id="temaId" value="0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTemaTitle"><i class="bi bi-plus-lg me-2"></i>Nuevo Tema de Discusión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3" id="divMateriaTema">
                            <label class="form-label fw-semibold small">Materia</label>
                            <select name="materia_id" class="form-select" required>
                                <?php foreach ($materias as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo sanitize($m['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Título</label>
                            <input type="text" name="titulo" id="temaTitulo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Contenido</label>
                            <textarea name="contenido" id="temaContenido" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Publicar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelector('[data-bs-target="#modalTema"]')?.addEventListener('click', function() {
    document.getElementById('temaAccion').value = 'crear_tema';
    document.getElementById('temaId').value = '0';
    document.getElementById('temaTitulo').value = '';
    document.getElementById('temaContenido').value = '';
    document.getElementById('divMateriaTema').style.display = 'block';
    document.getElementById('modalTemaTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Nuevo Tema de Discusión';
});

function editarTema(t) {
    document.getElementById('temaAccion').value = 'editar_tema';
    document.getElementById('temaId').value = t.id;
    document.getElementById('temaTitulo').value = t.titulo;
    document.getElementById('temaContenido').value = t.contenido;
    document.getElementById('divMateriaTema').style.display = 'none'; // No se edita la materia del tema
    document.getElementById('modalTemaTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Tema';
    new bootstrap.Modal(document.getElementById('modalTema')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
