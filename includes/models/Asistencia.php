<?php
/** Control de asistencia individual por sesión (marcado por administradores). */
class Asistencia
{
    /** Marca/desmarca la asistencia de un empleado a una sesión concreta. */
    public static function marcar(int $empleadoId, string $cursoId, int $sesionId, bool $presente, int $adminId): void
    {
        Database::run(
            "INSERT INTO asistencias (empleado_id, curso_id, sesion_id, presente, marcado_por, marcado_en)
             VALUES (:e, :c, :s, :p, :a, NOW())
             ON DUPLICATE KEY UPDATE presente = VALUES(presente),
                marcado_por = VALUES(marcado_por), marcado_en = NOW()",
            [':e' => $empleadoId, ':c' => $cursoId, ':s' => $sesionId, ':p' => $presente ? 1 : 0, ':a' => $adminId]
        );
    }

    /** Mapa sesion_id => bool presente, para un empleado y curso. */
    public static function mapa(int $empleadoId, string $cursoId): array
    {
        $rows = Database::run(
            "SELECT sesion_id, presente FROM asistencias WHERE empleado_id = ? AND curso_id = ?",
            [$empleadoId, $cursoId]
        )->fetchAll();
        $mapa = [];
        foreach ($rows as $r) $mapa[(int)$r['sesion_id']] = (bool)$r['presente'];
        return $mapa;
    }
}
