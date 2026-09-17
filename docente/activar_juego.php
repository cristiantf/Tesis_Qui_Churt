<?php
/**
 * Activar un juego educativo (docente)
 * Permite programar una sesión para un grado/paralelo y fecha/hora.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];
$juegoId = intval($_GET['juego_id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM juegos WHERE id = ? AND docente_id = ?');
$stmt->execute([$juegoId, $userId]);
$juego = $stmt->fetch();
if (!$juego) {
    setFlashMessage('danger', 'Juego no encontrado o sin permisos.');
    header('Location: juegos.php');
    exit;
}

$theme = getMateriaThemeClass($juego['tema'] ?? '');

$grado = '';
$paralelo = '';
$fechaActivacion = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errores[] = 'Token CSRF inválido.';
    }
    $grado = trim($_POST['grado'] ?? '');
    $paralelo = trim($_POST['paralelo'] ?? '');
    $fechaActivacion = trim($_POST['fecha_activacion'] ?? '');
    if ($grado === '' || $paralelo === '' || $fechaActivacion === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    }
    if (!in_array($paralelo, ['A','B','C','D'])) {
        $errores[] = 'Paralelo debe ser A, B, C o D.';
    }
    if (empty($errores)) {
        try {
            $stmt = $db->prepare('INSERT INTO juegos_sesiones (juego_id, actividad_id, grado, paralelo, fecha_activacion) VALUES (?, ?, ?, ?, ?)');
            // actividad_id: se asume que la actividad del juego es la propia (puede ser 0). Usamos 0.
            $stmt->execute([$juegoId, 0, $grado, $paralelo, $fechaActivacion]);
            setFlashMessage('success', 'Sesión activada correctamente.');
            header('Location: sesiones.php?juego_id=' . $juegoId);
            exit;
        } catch (PDOException $e) {
            $errores[] = 'Error al crear la sesión: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid px-4 py-4 <?= $theme ?>">
    <h2 class="mb-4"><i class="bi bi-calendar-check me-2"></i>Activar Juego Educativo</h2>
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="activar_juego.php?juego_id=<?= $juegoId ?>" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="col-md-4">
            <label class="form-label">Grado (ej. 3, 4, 5)</label>
            <input type="text" name="grado" class="form-control" value="<?= sanitize($grado) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Paralelo</label>
            <select name="paralelo" class="form-select" required>
                <option value="" <?= $paralelo === '' ? 'selected' : '' ?>>Seleccionar</option>
                <option value="A" <?= $paralelo === 'A' ? 'selected' : '' ?>>A</option>
                <option value="B" <?= $paralelo === 'B' ? 'selected' : '' ?>>B</option>
                <option value="C" <?= $paralelo === 'C' ? 'selected' : '' ?>>C</option>
                <option value="D" <?= $paralelo === 'D' ? 'selected' : '' ?>>D</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fecha y hora de activación</label>
            <input type="datetime-local" name="fecha_activacion" class="form-control" value="<?= sanitize($fechaActivacion) ?>" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Activar Sesión</button>
            <a href="juegos.php" class="btn btn-outline-secondary ms-2">Volver</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
