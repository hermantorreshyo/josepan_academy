<?php
/** Cache local de empleados (snapshot del perfil del API CORE) para reportes. */
class Empleado
{
    /** Inserta o actualiza el empleado y marca primer/último acceso. */
    public static function upsert(array $p, bool $esAdmin): void
    {
        if (empty($p['id'])) return;
        Database::run(
            "INSERT INTO empleados (empleado_id, nombre, rol, tienda, email, es_admin, primer_acceso, ultimo_acceso)
             VALUES (:id, :nombre, :rol, :tienda, :email, :admin, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                nombre = VALUES(nombre),
                rol = VALUES(rol),
                tienda = VALUES(tienda),
                email = VALUES(email),
                es_admin = VALUES(es_admin),
                ultimo_acceso = NOW()",
            [
                ':id' => $p['id'], ':nombre' => $p['nombre'], ':rol' => $p['rol'],
                ':tienda' => $p['tienda'], ':email' => $p['email'], ':admin' => $esAdmin ? 1 : 0,
            ]
        );
    }

    public static function all(): array
    {
        return Database::run("SELECT * FROM empleados ORDER BY nombre")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $row = Database::run("SELECT * FROM empleados WHERE empleado_id = ?", [$id])->fetch();
        return $row ?: null;
    }
}
