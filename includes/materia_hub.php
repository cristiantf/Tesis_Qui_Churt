<?php
/**
 * Panel de materias con acciones (público o autenticado)
 *
 * Variables esperadas:
 * - $materiasHub: array de materias
 * - $hubPublic: bool (true = redirige a login)
 */
$hubPublic = $hubPublic ?? true;
?>
<div class="row g-4">
    <?php foreach ($materiasHub as $m):
        $themeClass = getMateriaThemeClass($m['nombre']);
    ?>
    <div class="col-lg-6">
        <div class="materia-hub-panel <?php echo $themeClass; ?>">
            <div class="materia-hub-banner">
                <img src="<?php echo getMateriaImageUrl($m['imagen']); ?>" alt="<?php echo sanitize($m['nombre']); ?>">
                <div class="materia-hub-banner-overlay">
                    <span class="materia-hub-chip">
                        <i class="<?php echo getMateriaIcon($m['nombre']); ?> me-1"></i>
                        <?php echo sanitize($m['nombre']); ?>
                    </span>
                    <p><?php echo sanitize($m['descripcion']); ?></p>
                </div>
            </div>

            <div class="materia-hub-body">
                <h6 class="materia-hub-actions-title">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Acciones de la materia
                </h6>
                <div class="materia-actions-grid">
                    <?php foreach (getMateriaAcciones() as $accion): ?>
                    <a href="<?php echo getMateriaActionHref($m['id'], $accion['id'], $hubPublic); ?>"
                       class="materia-action-btn <?php echo $accion['class']; ?>">
                        <i class="bi <?php echo $accion['icon']; ?>"></i>
                        <span><?php echo $accion['label']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if (!$hubPublic): ?>
                <div class="materia-hub-footer">
                    <a href="<?php echo BASE_URL; ?>/estudiante/materia.php?id=<?php echo $m['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar a la materia
                    </a>
                </div>
                <?php else: ?>
                <div class="materia-hub-footer text-muted small">
                    <i class="bi bi-info-circle me-1"></i>Selecciona una acción para iniciar sesión
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
