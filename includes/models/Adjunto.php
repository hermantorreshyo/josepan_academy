<?php
/** Archivos adjuntos de las sesiones de un curso. */
class Adjunto
{
    /** Extensiones permitidas para subir. */
    public const PERMITIDAS = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','png','jpg','jpeg','gif','webp','zip'];
    public const MAX_BYTES = 20971520; // 20 MB

    public static function dir(): string
    {
        return rtrim(DOWNLOADS_DIR, '/\\') . DIRECTORY_SEPARATOR . 'adjuntos';
    }

    public static function listar(string $cursoId, int $sesionNum): array
    {
        return Database::run(
            "SELECT * FROM cursos_adjuntos WHERE curso_id = ? AND sesion_num = ? ORDER BY subido_en",
            [$cursoId, $sesionNum]
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $r = Database::run("SELECT * FROM cursos_adjuntos WHERE id = ?", [$id])->fetch();
        return $r ?: null;
    }

    /**
     * Guarda un archivo subido ($_FILES['x']) y crea el registro.
     * @return array{ok:bool, error?:string}
     */
    public static function guardar(string $cursoId, int $sesionNum, array $file, int $adminId): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'No se recibió el archivo.'];
        }
        if ($file['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'El archivo supera el límite de 20 MB.'];
        }

        $nombre = $file['name'];
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!in_array($ext, self::PERMITIDAS, true)) {
            return ['ok' => false, 'error' => 'Tipo de archivo no permitido (.' . e($ext) . ').'];
        }

        $dir = self::dir();
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        if (!is_writable($dir)) {
            return ['ok' => false, 'error' => 'La carpeta de adjuntos no es escribible.'];
        }

        // Nombre físico único y seguro.
        $fisico = bin2hex(random_bytes(8)) . '.' . $ext;
        $destino = $dir . DIRECTORY_SEPARATOR . $fisico;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            return ['ok' => false, 'error' => 'No se pudo almacenar el archivo.'];
        }

        Database::run(
            "INSERT INTO cursos_adjuntos (curso_id, sesion_num, nombre, archivo, mime, tamano, subido_por)
             VALUES (?,?,?,?,?,?,?)",
            [$cursoId, $sesionNum, mb_substr($nombre, 0, 200), $fisico, $file['type'] ?? null, (int)$file['size'], $adminId]
        );
        return ['ok' => true];
    }

    public static function eliminar(int $id): void
    {
        $a = self::find($id);
        if (!$a) return;
        $ruta = self::dir() . DIRECTORY_SEPARATOR . basename($a['archivo']);
        if (is_file($ruta)) @unlink($ruta);
        Database::run("DELETE FROM cursos_adjuntos WHERE id = ?", [$id]);
    }
}
