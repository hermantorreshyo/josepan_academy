<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$adj = Adjunto::find($id);
if (!$adj) { http_response_code(404); die('Adjunto no encontrado.'); }

$u = current_user();
$empId = (int)($u['id'] ?? 0);

// Solo si el empleado puede ver el curso (o es admin).
if (!Curso::esVisiblePara($adj['curso_id'], $empId, is_admin())) {
    http_response_code(403);
    die('No tienes acceso a este archivo.');
}

$archivo = basename($adj['archivo']); // anti-traversal
$dir = realpath(Adjunto::dir());
$ruta = realpath($dir . DIRECTORY_SEPARATOR . $archivo);

if ($dir === false || $ruta === false || !str_starts_with($ruta, $dir)) {
    http_response_code(404);
    die('Archivo no disponible.');
}

$mime = $adj['mime'] ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $adj['nombre']) . '"');
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta);
exit;
