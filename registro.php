<?php
/**
 * Registro de nuevos usuarios
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard(getCurrentUser()['rol']);
}

$error = '';
$formData = ['nombre' => '', 'apellido' => '', 'email' => '', 'rol' => 'estudiante', 'grado' => '', 'paralelo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['apellido'] = trim($_POST['apellido'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['rol'] = $_POST['rol'] ?? 'estudiante';
    $formData['grado'] = $_POST['grado'] ?? '';
    $formData['paralelo'] = $_POST['paralelo'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($formData['nombre']) || empty($formData['apellido']) || empty($formData['email']) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!in_array($formData['rol'], ['docente', 'estudiante'])) {
        $error = 'Rol no válido.';
    } elseif ($formData['rol'] === 'estudiante' && (empty($formData['grado']) || empty($formData['paralelo']))) {
        $error = 'Selecciona tu grado y paralelo.';
    } elseif ($formData['rol'] === 'estudiante' && !in_array(intval($formData['grado']), GRADOS, true)) {
        $error = 'El grado seleccionado no es válido.';
    } elseif ($formData['rol'] === 'estudiante' && !in_array(strtoupper($formData['paralelo']), PARALELOS, true)) {
        $error = 'El paralelo seleccionado no es válido.';
    } else {
        $db = getDB();
        
        // Verificar si ya existe el email
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $error = 'Este correo electrónico ya está registrado.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $grado = $formData['rol'] === 'estudiante' ? intval($formData['grado']) : null;
            $paralelo = $formData['rol'] === 'estudiante' ? strtoupper($formData['paralelo']) : null;
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, password, rol, grado, paralelo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$formData['nombre'], $formData['apellido'], $formData['email'], $hashedPassword, $formData['rol'], $grado, $paralelo]);
            
            // Si es estudiante, inscribirlo automáticamente en las 2 materias
            if ($formData['rol'] === 'estudiante') {
                $studentId = $db->lastInsertId();
                $materias = $db->query("SELECT id FROM materias")->fetchAll();
                foreach ($materias as $materia) {
                    $stmt = $db->prepare("INSERT IGNORE INTO materia_estudiante (materia_id, estudiante_id) VALUES (?, ?)");
                    $stmt->execute([$materia['id'], $studentId]);
                }
            }
            
            header('Location: ' . BASE_URL . '/login.php?registro=exitoso');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Plataforma Educativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="<?php echo getLogoUrl(); ?>" alt="Logo institucional" height="65" class="mb-3 institution-logo">
                            <h4 class="fw-bold text-dark">U.E. 10 de Agosto</h4>
                            <p class="text-muted small">Únete a la plataforma educativa</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?php echo $error; ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">
                                        <i class="bi bi-person me-1"></i>Nombre
                                    </label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Juan" 
                                           value="<?php echo sanitize($formData['nombre']); ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">
                                        <i class="bi bi-person me-1"></i>Apellido
                                    </label>
                                    <input type="text" name="apellido" class="form-control" placeholder="Pérez" 
                                           value="<?php echo sanitize($formData['apellido']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-envelope me-1"></i>Correo Electrónico
                                </label>
                                <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" 
                                       value="<?php echo sanitize($formData['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-person-badge me-1"></i>Rol
                                </label>
                                <select name="rol" id="rolSelect" class="form-select" style="border-radius: 8px; padding: 0.75rem 1rem; border: 2px solid var(--gray-200);">
                                    <option value="estudiante" <?php echo $formData['rol'] === 'estudiante' ? 'selected' : ''; ?>>
                                        Estudiante (5° a 10° grado)
                                    </option>
                                    <option value="docente" <?php echo $formData['rol'] === 'docente' ? 'selected' : ''; ?>>
                                        Docente
                                    </option>
                                </select>
                            </div>

                            <div id="estudianteFields" class="row g-3 mb-3" style="<?php echo $formData['rol'] === 'estudiante' ? '' : 'display:none;'; ?>">
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">
                                        <i class="bi bi-mortarboard me-1"></i>Grado
                                    </label>
                                    <select name="grado" class="form-select" style="border-radius: 8px; padding: 0.75rem 1rem; border: 2px solid var(--gray-200);">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach (GRADOS as $g): ?>
                                        <option value="<?php echo $g; ?>" <?php echo $formData['grado'] == $g ? 'selected' : ''; ?>>
                                            <?php echo $g; ?>° grado
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">
                                        <i class="bi bi-people me-1"></i>Paralelo
                                    </label>
                                    <select name="paralelo" class="form-select" style="border-radius: 8px; padding: 0.75rem 1rem; border: 2px solid var(--gray-200);">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach (PARALELOS as $p): ?>
                                        <option value="<?php echo $p; ?>" <?php echo $formData['paralelo'] === $p ? 'selected' : ''; ?>>
                                            Paralelo <?php echo $p; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-lock me-1"></i>Contraseña
                                </label>
                                <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-lock-fill me-1"></i>Confirmar Contraseña
                                </label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Repetir contraseña" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-person-plus me-2"></i>Registrarse
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="text-muted small mb-0">¿Ya tienes cuenta? 
                                <a href="<?php echo BASE_URL; ?>/login.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Inicia sesión</a>
                            </p>
                        </div>

                        <hr class="my-3">
                        <div class="text-center">
                            <a href="<?php echo BASE_URL; ?>/" class="text-muted small text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i>Volver al inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('rolSelect').addEventListener('change', function() {
        document.getElementById('estudianteFields').style.display = this.value === 'estudiante' ? '' : 'none';
    });
    </script>
</body>
</html>
