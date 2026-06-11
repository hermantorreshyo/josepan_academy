<?php
/** Catálogo de cursos y sesiones (datos maestros en BD) + gestión y visibilidad. */
class Curso
{
    /* ----------------------------- Lectura --------------------------------- */

    public static function all(): array
    {
        return Database::run(
            "SELECT c.*, c.curso_id AS id,
                    (SELECT COUNT(*) FROM cursos_sesiones s WHERE s.curso_id = c.curso_id) AS num_sesiones,
                    (SELECT COUNT(*) FROM curso_asignaciones a WHERE a.curso_id = c.curso_id) AS num_asignados
             FROM cursos c
             ORDER BY c.orden, c.titulo"
        )->fetchAll();
    }

    public static function find(string $id): ?array
    {
        $c = Database::run("SELECT *, curso_id AS id FROM cursos WHERE curso_id = ?", [$id])->fetch();
        if (!$c) return null;

        $rows = Database::run(
            "SELECT * FROM cursos_sesiones WHERE curso_id = ? ORDER BY sesion_num",
            [$id]
        )->fetchAll();

        $c['sesiones'] = array_map(function ($s) {
            $mat = json_decode($s['materiales'] ?? '[]', true);
            return [
                'id'        => (int)$s['sesion_num'],
                'titulo'    => $s['titulo'],
                'subtitulo' => $s['subtitulo'] ?? '',
                'video'     => $s['video'] ?? '',
                'guia'      => $s['guia'] ?? '',
                'materiales'=> is_array($mat) ? $mat : [],
            ];
        }, $rows);

        return $c;
    }

    public static function existe(string $id): bool
    {
        return (bool)Database::run("SELECT 1 FROM cursos WHERE curso_id = ?", [$id])->fetch();
    }

    public static function totalSesiones(string $id): int
    {
        $r = Database::run("SELECT COUNT(*) AS n FROM cursos_sesiones WHERE curso_id = ?", [$id])->fetch();
        return (int)$r['n'];
    }

    public static function primero(): ?string
    {
        $r = Database::run("SELECT curso_id FROM cursos ORDER BY orden, titulo LIMIT 1")->fetch();
        return $r ? $r['curso_id'] : null;
    }

    /** Una sesión concreta (para edición). */
    public static function sesion(string $cursoId, int $num): ?array
    {
        $r = Database::run(
            "SELECT * FROM cursos_sesiones WHERE curso_id = ? AND sesion_num = ?",
            [$cursoId, $num]
        )->fetch();
        return $r ?: null;
    }

    /* --------------------------- Visibilidad ------------------------------- */

    /** Cursos visibles para un empleado (admins ven todos). */
    public static function visiblesPara(int $empId, bool $esAdmin): array
    {
        if ($esAdmin) return self::all();
        return Database::run(
            "SELECT c.*, c.curso_id AS id,
                    (SELECT COUNT(*) FROM cursos_sesiones s WHERE s.curso_id = c.curso_id) AS num_sesiones
             FROM cursos c
             WHERE c.visibilidad = 'todos'
                OR EXISTS (SELECT 1 FROM curso_asignaciones a
                           WHERE a.curso_id = c.curso_id AND a.empleado_id = ?)
             ORDER BY c.orden, c.titulo",
            [$empId]
        )->fetchAll();
    }

    public static function esVisiblePara(string $cursoId, int $empId, bool $esAdmin): bool
    {
        if ($esAdmin) return self::existe($cursoId);
        $r = Database::run(
            "SELECT 1 FROM cursos c
             WHERE c.curso_id = ?
               AND (c.visibilidad = 'todos'
                    OR EXISTS (SELECT 1 FROM curso_asignaciones a
                               WHERE a.curso_id = c.curso_id AND a.empleado_id = ?))",
            [$cursoId, $empId]
        )->fetch();
        return (bool)$r;
    }

    /* --------------------------- Escritura --------------------------------- */

    public static function crear(array $d): void
    {
        $ordRow = Database::run("SELECT COALESCE(MAX(orden),0)+1 AS o FROM cursos")->fetch();
        Database::run(
            "INSERT INTO cursos (curso_id, titulo, resumen, categoria, horas, obligatorio, visibilidad, orden)
             VALUES (:id, :t, :r, :c, :h, :o, :v, :ord)",
            [
                ':id' => $d['curso_id'], ':t' => $d['titulo'], ':r' => $d['resumen'] ?? '',
                ':c' => $d['categoria'] ?? '', ':h' => (int)($d['horas'] ?? 0),
                ':o' => !empty($d['obligatorio']) ? 1 : 0,
                ':v' => ($d['visibilidad'] ?? 'todos') === 'asignados' ? 'asignados' : 'todos',
                ':ord' => (int)$ordRow['o'],
            ]
        );
    }

    public static function actualizar(string $id, array $d): void
    {
        Database::run(
            "UPDATE cursos SET titulo=:t, resumen=:r, categoria=:c, horas=:h,
                obligatorio=:o, visibilidad=:v WHERE curso_id=:id",
            [
                ':t' => $d['titulo'], ':r' => $d['resumen'] ?? '', ':c' => $d['categoria'] ?? '',
                ':h' => (int)($d['horas'] ?? 0), ':o' => !empty($d['obligatorio']) ? 1 : 0,
                ':v' => ($d['visibilidad'] ?? 'todos') === 'asignados' ? 'asignados' : 'todos',
                ':id' => $id,
            ]
        );
    }

    public static function eliminar(string $id): void
    {
        // Las FK ON DELETE CASCADE limpian sesiones, adjuntos, asignaciones,
        // progreso, asistencias, telemetría y puntuación.
        Database::run("DELETE FROM cursos WHERE curso_id = ?", [$id]);
    }

    public static function setVisibilidad(string $id, string $vis): void
    {
        $vis = $vis === 'asignados' ? 'asignados' : 'todos';
        Database::run("UPDATE cursos SET visibilidad = ? WHERE curso_id = ?", [$vis, $id]);
    }

    /* ---------------------------- Sesiones --------------------------------- */

    public static function siguienteSesionNum(string $id): int
    {
        $r = Database::run("SELECT COALESCE(MAX(sesion_num),0)+1 AS n FROM cursos_sesiones WHERE curso_id = ?", [$id])->fetch();
        return (int)$r['n'];
    }

    public static function crearSesion(string $cursoId, array $s): int
    {
        $num = isset($s['sesion_num']) ? (int)$s['sesion_num'] : self::siguienteSesionNum($cursoId);
        Database::run(
            "INSERT INTO cursos_sesiones (curso_id, sesion_num, titulo, subtitulo, video, guia, materiales)
             VALUES (:c,:n,:t,:s,:v,:g,:m)
             ON DUPLICATE KEY UPDATE titulo=VALUES(titulo), subtitulo=VALUES(subtitulo),
                video=VALUES(video), guia=VALUES(guia), materiales=VALUES(materiales)",
            [
                ':c' => $cursoId, ':n' => $num, ':t' => $s['titulo'] ?? ('Sesión ' . $num),
                ':s' => $s['subtitulo'] ?? '', ':v' => $s['video'] ?? '', ':g' => $s['guia'] ?? '',
                ':m' => json_encode($s['materiales'] ?? [], JSON_UNESCAPED_UNICODE),
            ]
        );
        return $num;
    }

    public static function actualizarSesion(string $cursoId, int $num, array $s): void
    {
        Database::run(
            "UPDATE cursos_sesiones SET titulo=:t, subtitulo=:s, video=:v, guia=:g, materiales=:m
             WHERE curso_id=:c AND sesion_num=:n",
            [
                ':t' => $s['titulo'] ?? ('Sesión ' . $num), ':s' => $s['subtitulo'] ?? '',
                ':v' => $s['video'] ?? '', ':g' => $s['guia'] ?? '',
                ':m' => json_encode($s['materiales'] ?? [], JSON_UNESCAPED_UNICODE),
                ':c' => $cursoId, ':n' => $num,
            ]
        );
    }

    public static function eliminarSesion(string $cursoId, int $num): void
    {
        Database::run("DELETE FROM cursos_sesiones WHERE curso_id = ? AND sesion_num = ?", [$cursoId, $num]);
        Database::run("DELETE FROM cursos_adjuntos WHERE curso_id = ? AND sesion_num = ?", [$cursoId, $num]);
    }

    /* -------------------------- Asignaciones ------------------------------- */

    public static function asignar(string $cursoId, int $empId, int $adminId): void
    {
        Database::run(
            "INSERT INTO curso_asignaciones (curso_id, empleado_id, asignado_por)
             VALUES (?,?,?) ON DUPLICATE KEY UPDATE asignado_por = VALUES(asignado_por)",
            [$cursoId, $empId, $adminId]
        );
    }

    public static function desasignar(string $cursoId, int $empId): void
    {
        Database::run("DELETE FROM curso_asignaciones WHERE curso_id = ? AND empleado_id = ?", [$cursoId, $empId]);
    }

    /** IDs de empleados asignados a un curso. */
    public static function asignados(string $cursoId): array
    {
        $rows = Database::run("SELECT empleado_id FROM curso_asignaciones WHERE curso_id = ?", [$cursoId])->fetchAll();
        return array_map(fn($r) => (int)$r['empleado_id'], $rows);
    }
}
