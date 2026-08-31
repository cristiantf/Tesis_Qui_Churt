# Plataforma Educativa 10 de Agosto

Plataforma web educativa basada en **metodologías activas** para las materias de **Ciencias Naturales** y **Estudios Sociales** de la Unidad Educativa Fiscomisional 10 de Agosto.

---

## Requisitos

| Componente | Versión mínima |
|------------|----------------|
| PHP        | 7.4+           |
| MySQL      | 5.7+ / MariaDB |
| Apache     | 2.4 (XAMPP)    |

---

## Instalación rápida (XAMPP)

1. **Copiar el proyecto** en `C:\xampp\htdocs\10 de agosto`
2. **Iniciar** Apache y MySQL desde el Panel de Control de XAMPP
3. **Instalar la base de datos** abriendo:
   ```
   http://localhost/10%20de%20agosto/install_db.php
   ```
4. **Acceder a la plataforma**:
   ```
   http://localhost/10%20de%20agosto/
   ```

---

## Enlaces de acceso

| Sección              | URL |
|----------------------|-----|
| **Inicio**           | http://localhost/10%20de%20agosto/ |
| **Iniciar sesión**   | http://localhost/10%20de%20agosto/login.php |
| **Registro**         | http://localhost/10%20de%20agosto/registro.php |
| **Panel Admin**      | http://localhost/10%20de%20agosto/admin/ |
| **Panel Docente**    | http://localhost/10%20de%20agosto/docente/ |
| **Panel Estudiante** | http://localhost/10%20de%20agosto/estudiante/ |

---

## Usuarios de demostración

| Rol          | Correo                      | Contraseña    |
|--------------|-----------------------------|---------------|
| Administrador| admin@plataforma.com        | admin123      |
| Docente      | docente@plataforma.com      | docente123    |
| Estudiante   | estudiante@plataforma.com   | estudiante123 |

---

## Estructura del proyecto

```
10 de agosto/
├── index.php              # Página de inicio pública
├── login.php              # Autenticación
├── registro.php           # Registro de usuarios
├── logout.php             # Cierre de sesión
├── install_db.php         # Instalador (acceso web)
│
├── config/
│   ├── app.php            # Configuración general y helpers de rutas
│   └── database.php       # Conexión PDO a MySQL
│
├── includes/
│   ├── auth.php           # Sesión, autenticación y helpers
│   ├── header.php         # Navbar y cabecera HTML
│   └── footer.php         # Pie de página
│
├── assets/
│   ├── css/styles.css     # Estilos globales
│   └── images/            # Logo e imágenes de materias
│
├── admin/                 # Módulo administrador
├── docente/               # Módulo docente
├── estudiante/            # Módulo estudiante
│
├── database/
│   └── schema.sql         # Esquema y datos iniciales
│
├── scripts/
│   └── install_db.php     # Lógica del instalador
│
└── uploads/
    └── recursos/          # Archivos subidos por docentes
```

---

## Configuración

Editar `config/database.php` si tu entorno difiere del predeterminado:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'plataforma_educativa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/10%20de%20agosto');
```

---

## Roles y permisos

| Rol            | Carpeta      | Funciones principales |
|----------------|--------------|------------------------|
| administrador  | `/admin/`    | Usuarios, materias, reportes, configuración |
| docente        | `/docente/`  | Actividades, recursos, calificaciones, foro |
| estudiante     | `/estudiante/` | Materias, tareas, calificaciones, recursos |

---

## Metodologías activas soportadas

- Aprendizaje Basado en Problemas (ABP)
- Clase Invertida
- Gamificación
- Aprendizaje Colaborativo
- Estudio de Caso
- Debate

---

## Licencia

Proyecto educativo — Unidad Educativa Fiscomisional 10 de Agosto.
