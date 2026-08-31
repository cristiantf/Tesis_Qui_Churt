<!-- Footer -->
<footer class="main-footer mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center mb-2">
                    <img src="<?php echo getLogoUrl(); ?>" alt="Logo" height="35" class="me-2">
                    <h6 class="mb-0 text-white fw-bold">Plataforma Educativa</h6>
                </div>
                <p class="text-white-50 small">Plataforma basada en metodologías activas para mejorar el proceso de enseñanza-aprendizaje.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white fw-bold mb-2"><i class="bi bi-book me-1"></i>Materias</h6>
                <ul class="list-unstyled small text-white-50">
                    <li><i class="bi bi-chevron-right me-1"></i>Ciencias Naturales</li>
                    <li><i class="bi bi-chevron-right me-1"></i>Estudios Sociales</li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white fw-bold mb-2"><i class="bi bi-lightbulb me-1"></i>Metodologías</h6>
                <ul class="list-unstyled small text-white-50">
                    <li><i class="bi bi-check2 me-1"></i>Aprendizaje Basado en Problemas</li>
                    <li><i class="bi bi-check2 me-1"></i>Clase Invertida</li>
                    <li><i class="bi bi-check2 me-1"></i>Aprendizaje Colaborativo</li>
                    <li><i class="bi bi-check2 me-1"></i>Gamificación</li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center">
            <p class="text-white-50 small mb-0">&copy; <?php echo date('Y'); ?> Plataforma Educativa 10 de Agosto. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
// Confirmar eliminación
function confirmarEliminar(url, nombre) {
    if (confirm('¿Está seguro de que desea eliminar "' + nombre + '"? Esta acción no se puede deshacer.')) {
        window.location.href = url;
    }
}

// Animación de entrada para cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Auto-cerrar alertas después de 5 segundos
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
</body>
</html>
