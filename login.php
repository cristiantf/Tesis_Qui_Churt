<?php
/**
 * Login - Inicio de Sesión
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard(getCurrentUser()['rol']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_apellido'] = $user['apellido'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['user_grado'] = $user['grado'] ?? null;
            $_SESSION['user_paralelo'] = $user['paralelo'] ?? null;
            
            // Actualizar último acceso
            $stmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            redirectToDashboard($user['rol']);
        } else {
            $error = 'Credenciales incorrectas o cuenta desactivada.';
        }
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'acceso_denegado') {
    $error = 'No tiene permisos para acceder a esa sección.';
}
if (isset($_GET['registro']) && $_GET['registro'] === 'exitoso') {
    $success = 'Registro exitoso. Inicie sesión con sus credenciales.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Plataforma Educativa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/Logo.png">
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="auth-card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <img src="<?php echo getLogoUrl(); ?>" alt="Logo institucional" height="70" class="mb-3 institution-logo">
                            <h4 class="fw-bold text-dark">U.E. 10 de Agosto</h4>
                            <p class="text-muted small">Inicia sesión en tu cuenta</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                            <i class="bi bi-exclamation-circle me-1"></i><?php echo $error; ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                            <i class="bi bi-check-circle me-1"></i><?php echo $success; ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php echo csrfInput(); ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-envelope me-1"></i>Correo Electrónico
                                </label>
                                <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com" 
                                       value="<?php echo sanitize($email ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">
                                    <i class="bi bi-lock me-1"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" 
                                           placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="text-muted small mb-0">¿No tienes cuenta? 
                                <a href="<?php echo BASE_URL; ?>/registro.php" class="fw-bold text-decoration-none" style="color: var(--primary);">Regístrate aquí</a>
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
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
    </script>
</body>
</html>
