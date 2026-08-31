<?php
/**
 * Configuración - Panel Administrador
 */
$pageTitle = 'Configuración';
require_once __DIR__ . '/../includes/auth.php';
requireRole('administrador');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configs = $_POST['config'] ?? [];
    foreach ($configs as $clave => $valor) {
        $stmt = $db->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $stmt->execute([$valor, $clave]);
    }
    setFlashMessage('success', 'Configuración actualizada correctamente.');
    header('Location: ' . BASE_URL . '/admin/configuracion.php');
    exit;
}

$configuraciones = $db->query("SELECT * FROM configuracion ORDER BY id")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <h3 class="fw-bold mb-4"><i class="bi bi-gear me-2"></i>Configuración de la Plataforma</h3>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sliders me-2"></i>Parámetros Generales
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php foreach ($configuraciones as $config): ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <?php echo sanitize($config['descripcion']); ?>
                            </label>
                            <?php if ($config['clave'] === 'registro_habilitado'): ?>
                            <select name="config[<?php echo $config['clave']; ?>]" class="form-select">
                                <option value="1" <?php echo $config['valor'] == '1' ? 'selected' : ''; ?>>Habilitado</option>
                                <option value="0" <?php echo $config['valor'] == '0' ? 'selected' : ''; ?>>Deshabilitado</option>
                            </select>
                            <?php elseif (strpos($config['clave'], 'color') !== false): ?>
                            <div class="input-group">
                                <input type="color" name="config[<?php echo $config['clave']; ?>]" class="form-control form-control-color" 
                                       value="<?php echo sanitize($config['valor']); ?>">
                                <input type="text" class="form-control" value="<?php echo sanitize($config['valor']); ?>" readonly>
                            </div>
                            <?php elseif ($config['clave'] === 'descripcion_plataforma'): ?>
                            <textarea name="config[<?php echo $config['clave']; ?>]" class="form-control" rows="3"><?php echo sanitize($config['valor']); ?></textarea>
                            <?php else: ?>
                            <input type="text" name="config[<?php echo $config['clave']; ?>]" class="form-control" 
                                   value="<?php echo sanitize($config['valor']); ?>">
                            <?php endif; ?>
                            <small class="text-muted">Clave: <code><?php echo $config['clave']; ?></code></small>
                        </div>
                        <?php endforeach; ?>

                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
