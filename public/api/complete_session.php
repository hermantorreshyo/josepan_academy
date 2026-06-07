<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login_api();

if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['ok' => false, 'error' => 'CSRF inválido'], 419);
}

$in = read_json_body();
$cursoId = trim((string)($in['curso_id'] ?? ''));
$sesionId = (int)($in['sesion_id'] ?? 0);

$curso = Curso::find($cursoId);
if (!$curso) json_response(['ok' => false, 'error' => 'Curso no válido'], 422);
$total = count($curso['sesiones']);
$valida = false;
foreach ($curso['sesiones'] as $s) if ((int)$s['id'] === $sesionId) $valida = true;
if (!$valida) json_response(['ok' => false, 'error' => 'Sesión no válida'], 422);

$empId = (int)current_user()['id'];
$progreso = Progreso::completarSesion($empId, $cursoId, $sesionId, $total);

json_response(['ok' => true, 'progreso' => $progreso]);
