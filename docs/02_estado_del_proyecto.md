# Estado del Proyecto

## Situación Actual

La **Plataforma Educativa 10 de Agosto** se encuentra en su fase de **Producto Mínimo Viable (MVP)** funcional, estructurado utilizando PHP vanilla con una arquitectura sencilla y directa (monolítica).

El proyecto actualmente cubre las funcionalidades fundamentales de gestión educativa básica, enfocadas en las materias de Ciencias Naturales y Estudios Sociales.

### Módulos Implementados (Terminados)

1. **Gestión de Usuarios y Autenticación:**
   - Sistema de login con sesiones en PHP (`includes/auth.php`).
   - Registro de usuarios (`registro.php`).
   - Diferenciación clara entre los roles: `administrador`, `docente` y `estudiante`.

2. **Módulo Administrador (`/admin/`):**
   - Configuración inicial y despliegue de base de datos (`install_db.php`, `database/schema.sql`).
   - Gestión básica de la plataforma (posibles scripts de limpieza de BD).

3. **Módulo Docente (`/docente/`):**
   - Creación y carga de recursos educativos (`uploads/recursos/`).
   - Visualización de materias asignadas.

4. **Módulo Estudiante (`/estudiante/`):**
   - Panel de control para el alumno.
   - Acceso a las materias (Ciencias Naturales y Estudios Sociales).
   - Descarga o visualización de los recursos compartidos por los docentes.

### Módulos Pendientes (Siguientes Fases)

1. **Foros y Mensajería (Aprendizaje Colaborativo):**
   - Espacio para que los alumnos interactúen con el docente y debatan temas de las clases.
2. **Sistema de Evaluaciones y Calificaciones:**
   - Envío de tareas por parte de los estudiantes.
   - Herramienta para que el docente pueda calificar con rúbricas.
3. **Módulo de Gamificación:**
   - Sistema de puntos e insignias para fomentar la participación.

### Deuda Técnica / Posibles Mejoras

- **Arquitectura:** Actualmente la lógica de negocio y las vistas HTML están en gran parte acopladas. En futuros *sprints* se recomienda migrar a un patrón MVC (Modelo-Vista-Controlador) para mayor escalabilidad.
- **Seguridad:** Reforzar validaciones contra inyecciones SQL (asegurando el uso estricto de PDO en todos lados) y XSS.
- **Framework CSS:** Integrar un framework de diseño robusto para mejorar la interfaz responsiva de forma más ágil (ej. Bootstrap o TailwindCSS).
