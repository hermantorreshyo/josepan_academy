<?php
/**
 * Progreso de aprendizaje: sesiones completadas, puntuación por curso,
 * nivel global y estado de aprobación.
 */
class Progreso
{
    /** Marca una sesión como completada (idempotente) y recalcula puntos. */
    public static function completarSesion(int $empleadoId, string $cursoId, int $sesionId, int $totalSesiones): array
    {
        // Operación atómica: el UNIQUE evita duplicar la sesión.
        Database::run(
            "INSERT INTO usuarios_progreso (empleado_id, curso_id, sesion_id, estado, completado_en)
             VALUES (:e, :c, :s, 'completado', NOW())
             ON DUPLICATE KEY UPDATE estado = 'completado',
                completado_en = COALESCE(completado_en, NOW())",
            [':e' => $empleadoId, ':c' => $cursoId, ':s' => $sesionId]
        );

        return self::recalcular($empleadoId, $cursoId, $totalSesiones);
    }

    /** Recalcula la fila de cursos_puntuacion del empleado para ese curso. */
    public static function recalcular(int $empleadoId, string $cursoId, int $totalSesiones): array
    {
        $completas = self::sesionesCompletadas($empleadoId, $cursoId);
        $aprobado  = self::estaAprobado($empleadoId, $cursoId);
        $pptSesion = Parametros::getInt('puntos_por_sesion', PUNTOS_POR_SESION);
        $pptAprob  = Parametros::getInt('puntos_aprobacion', PUNTOS_APROBACION);
        $puntos    = $completas * $pptSesion + ($aprobado ? $pptAprob : 0);
        $nivelGlobal = self::nivelGlobalTrasActualizar($empleadoId, $cursoId, $puntos);

        Database::run(
            "INSERT INTO cursos_puntuacion (empleado_id, curso_id, puntos, nivel)
             VALUES (:e, :c, :p, :n)
             ON DUPLICATE KEY UPDATE puntos = VALUES(puntos), nivel = VALUES(nivel)",
            [':e' => $empleadoId, ':c' => $cursoId, ':p' => $puntos, ':n' => $nivelGlobal]
        );

        return [
            'sesiones_completadas' => $completas,
            'total_sesiones'       => $totalSesiones,
            'puntos_curso'         => $puntos,
            'curso_completo'       => $completas >= $totalSesiones,
            'aprobado'             => $aprobado,
            'certificable'         => ($completas >= $totalSesiones) && $aprobado,
        ];
    }

    private static function nivelGlobalTrasActualizar(int $empleadoId, string $cursoId, int $puntosCurso): int
    {
        // Suma de puntos de los demás cursos + los de este curso recién calculado.
        $row = Database::run(
            "SELECT COALESCE(SUM(puntos),0) AS total FROM cursos_puntuacion
             WHERE empleado_id = ? AND curso_id <> ?",
            [$empleadoId, $cursoId]
        )->fetch();
        $totalGlobal = (int)$row['total'] + $puntosCurso;
        return Gamification::estado($totalGlobal)['nivel'];
    }

    public static function sesionesCompletadas(int $empleadoId, string $cursoId): int
    {
        $row = Database::run(
            "SELECT COUNT(*) AS n FROM usuarios_progreso
             WHERE empleado_id = ? AND curso_id = ? AND estado = 'completado'",
            [$empleadoId, $cursoId]
        )->fetch();
        return (int)$row['n'];
    }

    /** IDs de sesiones completadas (para pintar checks en la UI). */
    public static function sesionesIds(int $empleadoId, string $cursoId): array
    {
        $rows = Database::run(
            "SELECT sesion_id FROM usuarios_progreso
             WHERE empleado_id = ? AND curso_id = ? AND estado = 'completado'",
            [$empleadoId, $cursoId]
        )->fetchAll();
        return array_map(fn($r) => (int)$r['sesion_id'], $rows);
    }

    public static function estaAprobado(int $empleadoId, string $cursoId): bool
    {
        $row = Database::run(
            "SELECT estado_aprobacion FROM cursos_puntuacion WHERE empleado_id = ? AND curso_id = ?",
            [$empleadoId, $cursoId]
        )->fetch();
        return $row && $row['estado_aprobacion'] === 'aprobado';
    }

    /** Admin: aprueba o reprueba el curso y recalcula puntos/certificación. */
    public static function setAprobacion(int $empleadoId, string $cursoId, string $estado, int $adminId, int $totalSesiones): void
    {
        $estado = in_array($estado, ['aprobado', 'reprobado', 'pendiente'], true) ? $estado : 'pendiente';
        Database::run(
            "INSERT INTO cursos_puntuacion (empleado_id, curso_id, estado_aprobacion, aprobado_por, aprobado_en)
             VALUES (:e, :c, :est, :a, NOW())
             ON DUPLICATE KEY UPDATE estado_aprobacion = VALUES(estado_aprobacion),
                aprobado_por = VALUES(aprobado_por), aprobado_en = NOW()",
            [':e' => $empleadoId, ':c' => $cursoId, ':est' => $estado, ':a' => $adminId]
        );
        self::recalcular($empleadoId, $cursoId, $totalSesiones);
    }

    /** Estado completo del curso para un empleado. */
    public static function resumenCurso(int $empleadoId, string $cursoId): array
    {
        $row = Database::run(
            "SELECT * FROM cursos_puntuacion WHERE empleado_id = ? AND curso_id = ?",
            [$empleadoId, $cursoId]
        )->fetch();
        return $row ?: [
            'puntos' => 0, 'nivel' => 1, 'estado_aprobacion' => 'pendiente',
            'certificado_emitido' => 0,
        ];
    }

    /** Puntos totales globales del empleado (todos los cursos). */
    public static function puntosGlobales(int $empleadoId): int
    {
        $row = Database::run(
            "SELECT COALESCE(SUM(puntos),0) AS total FROM cursos_puntuacion WHERE empleado_id = ?",
            [$empleadoId]
        )->fetch();
        return (int)$row['total'];
    }

    public static function marcarCertificadoEmitido(int $empleadoId, string $cursoId): void
    {
        Database::run(
            "UPDATE cursos_puntuacion SET certificado_emitido = 1 WHERE empleado_id = ? AND curso_id = ?",
            [$empleadoId, $cursoId]
        );
    }
}
