<?php
/**
 * Gestión de Juegos Educativos - Docente
 */
$pageTitle = 'Juegos Educativos';
require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];
$materiaId = intval($_GET['materia_id'] ?? 0);
$materia = getMateriaById($materiaId);

if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/docente/index.php');
    exit;
}

// Eliminar un juego
if (isset($_POST['eliminar_juego']) && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $juegoId = intval($_POST['juego_id']);
    try {
        $stmt = $db->prepare("DELETE FROM juegos WHERE id = ? AND docente_id = ?");
        $stmt->execute([$juegoId, $userId]);
        setFlashMessage('success', 'Juego eliminado correctamente.');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error al eliminar el juego.');
    }
    header("Location: juegos.php?materia_id=$materiaId");
    exit;
}

// Eliminar un intento
if (isset($_POST['eliminar_intento']) && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $intentoId = intval($_POST['intento_id']);
    try {
        // Verificar que el intento pertenece a un juego de este docente
        $stmt = $db->prepare("
            DELETE ji FROM juegos_intentos ji
            JOIN juegos_sesiones js ON ji.sesion_id = js.id
            JOIN juegos j ON js.juego_id = j.id
            WHERE ji.id = ? AND j.docente_id = ?
        ");
        $stmt->execute([$intentoId, $userId]);
        setFlashMessage('success', 'Intento eliminado correctamente.');
    } catch (PDOException $e) {
        setFlashMessage('danger', 'Error al eliminar el intento.');
    }
    header("Location: juegos.php?materia_id=$materiaId");
    exit;
}

// Obtener los juegos creados
$stmt = $db->prepare("SELECT * FROM juegos WHERE materia_id = ? AND docente_id = ? ORDER BY fecha_creacion DESC");
$stmt->execute([$materiaId, $userId]);
$juegos = $stmt->fetchAll();

$theme = getMateriaThemeClass($materia['nombre']);
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4 <?php echo $theme; ?>">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-controller me-2"></i>Juegos Educativos</h2>
            <p class="text-muted">Materia: <?php echo sanitize($materia['nombre']); ?></p>
        </div>
        <div>
            <a href="materia.php?id=<?php echo $materiaId; ?>" class="btn btn-outline-secondary me-2">Volver</a>
            <a href="crear_juego.php?materia_id=<?php echo $materiaId; ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo Juego
            </a>
        </div>
    </div>

    <?php if (empty($juegos)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-joystick fs-1 d-block mb-3"></i>
            <h4>No hay juegos creados</h4>
            <p>Comienza creando un laberinto del conocimiento con inteligencia artificial.</p>
            <a href="crear_juego.php?materia_id=<?php echo $materiaId; ?>" class="btn btn-primary mt-2">Crear mi primer juego</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($juegos as $juego): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 text-truncate" title="<?php echo sanitize($juego['titulo']); ?>">
                                <?php echo sanitize($juego['titulo']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text mb-1"><strong>Asignatura:</strong> <?php echo sanitize($juego['asignatura']); ?></p>
                            <p class="card-text mb-3"><strong>Tema:</strong> <?php echo sanitize($juego['tema']); ?></p>
                            
                            <?php
                            // Obtener cantidad de preguntas
                            $stmtP = $db->prepare("SELECT COUNT(*) FROM juegos_preguntas WHERE juego_id = ?");
                            $stmtP->execute([$juego['id']]);
                            $numPreguntas = $stmtP->fetchColumn();
                            
                            // Obtener la sesión activa si la hay
                            $stmtS = $db->prepare("SELECT * FROM juegos_sesiones WHERE juego_id = ? ORDER BY fecha_activacion DESC LIMIT 1");
                            $stmtS->execute([$juego['id']]);
                            $sesionActiva = $stmtS->fetch();
                            ?>
                            
                            <span class="badge bg-secondary mb-3"><?php echo $numPreguntas; ?> preguntas</span>
                            
                            <?php if ($sesionActiva): ?>
                                <div class="alert alert-success py-2 mb-0">
                                    <small><i class="bi bi-play-circle-fill me-1"></i>Activado para: <?php echo $sesionActiva['grado'] ? $sesionActiva['grado'].'° "'.$sesionActiva['paralelo'].'"' : 'Todos'; ?></small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light py-2 mb-0 border">
                                    <small><i class="bi bi-pause-circle me-1"></i>No activado</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                            <a href="activar_juego.php?id=<?php echo $juego['id']; ?>&materia_id=<?php echo $materiaId; ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-lightning-charge"></i> Activar
                            </a>
                            <form method="post" action="juegos.php?materia_id=<?php echo $materiaId; ?>" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este juego?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="juego_id" value="<?php echo $juego['id']; ?>">
                                <button type="submit" name="eliminar_juego" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Sección de intentos recientes -->
        <h4 class="mt-5 mb-3"><i class="bi bi-clock-history me-2"></i>Intentos Recientes</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Estudiante</th>
                                <th>Juego</th>
                                <th>Puntaje</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmtI = $db->prepare("
                                SELECT ji.id, ji.puntaje, ji.fecha_intento, u.nombre, u.apellido, j.titulo 
                                FROM juegos_intentos ji
                                JOIN usuarios u ON ji.estudiante_id = u.id
                                JOIN juegos_sesiones js ON ji.sesion_id = js.id
                                JOIN juegos j ON js.juego_id = j.id
                                WHERE j.docente_id = ? AND j.materia_id = ?
                                ORDER BY ji.fecha_intento DESC
                                LIMIT 20
                            ");
                            $stmtI->execute([$userId, $materiaId]);
                            $intentos = $stmtI->fetchAll();
                            
                            if (empty($intentos)):
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No hay intentos registrados aún.</td>
                                </tr>
                            <?php else: foreach ($intentos as $intento): ?>
                                <tr>
                                    <td><?php echo sanitize($intento['nombre'] . ' ' . $intento['apellido']); ?></td>
                                    <td><?php echo sanitize($intento['titulo']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $intento['puntaje']; ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($intento['fecha_intento'])); ?></td>
                                    <td>
                                        <form method="post" action="juegos.php?materia_id=<?php echo $materiaId; ?>" class="d-inline" onsubmit="return confirm('¿Deseas eliminar este intento para que el estudiante pueda volver a jugar?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="intento_id" value="<?php echo $intento['id']; ?>">
                                            <button type="submit" name="eliminar_intento" class="btn btn-sm btn-danger py-0 px-2" title="Eliminar intento">
                                                <i class="bi bi-x-circle"></i>
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
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
