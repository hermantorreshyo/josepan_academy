<?php
/**
 * Migraciones idempotentes para instalaciones ya desplegadas.
 * La ejecuta el instalador tras schema+seeders. Comprueba el estado real del
 * esquema en information_schema y aplica solo lo que falte.
 */
function jp_run_migraciones(PDO $pdo): array
{
    $hechas = [];

    // Helper: ¿existe una columna?
    $existeColumna = function (string $tabla, string $col) use ($pdo): bool {
        $st = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $st->execute([$tabla, $col]);
        return (int)$st->fetchColumn() > 0;
    };

    // 1) cursos.visibilidad
    if (!$existeColumna('cursos', 'visibilidad')) {
        $pdo->exec(
            "ALTER TABLE cursos
             ADD COLUMN visibilidad ENUM('todos','asignados') NOT NULL DEFAULT 'todos' AFTER obligatorio"
        );
        $hechas[] = 'cursos.visibilidad';
    }

    // 2) cursos_adjuntos (por si la instalación es anterior a esta tabla)
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cursos_adjuntos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $hechas[] = 'cursos_adjuntos';

    // 3) curso_asignaciones
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS curso_asignaciones (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $hechas[] = 'curso_asignaciones';

    return $hechas;
}
