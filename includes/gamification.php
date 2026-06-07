<?php
/** Sistema de niveles de capacitación basado en puntos acumulados. */

class Gamification
{
    /** Escala por defecto (respaldo y fuente del seeder). */
    public const NIVELES = [
        ['nivel' => 1, 'nombre' => 'Aprendiz',            'min' => 0],
        ['nivel' => 2, 'nombre' => 'Operativo',           'min' => 100],
        ['nivel' => 3, 'nombre' => 'Referente',           'min' => 250],
        ['nivel' => 4, 'nombre' => 'Líder',               'min' => 500],
        ['nivel' => 5, 'nombre' => 'Embajador JOSEPAN',   'min' => 1000],
    ];

    private static ?array $cache = null;

    /** Niveles vigentes: desde la tabla 'niveles' si existe, si no las constantes. */
    public static function niveles(): array
    {
        if (self::$cache === null) {
            self::$cache = self::NIVELES;
            if (class_exists('Database')) {
                try {
                    $rows = Database::run("SELECT nivel, nombre, min_puntos FROM niveles ORDER BY min_puntos")->fetchAll();
                    if ($rows) {
                        self::$cache = array_map(fn($r) => [
                            'nivel' => (int)$r['nivel'], 'nombre' => $r['nombre'], 'min' => (int)$r['min_puntos'],
                        ], $rows);
                    }
                } catch (Throwable $e) {
                    // se mantiene el respaldo
                }
            }
        }
        return self::$cache;
    }

    /** Devuelve nivel actual, siguiente umbral y progreso (0-100). */
    public static function estado(int $puntos): array
    {
        $niveles = self::niveles();
        $actual = $niveles[0];
        $siguiente = null;

        foreach ($niveles as $i => $n) {
            if ($puntos >= $n['min']) {
                $actual = $n;
                $siguiente = $niveles[$i + 1] ?? null;
            }
        }

        if ($siguiente === null) {
            $progreso = 100;
            $faltan = 0;
        } else {
            $rango = $siguiente['min'] - $actual['min'];
            $avance = $puntos - $actual['min'];
            $progreso = $rango > 0 ? (int)round($avance / $rango * 100) : 0;
            $faltan = max(0, $siguiente['min'] - $puntos);
        }

        return [
            'puntos'        => $puntos,
            'nivel'         => $actual['nivel'],
            'nivel_nombre'  => $actual['nombre'],
            'siguiente'     => $siguiente ? $siguiente['nombre'] : null,
            'progreso'      => $progreso,
            'faltan'        => $faltan,
        ];
    }
}
