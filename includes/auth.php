<?php
/** Autenticación / autorización basada en sesión del servidor. */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['token']);
}

/** Determina si el usuario actual es administrador (Director, RRHH, etc.). */
function is_admin(): bool
{
    $u = current_user();
    if (!$u) return false;

    $rol = $u['rol'] ?? '';
    foreach ($GLOBALS['ADMIN_ROLES'] as $needle) {
        if ($needle !== '' && stripos($rol, $needle) !== false) return true;
    }
    if (!empty($u['email']) && in_array($u['email'], $GLOBALS['ADMIN_EMAILS'], true)) return true;

    // Permisos OMNI (resource.action) que conceden administración.
    $perms = $_SESSION['permissions'] ?? [];
    foreach (($GLOBALS['ADMIN_PERMS'] ?? []) as $p) {
        if (in_array($p, $perms, true)) return true;
    }

    return false;
}

/** Acceso a la documentación TÉCNICA del sistema. */
function is_tech(): bool
{
    $u = current_user();
    if (!$u) return false;
    if (is_admin()) return true;
    $rol = $u['rol'] ?? '';
    foreach ($GLOBALS['TECH_ROLES'] as $needle) {
        if ($needle !== '' && stripos($rol, $needle) !== false) return true;
    }
    return false;
}

/** Exige sesión válida o redirige al login. */
function require_login(): void
{
    if (!is_logged_in()) {
        $back = urlencode($_SERVER['REQUEST_URI'] ?? url('index.php'));
        redirect(url('login.php') . '?next=' . $back);
    }
}

/** Igual pero para endpoints AJAX: responde 401 JSON. */
function require_login_api(): void
{
    if (!is_logged_in()) {
        json_response(['ok' => false, 'error' => 'No autenticado', 'code' => 'UNAUTHENTICATED'], 401);
    }
}

/** Exige rol administrador. */
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('Acceso restringido a administradores.');
    }
}

/** Crea la sesión tras un login correcto en OMNI y cachea el perfil en BD. */
function establish_session(string $token, array $profile, array $permissions = []): void
{
    session_regenerate_id(true); // evita fijación de sesión
    $_SESSION['token']       = $token;
    $_SESSION['user']        = $profile;
    $_SESSION['permissions'] = $permissions;

    Empleado::upsert($profile, is_admin());
}
