<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login_api();

if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['ok' => false, 'error' => 'CSRF inválido'], 419);
}

$in = read_json_body();
$id = (int)($in['id'] ?? 0);
$segundos = (int)($in['segundos'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'error' => 'id requerido'], 422);

$empId = (int)current_user()['id'];
Telemetria::ping($id, $empId, $segundos);

json_response(['ok' => true]);
