# Plataforma Web Educativa Basada en Metodologías Activas para Mejorar el Proceso de Enseñanza-Aprendizaje

**Plataforma web educativa basada en metodologías activas para mejorar el proceso de enseñanza-aprendizaje de Ciencias Naturales y Estudios Sociales en los estudiantes de la unidad educativa fiscomisional 10 de agosto de básica media mediante el lenguaje de programación PHP.**

---

## Planteamiento del Problema de investigación
En la Unidad Educativa Fiscomisional 10 de Agosto se ha evidenciado una baja participación activa de los estudiantes en las áreas de Ciencias Naturales y Estudios Sociales, lo que repercute negativamente en la comprensión significativa de los contenidos y en el desarrollo de habilidades críticas. A pesar de los esfuerzos de los docentes, las metodologías tradicionales centradas en la transmisión de información han limitado el involucramiento del estudiante como protagonista de su propio aprendizaje.

Diversos estudios y experiencias pedagógicas han demostrado que el uso de metodologías activas, como el aprendizaje basado en proyectos, el trabajo colaborativo y el uso de recursos digitales interactivos, favorecen la motivación, la autonomía y el pensamiento crítico. Sin embargo, en el contexto de esta institución, el acceso a plataformas tecnológicas adaptadas a estas metodologías es limitado o inexistente, lo que genera una brecha entre las demandas educativas actuales y las herramientas disponibles.

Por tanto, el problema radica en la ausencia de una plataforma web interactiva que articule metodologías activas y recursos digitales contextualizados, capaces de optimizar el aprendizaje en Ciencias Naturales y Estudios Sociales. La implementación de dicha plataforma no solo responde a una necesidad institucional, sino que se alinea con los objetivos de calidad educativa, tecnológica y mejora del rendimiento académico. 

## Objetivos
### Objetivo General
Implementar una plataforma web interactiva basada en metodologías activas y recursos digitales, con el fin de optimizar el aprendizaje de Ciencias Naturales y Estudios Sociales en los estudiantes de la Unidad Educativa Fiscomisional 10 de agosto, promoviendo la participación, el pensamiento crítico y el uso significativo de la tecnología en el aula.

### Objetivos Específicos
- Analizar las necesidades del proceso de enseñanza-aprendizaje de Ciencias Naturales y Estudios Sociales en los estudiantes de Básica Media.
- Diseñar una plataforma web educativa basada en metodologías activas para fortalecer el aprendizaje de Ciencias Naturales y Estudios Sociales.
- Implementar la plataforma web educativa utilizando PHP para mejorar el proceso de enseñanza-aprendizaje. 

## Justificación
El aprendizaje de Ciencias Naturales y Estudios Sociales enfrenta desafíos significativos en contextos educativos tradicionales, donde la enseñanza suele ser pasiva y poco contextualizada. En la Unidad Educativa Fiscomisional 10 de Agosto, se ha identificado la necesidad de transformar las prácticas pedagógicas mediante el uso de metodologías activas y recursos digitales propuesto por (Valdivia, 2016) que promueve la participación, el pensamiento crítico y el aprendizaje significativo.
Diseñar e implementar una plataforma web interactiva responde a esta necesidad, ofreciendo un entorno dinámico que integra contenidos curriculares con herramientas tecnológicas accesibles y atractivas para los estudiantes. (Valdivia, 2016) propone busca y optimizar el proceso de enseñanza-aprendizaje, adaptándose a los estilos cognitivos de los alumnos y fomentando la autonomía en el estudio.
Los beneficios esperados incluyen una mejora en el rendimiento académico, mayor motivación estudiantil y el desarrollo de competencias digitales. Según (Antonio, 2019) El proyecto se llevará a cabo mediante la colaboración entre docentes y estudiantes, utilizando recursos como software libre, dispositivos disponibles en la institución y conectividad básica. Esta iniciativa no solo responde a las demandas actuales de la educación, sino que también prepara a los estudiantes para enfrentar los retos del siglo XXI con una formación integral y contextualizada.

## Beneficios Esperados
Para los estudiantes se espera mejorar el aprendizaje promoviendo una comprensión más profunda de los contenidos mediante el uso de metodologías activas como el aprendizaje basado en proyectos, el aprendizaje colaborativo y el pensamiento crítico. También puede Facilitar la conexión entre teoría y práctica a través de simulaciones, videos interactivos y actividades digitales. Fomenta el desarrollo de competencias digitales tanto en estudiantes como en docentes.

---

## Requisitos

| Componente | Versión mínima |
|------------|----------------|
| PHP        | 7.4+           |
| MySQL      | 5.7+ / MariaDB |
| Apache     | 2.4 (XAMPP)    |

---

## Instalación rápida (XAMPP)

1. **Copiar el proyecto** en `C:\xampp\htdocs\10_DE_AGOSTO`
2. **Iniciar** Apache y MySQL desde el Panel de Control de XAMPP
3. **Instalar la base de datos** abriendo:
   ```
   php scripts/install_db.php
   ```
4. **Acceder a la plataforma**:
   ```
   http://localhost/10_DE_AGOSTO/
   ```

---

## Enlaces de acceso

| Sección              | URL |
|----------------------|-----|
| **Inicio**           | http://localhost/10_DE_AGOSTO/ |
| **Iniciar sesión**   | http://localhost/10_DE_AGOSTO/login.php |
| **Registro**         | http://localhost/10_DE_AGOSTO/registro.php |
| **Panel Admin**      | http://localhost/10_DE_AGOSTO/admin/ |
| **Panel Docente**    | http://localhost/10_DE_AGOSTO/docente/ |
| **Panel Estudiante** | http://localhost/10_DE_AGOSTO/estudiante/ |

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
define('BASE_URL', '/10_DE_AGOSTO');
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

## Nuevas Funcionalidades Integradas (Fase 2)
- **Generación de Actividades con Inteligencia Artificial**: Integración de la API de Google Gemini (3.6 Flash) para sugerir títulos, descripciones e instrucciones estructuradas para las actividades basadas en metodologías activas.
- **Exportación de Documentos**: Los docentes pueden exportar actividades y recursos generados en formatos PDF (vía html2pdf.js) y TXT (Texto plano).
- **Gestión Avanzada de Recursos**: Operaciones CRUD completas que permiten editar y eliminar recursos digitales y enlaces web de manera dinámica.
- **Foros Colaborativos Activos**: Los docentes tienen control total (CRUD) sobre los temas de foro que crean, permitiendo editar contenidos o eliminar temas (y sus respuestas) para moderar el entorno virtual.

---

## Licencia

Proyecto educativo — Unidad Educativa Fiscomisional 10 de Agosto.
