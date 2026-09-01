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

<!-- html2pdf JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- Custom JS -->
<script>
// Función genérica para exportar un elemento a PDF
function exportarAPDF(elementoId, nombreArchivo) {
    const elemento = document.getElementById(elementoId);
    if (!elemento) return;
    
    // Clonar para no modificar la vista original
    const elementoClone = elemento.cloneNode(true);
    // Eliminar botones u elementos que no queremos imprimir (con clase no-print)
    const noPrint = elementoClone.querySelectorAll('.no-print, button');
    noPrint.forEach(el => el.remove());
    
    // Añadir algunos estilos básicos al clon para PDF
    elementoClone.style.padding = '20px';
    elementoClone.style.backgroundColor = 'white';
    
    const opt = {
        margin:       10,
        filename:     nombreArchivo + '.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(elementoClone).save();
}

// Función genérica para exportar texto plano
function exportarATXT(titulo, descripcion, instrucciones, nombreArchivo) {
    let contenido = titulo.toUpperCase() + "\n\n";
    contenido += "DESCRIPCIÓN:\n" + descripcion + "\n\n";
    if (instrucciones && instrucciones.trim() !== '') {
        contenido += "INSTRUCCIONES:\n" + instrucciones + "\n";
    }
    
    const blob = new Blob([contenido], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = nombreArchivo + '.txt';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

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
