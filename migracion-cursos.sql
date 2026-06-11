-- ============================================================================
-- Migración: gestión de cursos + adjuntos + asignaciones
-- Academia JOSEPAN 360. Idempotente y SIN tocar el contenido de los cursos.
-- Aplica sobre una instalación existente:
--   mysql -u TU_USUARIO -p josepan_academy < migracion-cursos.sql
-- (MariaDB 10.3+. En MySQL 8, si la columna ya existe, ignora el error del ALTER.)
-- ============================================================================

SET NAMES utf8mb4;

-- 1) Visibilidad del curso (todos / solo asignados)
ALTER TABLE cursos
  ADD COLUMN IF NOT EXISTS visibilidad ENUM('todos','asignados')
  NOT NULL DEFAULT 'todos' AFTER obligatorio;

-- 2) Adjuntos por sesión
CREATE TABLE IF NOT EXISTS cursos_adjuntos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id    VARCHAR(80)     NOT NULL,
    sesion_num  INT UNSIGNED    NOT NULL,
    nombre      VARCHAR(200)    NOT NULL,
    archivo     VARCHAR(255)    NOT NULL,
    mime        VARCHAR(120)    NULL,
    tamano      INT UNSIGNED    NOT NULL DEFAULT 0,
    subido_por  INT UNSIGNED    NULL,
    subido_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adj_curso_sesion (curso_id, sesion_num),
    CONSTRAINT fk_adj_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Asignaciones curso <-> empleado
CREATE TABLE IF NOT EXISTS curso_asignaciones (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id      VARCHAR(80)     NOT NULL,
    empleado_id   INT UNSIGNED    NOT NULL,
    asignado_por  INT UNSIGNED    NULL,
    asignado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_asignacion (curso_id, empleado_id),
    KEY idx_asig_emp (empleado_id),
    CONSTRAINT fk_asig_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asig_emp FOREIGN KEY (empleado_id)
        REFERENCES empleados (empleado_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
