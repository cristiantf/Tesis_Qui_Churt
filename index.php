<?php
/**
 * Punto de entrada — Plataforma Educativa 10 de Agosto
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard(getCurrentUser()['rol']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_DESCRIPTION; ?>">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?php echo assetUrl('css/styles.css'); ?>" rel="stylesheet">
    <style>
        /* =====================================================
           LANDING PAGE - ESTILOS ESPECÍFICOS
           ===================================================== */

        /* Fondo animado */
        .landing-bg {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2454 0%, #1a4b8c 40%, #1e5fa0 70%, #c96a25 100%);
            position: relative;
            overflow: hidden;
        }

        .landing-bg::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232,131,58,0.15) 0%, transparent 70%);
            animation: float-slow 8s ease-in-out infinite;
        }

        .landing-bg::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(45,107,196,0.2) 0%, transparent 70%);
            animation: float-slow 10s ease-in-out infinite reverse;
        }

        @keyframes float-slow {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        /* Partículas decorativas */
        .particles {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            animation: particle-float linear infinite;
        }

        @keyframes particle-float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(720deg); opacity: 0; }
        }

        /* Header institucional */
        .inst-header {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 3rem 1.5rem 2rem;
        }

        .inst-logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            filter: drop-shadow(0 8px 24px rgba(0,0,0,0.4));
            animation: logo-entrance 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes logo-entrance {
            0% { transform: scale(0) rotate(-10deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .inst-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            text-shadow: 0 2px 20px rgba(0,0,0,0.3);
            margin-bottom: 0.5rem;
        }

        .inst-subtitle {
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
        }

        .inst-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 0.4rem 1.2rem;
            color: rgba(255,255,255,0.9);
            font-size: 0.82rem;
            font-weight: 500;
            margin-top: 0.75rem;
        }

        /* Sección de selección */
        .select-section {
            position: relative;
            z-index: 2;
            padding: 0 1.5rem 2rem;
        }

        .select-label {
            text-align: center;
            margin-bottom: 2rem;
        }

        .select-label h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 0.5rem;
        }

        .select-label h3 {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
        }

        /* ---- TARJETAS PRINCIPALES DE ASIGNATURA ---- */
        .subject-mega-card {
            border-radius: 24px;
            overflow: hidden;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            animation: card-entrance 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            opacity: 0;
        }

        .subject-mega-card:nth-child(1) { animation-delay: 0.2s; }
        .subject-mega-card:nth-child(2) { animation-delay: 0.4s; }

        @keyframes card-entrance {
            0% { transform: translateY(40px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .subject-mega-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 30px 80px rgba(0,0,0,0.45);
        }

        /* Imagen de la tarjeta */
        .subject-img-wrapper {
            position: relative;
            height: 240px;
            overflow: hidden;
        }

        .subject-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .subject-mega-card:hover .subject-img-wrapper img {
            transform: scale(1.08);
        }

        .subject-img-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1.5rem;
            background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 60%);
        }

        .subject-icon-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 0.75rem;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .subject-cn .subject-icon-badge { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .subject-es .subject-icon-badge { background: linear-gradient(135deg, #1a4b8c, #2d6bc4); }

        .subject-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 2px 8px rgba(0,0,0,0.4);
            line-height: 1.2;
        }

        .subject-desc {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.85);
            margin-top: 0.3rem;
        }

        /* Body de la tarjeta con botones */
        .subject-card-body {
            padding: 1.5rem;
        }

        /* Mensaje "Clic para ver opciones" */
        .click-hint {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: rgba(255,255,255,0.55);
            font-size: 0.82rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }

        .click-hint i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }

        .subject-mega-card.expanded .click-hint {
            color: rgba(255,255,255,0.8);
        }

        .subject-mega-card.expanded .click-hint i {
            transform: rotate(180deg);
        }

        /* Panel de acciones (oculto por defecto, se expande) */
        .actions-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
            opacity: 0;
        }

        .subject-mega-card.expanded .actions-panel {
            max-height: 600px;
            opacity: 1;
        }

        /* Separador de categorías */
        .action-category-label {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            margin-bottom: 0.75rem;
            margin-top: 0.25rem;
        }

        /* ---- BOTONES DE ACCIÓN ---- */
        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.85rem 1.1rem;
            border: none;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            margin-bottom: 0.5rem;
            color: #fff !important;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0);
            transition: background 0.25s ease;
        }

        .action-btn:hover::before {
            background: rgba(255,255,255,0.12);
        }

        .action-btn:hover {
            transform: translateX(5px) scale(1.01);
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
        }

        .action-btn:active {
            transform: translateX(3px) scale(0.99);
        }

        .action-btn .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            background: rgba(255,255,255,0.2);
            flex-shrink: 0;
        }

        .action-btn .btn-text {
            text-align: left;
            flex-grow: 1;
        }

        .action-btn .btn-text span {
            display: block;
            font-weight: 700;
            font-size: 0.88rem;
        }

        .action-btn .btn-text small {
            font-size: 0.72rem;
            opacity: 0.8;
            font-weight: 400;
        }

        .action-btn .btn-arrow {
            font-size: 0.9rem;
            opacity: 0.6;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .action-btn:hover .btn-arrow {
            transform: translateX(4px);
            opacity: 1;
        }

        /* Colores de botones */
        .btn-act-create   { background: linear-gradient(135deg, #1a6634, #27ae60); }
        .btn-act-quiz     { background: linear-gradient(135deg, #7b1fa2, #ab47bc); }
        .btn-act-video    { background: linear-gradient(135deg, #c62828, #ef5350); }
        .btn-act-edit     { background: linear-gradient(135deg, #e65100, #e8833a); }
        .btn-act-save     { background: linear-gradient(135deg, #0f3260, #1a4b8c); }
        .btn-act-delete   { background: linear-gradient(135deg, #7f1d1d, #dc2626); }

        /* Divisor entre grupos */
        .actions-divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 1rem 0;
        }

        /* Acceso rápido — ir al login */
        .login-quick-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .login-quick-card h4 {
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .login-quick-card p {
            color: rgba(255,255,255,0.65);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .btn-login-main {
            background: linear-gradient(135deg, #e8833a, #f0a36a);
            border: none;
            border-radius: 50px;
            padding: 0.85rem 2.5rem;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(232,131,58,0.4);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .btn-login-main:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 36px rgba(232,131,58,0.5);
            color: #fff;
        }

        .btn-register-link {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 1rem;
        }

        .btn-register-link:hover {
            color: rgba(255,255,255,0.95);
        }

        /* Footer info */
        .landing-footer {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 1.5rem;
            color: rgba(255,255,255,0.35);
            font-size: 0.78rem;
        }

        /* Responsive */
        @media (max-width: 767px) {
            .inst-title { font-size: 1.4rem; }
            .subject-img-wrapper { height: 180px; }
            .select-label h3 { font-size: 1.5rem; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#0f2454;">

<div class="landing-bg">

    <!-- Partículas decorativas -->
    <div class="particles" id="particles-container"></div>

    <!-- Header Institucional -->
    <div class="inst-header">
        <img src="<?php echo getLogoUrl(); ?>" alt="Logo institucional" class="inst-logo mb-3">
        <h1 class="inst-title">Unidad Educativa Fiscomisional<br>10 de Agosto</h1>
        <p class="inst-subtitle">Plataforma de Metodologías Activas</p>
        <div class="inst-badge">
            <i class="bi bi-mortarboard-fill"></i>
            5° a 10° Grado &middot; Paralelos A, B, C y D &middot; San Lorenzo
        </div>
    </div>

    <!-- Selección de Asignatura -->
    <div class="select-section">
        <div class="container" style="max-width: 920px;">

            <div class="select-label">
                <h2>Elige tu asignatura</h2>
                <h3>¿Qué quieres aprender hoy?</h3>
            </div>

            <!-- Fila de tarjetas -->
            <div class="row g-4 mb-4">

                <!-- ===== CIENCIAS NATURALES ===== -->
                <div class="col-md-6">
                    <div class="subject-mega-card subject-cn" id="card-cn" onclick="toggleCard('card-cn')">
                        <!-- Imagen con overlay -->
                        <div class="subject-img-wrapper">
                            <img src="<?php echo BASE_URL; ?>/Ciencias Naturales.jpg" 
                                 alt="Ciencias Naturales"
                                 onerror="this.src='https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=600&auto=format'">
                            <div class="subject-img-overlay">
                                <div class="subject-icon-badge">
                                    <i class="bi bi-flower1"></i>
                                </div>
                                <div class="subject-name">Ciencias Naturales</div>
                                <div class="subject-desc">Explora la vida, la Tierra y el universo</div>
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="subject-card-body">
                            <div class="click-hint">
                                <i class="bi bi-chevron-down"></i>
                                <span>Toca para ver las opciones</span>
                            </div>

                            <!-- Panel colapsable de acciones -->
                            <div class="actions-panel">

                                <!-- Crear contenido -->
                                <div class="action-category-label">Crear contenido</div>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=actividad" class="action-btn btn-act-create">
                                    <div class="btn-icon"><i class="bi bi-journal-plus"></i></div>
                                    <div class="btn-text">
                                        <span>Crear Actividad</span>
                                        <small>Tareas, proyectos y ejercicios</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=quiz" class="action-btn btn-act-quiz">
                                    <div class="btn-icon"><i class="bi bi-patch-question-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Crear Quiz</span>
                                        <small>Evaluaciones y cuestionarios</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=video" class="action-btn btn-act-video">
                                    <div class="btn-icon"><i class="bi bi-camera-video-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Subir Video</span>
                                        <small>Material audiovisual educativo</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>

                                <div class="actions-divider"></div>

                                <!-- Gestionar -->
                                <div class="action-category-label">Gestionar contenido</div>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=editar" class="action-btn btn-act-edit">
                                    <div class="btn-icon"><i class="bi bi-pencil-square"></i></div>
                                    <div class="btn-text">
                                        <span>Editar</span>
                                        <small>Modificar actividades existentes</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=guardar" class="action-btn btn-act-save">
                                    <div class="btn-icon"><i class="bi bi-floppy-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Guardar</span>
                                        <small>Guardar cambios realizados</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=ciencias&accion=eliminar" class="action-btn btn-act-delete">
                                    <div class="btn-icon"><i class="bi bi-trash3-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Eliminar</span>
                                        <small>Borrar actividades o recursos</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>

                            </div><!-- /actions-panel -->
                        </div><!-- /subject-card-body -->
                    </div><!-- /subject-mega-card -->
                </div>

                <!-- ===== ESTUDIOS SOCIALES ===== -->
                <div class="col-md-6">
                    <div class="subject-mega-card subject-es" id="card-es" onclick="toggleCard('card-es')">
                        <!-- Imagen con overlay -->
                        <div class="subject-img-wrapper">
                            <img src="<?php echo BASE_URL; ?>/Estudios Sociales.jpg" 
                                 alt="Estudios Sociales"
                                 onerror="this.src='https://images.unsplash.com/photo-1526470498-9ae73c665de8?w=600&auto=format'">
                            <div class="subject-img-overlay">
                                <div class="subject-icon-badge">
                                    <i class="bi bi-globe-americas"></i>
                                </div>
                                <div class="subject-name">Estudios Sociales</div>
                                <div class="subject-desc">Historia, culturas y sociedades del mundo</div>
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="subject-card-body">
                            <div class="click-hint">
                                <i class="bi bi-chevron-down"></i>
                                <span>Toca para ver las opciones</span>
                            </div>

                            <!-- Panel colapsable de acciones -->
                            <div class="actions-panel">

                                <!-- Crear contenido -->
                                <div class="action-category-label">Crear contenido</div>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=actividad" class="action-btn btn-act-create">
                                    <div class="btn-icon"><i class="bi bi-journal-plus"></i></div>
                                    <div class="btn-text">
                                        <span>Crear Actividad</span>
                                        <small>Tareas, proyectos y ejercicios</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=quiz" class="action-btn btn-act-quiz">
                                    <div class="btn-icon"><i class="bi bi-patch-question-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Crear Quiz</span>
                                        <small>Evaluaciones y cuestionarios</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=video" class="action-btn btn-act-video">
                                    <div class="btn-icon"><i class="bi bi-camera-video-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Subir Video</span>
                                        <small>Material audiovisual educativo</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>

                                <div class="actions-divider"></div>

                                <!-- Gestionar -->
                                <div class="action-category-label">Gestionar contenido</div>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=editar" class="action-btn btn-act-edit">
                                    <div class="btn-icon"><i class="bi bi-pencil-square"></i></div>
                                    <div class="btn-text">
                                        <span>Editar</span>
                                        <small>Modificar actividades existentes</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=guardar" class="action-btn btn-act-save">
                                    <div class="btn-icon"><i class="bi bi-floppy-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Guardar</span>
                                        <small>Guardar cambios realizados</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/login.php?materia=sociales&accion=eliminar" class="action-btn btn-act-delete">
                                    <div class="btn-icon"><i class="bi bi-trash3-fill"></i></div>
                                    <div class="btn-text">
                                        <span>Eliminar</span>
                                        <small>Borrar actividades o recursos</small>
                                    </div>
                                    <i class="bi bi-chevron-right btn-arrow"></i>
                                </a>

                            </div><!-- /actions-panel -->
                        </div>
                    </div>
                </div>

            </div><!-- /row -->

            <!-- Acceso rápido al login -->
            <div class="login-quick-card mb-4">
                <h4><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Accede a tu cuenta</h4>
                <p>Inicia sesión para ver tus calificaciones, actividades y recursos completos.</p>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn-login-main">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Iniciar Sesión
                </a>
                <br>
                <a href="<?php echo BASE_URL; ?>/registro.php" class="btn-register-link">
                    <i class="bi bi-person-plus"></i>
                    ¿No tienes cuenta? Regístrate aquí
                </a>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <div class="landing-footer">
        San Lorenzo &middot; Esmeraldas &middot; Ecuador &nbsp;|&nbsp; <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
    </div>

</div><!-- /landing-bg -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ===================================================
   FUNCIONALIDAD TARJETAS EXPANDIBLES
   =================================================== */
function toggleCard(cardId) {
    const card = document.getElementById(cardId);
    const allCards = document.querySelectorAll('.subject-mega-card');

    // Si el card ya está expandido, solo cerrarlo
    if (card.classList.contains('expanded')) {
        card.classList.remove('expanded');
        return;
    }

    // Cerrar todos los otros
    allCards.forEach(c => c.classList.remove('expanded'));

    // Abrir este
    card.classList.add('expanded');

    // Scroll suave hacia la tarjeta en móvil
    setTimeout(() => {
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

// Evitar que los links dentro de la tarjeta disparen el toggle
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

/* ===================================================
   PARTÍCULAS FLOTANTES
   =================================================== */
(function createParticles() {
    const container = document.getElementById('particles-container');
    const count = 25;

    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random() * 4 + 2;
        p.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${Math.random() * 100}%;
            animation-duration: ${Math.random() * 15 + 10}s;
            animation-delay: ${Math.random() * 10}s;
            opacity: ${Math.random() * 0.5 + 0.1};
        `;
        container.appendChild(p);
    }
})();
</script>
</body>
</html>
