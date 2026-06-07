<?php
/**
 * Punto de arranque común. Inclúyelo al principio de cada script.
 * Configura la sesión segura y carga config, BD, API y helpers.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/includes/base.php';
if (!defined('APP_BASE')) {
    define('APP_BASE', app_base_auto());
}

// --- Guard de instalación ---------------------------------------------------
// Si la plataforma no está instalada, redirige al asistente. Las páginas
// públicas (instalador y manuales) no pasan por aquí y siempre son visibles.
$instalado = file_exists($root . '/config/config.php') && file_exists($root . '/config/installed.lock');
if (!$instalado) {
    header('Location: ' . APP_BASE . 'install/');
    exit;
}

require_once $root . '/config/config.php';
require_once $root . '/config/database.php';
require_once $root . '/sdk_omni/OmniCoreClient.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/gamification.php';
require_once $root . '/includes/models/Empleado.php';
require_once $root . '/includes/models/Parametros.php';
require_once $root . '/includes/models/Curso.php';
require_once $root . '/includes/models/Progreso.php';
require_once $root . '/includes/models/Telemetria.php';
require_once $root . '/includes/models/Asistencia.php';
require_once $root . '/includes/models/Certificado.php';

// --- Sesión con flags de seguridad -----------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => SESSION_SECURE,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Control de inactividad -------------------------------------------------
if (!empty($_SESSION['last_activity'])) {
    $idle = time() - (int)$_SESSION['last_activity'];
    if ($idle > SESSION_IDLE_MIN * 60) {
        session_unset();
        session_destroy();
    }
}
$_SESSION['last_activity'] = time();
