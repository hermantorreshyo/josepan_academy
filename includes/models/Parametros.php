<?php
/** Parametrización del sistema (tabla parametros, poblada por el seeder). */
class Parametros
{
    private static ?array $cache = null;

    public static function load(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::run("SELECT clave, valor FROM parametros")->fetchAll() as $r) {
                    self::$cache[$r['clave']] = $r['valor'];
                }
            } catch (Throwable $e) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function getInt(string $clave, int $default): int
    {
        $v = self::load()[$clave] ?? null;
        return $v === null ? $default : (int)$v;
    }
}
