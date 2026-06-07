<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login_api();

if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['ok' => false, 'error' => 'CSRF inválido'], 419);
}

$in = read_json_body();
$cursoId = trim((string)($in['curso_id'] ?? ''));
$sesionId = isset($in['sesion_id']) && $in['sesion_id'] !== null ? (int)$in['sesion_id'] : null;
if ($cursoId === '') json_response(['ok' => false, 'error' => 'curso_id requerido'], 422);

$empId = (int)current_user()['id'];
$id = Telemetria::abrir($empId, $cursoId, $sesionId);

json_response(['ok' => true, 'id' => $id]);
