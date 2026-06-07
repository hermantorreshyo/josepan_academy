<?php
/** Funciones de utilidad compartidas. */

/** Escape XSS para salida HTML. */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Respuesta JSON y fin de script. */
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Lee el cuerpo JSON de una petición AJAX. */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Redirección segura. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Construye una URL relativa a la base de la app (raíz o subcarpeta). */
function url(string $path = ''): string
{
    $base = defined('APP_BASE') ? APP_BASE : '/';
    return $base . ltrim($path, '/');
}

/** Minutos a texto legible (p. ej. 95 -> "1 h 35 min"). */
function fmt_minutos(int $min): string
{
    if ($min < 60) return $min . ' min';
    $h = intdiv($min, 60);
    $m = $min % 60;
    return $m ? "{$h} h {$m} min" : "{$h} h";
}

/** Token CSRF por sesión. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}
