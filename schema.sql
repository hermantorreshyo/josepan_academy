-- ============================================================================
-- JOSEPAN 360 · Academia Interna — Esquema de base de datos LOCAL (MySQL/MariaDB)
-- Codificación utf8mb4 · Motor InnoDB. La autenticación es externa (OMNI [1001]);
-- aquí solo vive la lógica de la academia. empleado_id = ID de empleado de OMNI.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --- empleados: cache del perfil devuelto por OMNI --------------------------
CREATE TABLE IF NOT EXISTS empleados (
    empleado_id     INT UNSIGNED   NOT NULL,
    nombre          VARCHAR(150)   NOT NULL,
    rol             VARCHAR(100)   NOT NULL DEFAULT 'Sin asignar',
    tienda          VARCHAR(120)   NOT NULL DEFAULT 'Sin asignar',
    email           VARCHAR(150)   NULL,
    es_admin        TINYINT(1)     NOT NULL DEFAULT 0,
    primer_acceso   DATETIME       NULL,
    ultimo_acceso   DATETIME       NULL,
    PRIMARY KEY (empleado_id),
    KEY idx_emp_rol (rol),
    KEY idx_emp_tienda (tienda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- cursos: catálogo maestro (poblado por el seeder) -----------------------
CREATE TABLE IF NOT EXISTS cursos (
    curso_id    VARCHAR(80)   NOT NULL,
    titulo      VARCHAR(200)  NOT NULL,
    resumen     TEXT          NULL,
    categoria   VARCHAR(120)  NULL,
    horas       INT UNSIGNED  NOT NULL DEFAULT 0,
    obligatorio TINYINT(1)    NOT NULL DEFAULT 0,
    visibilidad ENUM('todos','asignados') NOT NULL DEFAULT 'todos',
    orden       INT           NOT NULL DEFAULT 0,
    creado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (curso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- cursos_sesiones: sesiones de cada curso (poblado por el seeder) --------
CREATE TABLE IF NOT EXISTS cursos_sesiones (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id    VARCHAR(80)     NOT NULL,
    sesion_num  INT UNSIGNED    NOT NULL,
    titulo      VARCHAR(200)    NOT NULL,
    subtitulo   VARCHAR(255)    NULL,
    video       VARCHAR(255)    NULL,
    guia        MEDIUMTEXT      NULL,
    materiales  TEXT            NULL,            -- JSON: ["MN-RRHH-001", ...]
    PRIMARY KEY (id),
    UNIQUE KEY uq_sesion (curso_id, sesion_num),
    KEY idx_ses_curso (curso_id),
    CONSTRAINT fk_ses_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- cursos_adjuntos: archivos adjuntos por sesión -------------------------
CREATE TABLE IF NOT EXISTS cursos_adjuntos (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    curso_id    VARCHAR(80)     NOT NULL,
    sesion_num  INT UNSIGNED    NOT NULL,
    nombre      VARCHAR(200)    NOT NULL,            -- nombre visible
    archivo     VARCHAR(255)    NOT NULL,            -- nombre físico en downloads/adjuntos
    mime        VARCHAR(120)    NULL,
    tamano      INT UNSIGNED    NOT NULL DEFAULT 0,
    subido_por  INT UNSIGNED    NULL,
    subido_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adj_curso_sesion (curso_id, sesion_num),
    CONSTRAINT fk_adj_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- curso_asignaciones: qué empleados pueden ver qué cursos ---------------
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

-- --- niveles: escala de gamificación (poblado por el seeder) ----------------
CREATE TABLE IF NOT EXISTS niveles (
    nivel       TINYINT UNSIGNED NOT NULL,
    nombre      VARCHAR(80)      NOT NULL,
    min_puntos  INT UNSIGNED     NOT NULL,
    PRIMARY KEY (nivel),
    KEY idx_niv_min (min_puntos)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- parametros: parametrización por defecto (poblado por el seeder) --------
CREATE TABLE IF NOT EXISTS parametros (
    clave       VARCHAR(60)   NOT NULL,
    valor       VARCHAR(255)  NOT NULL,
    descripcion VARCHAR(255)  NULL,
    PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- usuarios_progreso: fila por (empleado, curso, sesión) ------------------
CREATE TABLE IF NOT EXISTS usuarios_progreso (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id     INT UNSIGNED    NOT NULL,
    curso_id        VARCHAR(80)     NOT NULL,
    sesion_id       INT UNSIGNED    NOT NULL,
    estado          ENUM('en_progreso','completado') NOT NULL DEFAULT 'en_progreso',
    completado_en   DATETIME        NULL,
    creado_en       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_progreso (empleado_id, curso_id, sesion_id),
    KEY idx_prog_emp (empleado_id),
    KEY idx_prog_curso (curso_id),
    CONSTRAINT fk_prog_emp FOREIGN KEY (empleado_id)
        REFERENCES empleados (empleado_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prog_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- asistencias: control individual por sesión (admin) ---------------------
CREATE TABLE IF NOT EXISTS asistencias (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id     INT UNSIGNED    NOT NULL,
    curso_id        VARCHAR(80)     NOT NULL,
    sesion_id       INT UNSIGNED    NOT NULL,
    presente        TINYINT(1)      NOT NULL DEFAULT 0,
    marcado_por     INT UNSIGNED    NULL,
    marcado_en      DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_asistencia (empleado_id, curso_id, sesion_id),
    KEY idx_asis_emp (empleado_id),
    KEY idx_asis_curso (curso_id),
    CONSTRAINT fk_asis_emp FOREIGN KEY (empleado_id)
        REFERENCES empleados (empleado_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_asis_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- telemetria_tiempos: apertura y tiempo activo ---------------------------
CREATE TABLE IF NOT EXISTS telemetria_tiempos (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id       INT UNSIGNED    NOT NULL,
    curso_id          VARCHAR(80)     NOT NULL,
    sesion_id         INT UNSIGNED    NULL,
    abierto_en        DATETIME        NOT NULL,
    segundos_activos  INT UNSIGNED    NOT NULL DEFAULT 0,
    ultima_actividad  DATETIME        NULL,
    creado_en         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tele_emp (empleado_id),
    KEY idx_tele_curso_sesion (curso_id, sesion_id),
    KEY idx_tele_abierto (abierto_en),
    CONSTRAINT fk_tele_emp FOREIGN KEY (empleado_id)
        REFERENCES empleados (empleado_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tele_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- cursos_puntuacion: puntos, nivel y aprobación por (empleado, curso) ----
CREATE TABLE IF NOT EXISTS cursos_puntuacion (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id         INT UNSIGNED    NOT NULL,
    curso_id            VARCHAR(80)     NOT NULL,
    puntos              INT UNSIGNED    NOT NULL DEFAULT 0,
    nivel               TINYINT UNSIGNED NOT NULL DEFAULT 1,
    estado_aprobacion   ENUM('pendiente','aprobado','reprobado') NOT NULL DEFAULT 'pendiente',
    aprobado_por        INT UNSIGNED    NULL,
    aprobado_en         DATETIME        NULL,
    certificado_emitido TINYINT(1)      NOT NULL DEFAULT 0,
    actualizado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_puntuacion (empleado_id, curso_id),
    KEY idx_punt_emp (empleado_id),
    KEY idx_punt_estado (estado_aprobacion),
    CONSTRAINT fk_punt_emp FOREIGN KEY (empleado_id)
        REFERENCES empleados (empleado_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_punt_curso FOREIGN KEY (curso_id)
        REFERENCES cursos (curso_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
