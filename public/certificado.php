<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$cursoId = (string)($_GET['curso'] ?? '');
$curso = Curso::find($cursoId);
if (!$curso) { http_response_code(404); die('Curso no encontrado.'); }

$u = current_user();
$empId = (int)($u['id'] ?? 0);
$total = count($curso['sesiones']);

// Verificación: 100% de sesiones + aprobado.
$hechas = Progreso::sesionesCompletadas($empId, $cursoId);
$resumen = Progreso::resumenCurso($empId, $cursoId);
$aprobado = ($resumen['estado_aprobacion'] ?? '') === 'aprobado';

if ($hechas < $total || !$aprobado) {
    http_response_code(403);
    die('El certificado aún no está disponible. Requiere completar todas las sesiones y aprobación del módulo.');
}

$pdf = Certificado::generar(
    ['nombre' => $u['nombre'], 'rol' => $u['rol'], 'tienda' => $u['tienda']],
    ['titulo' => $curso['titulo']],
    (int)($resumen['puntos'] ?? 0)
);

Progreso::marcarCertificadoEmitido($empId, $cursoId);

$nombreArchivo = 'Certificado_' . preg_replace('/[^A-Za-z0-9]+/', '_', $curso['titulo']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . strlen($pdf));
header('X-Content-Type-Options: nosniff');
echo $pdf;
exit;
