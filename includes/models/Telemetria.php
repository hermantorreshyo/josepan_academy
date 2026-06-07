<?php
/** Telemetría de uso: registra apertura de módulos y tiempo activo. */
class Telemetria
{
    /** Registra la apertura de un módulo y devuelve el id de la sesión de telemetría. */
    public static function abrir(int $empleadoId, string $cursoId, ?int $sesionId): int
    {
        Database::run(
            "INSERT INTO telemetria_tiempos (empleado_id, curso_id, sesion_id, abierto_en, segundos_activos, ultima_actividad)
             VALUES (:e, :c, :s, NOW(), 0, NOW())",
            [':e' => $empleadoId, ':c' => $cursoId, ':s' => $sesionId]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * Suma segundos activos de forma ATÓMICA (nunca con UPDATE de valor fijo).
     * Se limita el delta para evitar inflar el tiempo si el cliente manipula el ping.
     */
    public static function ping(int $telemetriaId, int $empleadoId, int $segundos): bool
    {
        $segundos = max(0, min($segundos, 120)); // tope defensivo por ping
        $stmt = Database::run(
            "UPDATE telemetria_tiempos
                SET segundos_activos = segundos_activos + :seg, ultima_actividad = NOW()
              WHERE id = :id AND empleado_id = :e",
            [':seg' => $segundos, ':id' => $telemetriaId, ':e' => $empleadoId]
        );
        return $stmt->rowCount() >= 0;
    }

    /** Minutos totales acumulados por un empleado en toda la plataforma. */
    public static function minutosTotales(int $empleadoId): int
    {
        $row = Database::run(
            "SELECT COALESCE(SUM(segundos_activos),0) AS s FROM telemetria_tiempos WHERE empleado_id = ?",
            [$empleadoId]
        )->fetch();
        return (int)floor((int)$row['s'] / 60);
    }

    /** Número de módulos abiertos por un empleado. */
    public static function modulosAbiertos(int $empleadoId): int
    {
        $row = Database::run(
            "SELECT COUNT(*) AS n FROM telemetria_tiempos WHERE empleado_id = ?",
            [$empleadoId]
        )->fetch();
        return (int)$row['n'];
    }
}
