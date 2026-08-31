# Historias de Usuario y Casos de Uso

Este documento lista los requerimientos funcionales expresados mediante el marco de trabajo Scrum (Historias de Usuario) y descritos técnicamente (Casos de Uso).

## Historias de Usuario (Sprint Backlog - Fase MVP)

### Épica: Gestión de Cuentas
- **HU-01:** Como estudiante, quiero poder registrarme en la plataforma usando mi correo para acceder a mis materias.
- **HU-02:** Como usuario (cualquier rol), quiero iniciar sesión con mis credenciales para acceder al panel que me corresponde.
- **HU-03:** Como usuario, quiero poder cerrar mi sesión para mantener segura mi cuenta en dispositivos compartidos.

### Épica: Gestión de Materias (Docente)
- **HU-04:** Como docente, quiero ver las materias que tengo asignadas (Ciencias Naturales y Estudios Sociales) para gestionar sus recursos.
- **HU-05:** Como docente, quiero subir documentos y recursos a mi materia para que los estudiantes puedan descargarlos.

### Épica: Aprendizaje del Estudiante
- **HU-06:** Como estudiante, quiero visualizar las materias en las que estoy inscrito.
- **HU-07:** Como estudiante, quiero poder visualizar y descargar los recursos que el docente ha compartido en la materia.

## Casos de Uso (Casos Funcionales Principales)

### CU-01: Iniciar Sesión (Autenticación)
- **Actor:** Usuario (Estudiante, Docente, Administrador).
- **Precondición:** El usuario debe estar registrado en la base de datos.
- **Flujo Principal:**
  1. El usuario navega a `login.php`.
  2. Ingresa correo y contraseña.
  3. El sistema valida las credenciales y el Hash de la contraseña.
  4. El sistema inicia la variable de sesión `$_SESSION['usuario_id']` y el `rol`.
  5. El sistema redirige al usuario a su panel correspondiente (`/estudiante/`, `/docente/` o `/admin/`).
- **Excepciones:** Si los datos son incorrectos, se muestra un mensaje de error ("Credenciales inválidas").

### CU-02: Subida de Recursos
- **Actor:** Docente.
- **Precondición:** El usuario tiene sesión iniciada como docente.
- **Flujo Principal:**
  1. El docente ingresa al panel de su materia.
  2. Completa el formulario subiendo un archivo PDF, DOCX, etc.
  3. El sistema valida la extensión del archivo y el tamaño.
  4. El archivo es movido a `uploads/recursos/`.
  5. Se guarda un registro en la base de datos (tabla de recursos).
- **Excepciones:** Si el archivo es muy pesado o el formato no está permitido, se cancela la subida y se informa al docente.
