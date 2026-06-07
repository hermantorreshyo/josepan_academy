<?php
/** Acceso al catálogo de cursos y sesiones (datos maestros en BD). */
class Curso
{
    /** Lista de cursos con su número de sesiones. */
    public static function all(): array
    {
        return Database::run(
            "SELECT c.*, c.curso_id AS id,
                    (SELECT COUNT(*) FROM cursos_sesiones s WHERE s.curso_id = c.curso_id) AS num_sesiones
             FROM cursos c
             ORDER BY c.orden, c.titulo"
        )->fetchAll();
    }

    /** Curso con sus sesiones, en la forma que consumen las vistas. */
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

    public static function totalSesiones(string $id): int
    {
        $r = Database::run("SELECT COUNT(*) AS n FROM cursos_sesiones WHERE curso_id = ?", [$id])->fetch();
        return (int)$r['n'];
    }

    /** Primer curso del catálogo (para selectores admin por defecto). */
    public static function primero(): ?string
    {
        $r = Database::run("SELECT curso_id FROM cursos ORDER BY orden, titulo LIMIT 1")->fetch();
        return $r ? $r['curso_id'] : null;
    }
}
