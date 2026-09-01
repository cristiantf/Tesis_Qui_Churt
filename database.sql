-- =====================================================
-- PLATAFORMA WEB EDUCATIVA - METODOLOGÍAS ACTIVAS
-- Base de Datos: plataforma_educativa
-- =====================================================

CREATE DATABASE IF NOT EXISTS plataforma_educativa
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE plataforma_educativa;

-- =====================================================
-- TABLA: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'docente', 'estudiante') NOT NULL DEFAULT 'estudiante',
    grado TINYINT UNSIGNED DEFAULT NULL,
    paralelo ENUM('A', 'B', 'C', 'D') DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: materias
-- =====================================================
CREATE TABLE IF NOT EXISTS materias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255) DEFAULT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: materia_docente (asignación docente-materia)
-- =====================================================
CREATE TABLE IF NOT EXISTS materia_docente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    docente_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_materia_docente (materia_id, docente_id)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: docente_curso_paralelo
-- =====================================================
CREATE TABLE IF NOT EXISTS docente_curso_paralelo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    docente_id INT NOT NULL,
    grado TINYINT UNSIGNED NOT NULL,
    paralelo ENUM('A', 'B', 'C', 'D') NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_docente_curso_paralelo (docente_id, grado, paralelo)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: materia_estudiante (inscripción estudiante-materia)
-- =====================================================
CREATE TABLE IF NOT EXISTS materia_estudiante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_materia_estudiante (materia_id, estudiante_id)
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: actividades
-- =====================================================
CREATE TABLE IF NOT EXISTS actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    docente_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo_metodologia ENUM('abp', 'clase_invertida', 'gamificacion', 'colaborativo', 'estudio_caso', 'debate') NOT NULL DEFAULT 'abp',
    instrucciones TEXT,
    fecha_inicio DATE,
    fecha_limite DATE,
    puntaje_maximo DECIMAL(5,2) DEFAULT 100.00,
    estado ENUM('borrador', 'publicada', 'cerrada') NOT NULL DEFAULT 'borrador',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE actividades ADD FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE;
ALTER TABLE actividades ADD FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- =====================================================
-- TABLA: entregas
-- =====================================================
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    contenido TEXT,
    archivo VARCHAR(255) DEFAULT NULL,
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'entregada', 'calificada') NOT NULL DEFAULT 'entregada',
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: calificaciones
-- =====================================================
CREATE TABLE IF NOT EXISTS calificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entrega_id INT NOT NULL,
    docente_id INT NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    retroalimentacion TEXT,
    fecha_calificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entrega_id) REFERENCES entregas(id) ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: recursos
-- =====================================================
CREATE TABLE IF NOT EXISTS recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    docente_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    tipo ENUM('documento', 'video', 'imagen', 'enlace', 'presentacion', 'otro') NOT NULL DEFAULT 'documento',
    descargas INT DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: foro_temas
-- =====================================================
CREATE TABLE IF NOT EXISTS foro_temas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    contenido TEXT NOT NULL,
    fijado TINYINT(1) DEFAULT 0,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: foro_respuestas
-- =====================================================
CREATE TABLE IF NOT EXISTS foro_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tema_id INT NOT NULL,
    usuario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tema_id) REFERENCES foro_temas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLA: configuracion
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descripcion VARCHAR(255)
) ENGINE=InnoDB;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, apellido, email, password, rol, estado) VALUES
('Admin', 'Sistema', 'admin@plataforma.com', '$2y$10$SculkwL6CLs//Nq8pj0DtuJ/OSl.QtvHhE1QkS3Hvacy7FwkbMQnu', 'administrador', 1);

-- Docente de demostración (password: docente123)
INSERT INTO usuarios (nombre, apellido, email, password, rol, estado) VALUES
('María', 'González', 'docente@plataforma.com', '$2y$10$Zj0aSF4hkwiwLo64pWTO8uOr3tTvz/8EoQcN21OcYAgls1Je1Z8xC', 'docente', 1);

-- Estudiante de demostración (password: estudiante123)
INSERT INTO usuarios (nombre, apellido, email, password, rol, grado, paralelo, estado) VALUES
('Carlos', 'Ramírez', 'estudiante@plataforma.com', '$2y$10$2CLvh5CJeP7M3.4AT8Vz5uQ0ShPHYJgHSuEpkXMa20wSQJePT0PGG', 'estudiante', 8, 'A', 1);

-- Materias predeterminadas
INSERT INTO materias (nombre, descripcion, imagen) VALUES
('Ciencias Naturales', 'Estudio de los fenómenos naturales, la vida, la materia y la energía. Fomenta la observación, la experimentación y el pensamiento científico.', 'ciencias-naturales.jpg'),
('Estudios Sociales', 'Comprensión de la sociedad, la historia, la geografía y la cultura. Desarrolla el pensamiento crítico y la ciudadanía responsable.', 'estudios-sociales.jpg');

-- Configuración inicial
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('nombre_plataforma', 'Plataforma Educativa 10 de Agosto', 'Nombre de la plataforma'),
('descripcion_plataforma', 'Plataforma basada en metodologías activas para Ciencias Naturales y Estudios Sociales', 'Descripción de la plataforma'),
('registro_habilitado', '1', 'Permitir registro de nuevos usuarios'),
('color_primario', '#1a4b8c', 'Color primario de la plataforma'),
('color_secundario', '#e8833a', 'Color secundario de la plataforma');

-- Asignaciones de demostración (docente y estudiante en ambas materias)
INSERT IGNORE INTO materia_docente (materia_id, docente_id)
SELECT 1, id FROM usuarios WHERE email = 'docente@plataforma.com'
UNION SELECT 2, id FROM usuarios WHERE email = 'docente@plataforma.com';

INSERT IGNORE INTO materia_estudiante (materia_id, estudiante_id)
SELECT 1, id FROM usuarios WHERE email = 'estudiante@plataforma.com'
UNION SELECT 2, id FROM usuarios WHERE email = 'estudiante@plataforma.com';
