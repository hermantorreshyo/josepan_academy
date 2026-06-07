<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login(); // sin sesión no se sirve ningún PDF

$docs = require __DIR__ . '/../data/documentos.php';
$codigo = (string)($_GET['doc'] ?? '');

// El documento debe existir en la whitelist (no se acepta una ruta arbitraria).
if (!isset($docs[$codigo])) {
    http_response_code(404);
    die('Documento no encontrado.');
}

$archivo = basename($docs[$codigo]['archivo']); // neutraliza ../ traversal
$ruta = rtrim(DOWNLOADS_DIR, '/\\') . DIRECTORY_SEPARATOR . $archivo;
$real = realpath($ruta);
$baseReal = realpath(DOWNLOADS_DIR);

// Confirma que el path resuelto sigue dentro de DOWNLOADS_DIR.
if ($real === false || $baseReal === false || !str_starts_with($real, $baseReal)) {
    http_response_code(404);
    die('Archivo no disponible. Súbelo a la carpeta /downloads.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($real));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($real);
exit;
