<?php
/**
 * Asistente de instalación de la Academia JOSEPAN 360.
 * Solicita los parámetros, ESCRIBE config/config.php, crea la base de datos,
 * carga schema.sql y deja la plataforma operativa. Se autobloquea tras instalar.
 */
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$root       = dirname(__DIR__, 2);
require_once $root . '/includes/base.php';
$BASE       = app_base_auto();
$configDir  = $root . '/config';
$configFile = $configDir . '/config.php';
$lockFile   = $configDir . '/installed.lock';
$schemaFile = $root . '/schema.sql';
$seedFile   = $root . '/data/seeders.php';
$downloads  = $root . '/downloads';

function he($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$yaInstalado = file_exists($lockFile) && file_exists($configFile);
$forzar = (($_GET['reinstalar'] ?? '') === '1');

/* ----------------------------- Requisitos -------------------------------- */
$req = [
    ['ok' => version_compare(PHP_VERSION, '8.0.0', '>='), 'l' => 'PHP 8.0 o superior', 'd' => PHP_VERSION],
    ['ok' => extension_loaded('pdo_mysql'), 'l' => 'Extensión PDO MySQL', 'd' => ''],
    ['ok' => extension_loaded('curl'),      'l' => 'Extensión cURL', 'd' => ''],
    ['ok' => extension_loaded('mbstring'),  'l' => 'Extensión mbstring', 'd' => ''],
    ['ok' => is_writable($configDir),       'l' => 'Carpeta config/ escribible', 'd' => $configDir],
    ['ok' => is_writable($downloads),       'l' => 'Carpeta downloads/ escribible', 'd' => $downloads],
    ['ok' => is_readable($schemaFile),      'l' => 'Esquema schema.sql disponible', 'd' => ''],
];
$reqOk = array_reduce($req, fn($c, $r) => $c && $r['ok'], true);

/* ----------------------- Generador de config.php ------------------------- */
function build_config(array $v): string
{
    $s   = fn($x) => var_export((string)$x, true);
    $b   = fn($x) => $x ? 'true' : 'false';
    $arr = function (array $items): string {
        $items = array_values(array_filter(array_map('trim', $items), fn($x) => $x !== ''));
        $parts = array_map(fn($i) => var_export($i, true), $items);
        return '[' . implode(', ', $parts) . ']';
    };

    return "<?php\n"
        . "/**\n * Configuración generada por el instalador · Academia JOSEPAN 360.\n"
        . " * Generado el " . date('Y-m-d H:i:s') . ". Edítalo si cambian los parámetros.\n */\n\n"
        . "// --- Base de datos ---\n"
        . "define('DB_HOST', {$s($v['db_host'])});\n"
        . "define('DB_PORT', {$s($v['db_port'])});\n"
        . "define('DB_NAME', {$s($v['db_name'])});\n"
        . "define('DB_USER', {$s($v['db_user'])});\n"
        . "define('DB_PASS', {$s($v['db_pass'])});\n"
        . "define('DB_CHARSET', 'utf8mb4');\n\n"
        . "// --- OMNI API CORE [1001] ---\n"
        . "define('API_CORE_BASE', {$s($v['api_base'])});\n"
        . "define('API_PREFIX', '/api/v1');\n"
        . "define('API_TIMEOUT', 10);\n\n"
        . "// --- Sesión ---\n"
        . "define('SESSION_NAME', 'JP360ACAD');\n"
        . "define('SESSION_SECURE', {$b($v['session_secure'])});\n"
        . "define('SESSION_IDLE_MIN', " . (int)$v['idle'] . ");\n\n"
        . "// --- Roles con privilegios ---\n"
        . "\$GLOBALS['ADMIN_ROLES'] = {$arr($v['admin_roles'])};\n"
        . "\$GLOBALS['TECH_ROLES']  = {$arr($v['tech_roles'])};\n"
        . "\$GLOBALS['ADMIN_EMAILS'] = {$arr($v['admin_emails'])};\n"
        . "\$GLOBALS['ADMIN_PERMS']  = ['academia.admin', '*'];\n\n"
        . "// --- Gamificación ---\n"
        . "define('PUNTOS_POR_SESION', 25);\n"
        . "define('PUNTOS_APROBACION', 50);\n\n"
        . "// --- Rutas ---\n"
        . "define('DOWNLOADS_DIR', __DIR__ . '/../downloads');\n\n"
        . "// --- Modo desarrollo ---\n"
        . "define('DEV_MODE', {$b($v['dev_mode'])});\n"
        . "define('DEV_USER', {$s($v['dev_user'])});\n"
        . "define('DEV_PASS', {$s($v['dev_pass'])});\n";
}

/* ----------------------------- Procesar ---------------------------------- */
$errors = [];
$done   = false;
$seedStats = null;
$old    = [
    'db_host' => $_POST['db_host'] ?? '127.0.0.1',
    'db_port' => $_POST['db_port'] ?? '3306',
    'db_name' => $_POST['db_name'] ?? 'josepan_academia',
    'db_user' => $_POST['db_user'] ?? 'root',
    'db_pass' => $_POST['db_pass'] ?? '',
    'api_base' => $_POST['api_base'] ?? 'https://omni.josepan.es',
    'idle' => $_POST['idle'] ?? '60',
    'admin_roles' => $_POST['admin_roles'] ?? 'Director, RRHH, Recursos Humanos, Administrador, Coordinad',
    'dev_user' => $_POST['dev_user'] ?? 'admin@josepan360.com',
    'dev_pass' => $_POST['dev_pass'] ?? 'demo1234',
    'session_secure' => isset($_POST['session_secure']),
    'dev_mode' => isset($_POST['dev_mode']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'instalar') {
    $dbHost = trim($old['db_host']);
    $dbPort = trim($old['db_port']);
    $dbName = trim($old['db_name']);
    $dbUser = trim($old['db_user']);
    $dbPass = (string)$old['db_pass'];
    $apiBase = rtrim(trim($old['api_base']), '/');

    if (!$reqOk)                                       $errors[] = 'El entorno no cumple los requisitos.';
    if ($dbHost === '' || $dbName === '' || $dbUser === '') $errors[] = 'Host, base de datos y usuario son obligatorios.';
    if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName))      $errors[] = 'El nombre de la base de datos solo admite letras, números y guion bajo.';
    if ($apiBase === '')                                $errors[] = 'La URL de la API CORE es obligatoria.';

    if (!$errors) {
        try {
            $pdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                $dbUser, $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            $pdo->exec(file_get_contents($schemaFile));

            // Seeders: datos maestros (cursos, 4 sesiones, niveles, parámetros).
            require_once $seedFile;
            $seedStats = jp_run_seeders($pdo);

            $cfg = build_config([
                'db_host' => $dbHost, 'db_port' => $dbPort, 'db_name' => $dbName,
                'db_user' => $dbUser, 'db_pass' => $dbPass, 'api_base' => $apiBase,
                'session_secure' => $old['session_secure'], 'idle' => (int)$old['idle'],
                'admin_roles' => explode(',', $old['admin_roles']),
                'tech_roles' => ['Administrador', 'Tecnico', 'Técnico', 'Sistemas', 'Director'],
                'admin_emails' => [],
                'dev_mode' => $old['dev_mode'], 'dev_user' => $old['dev_user'], 'dev_pass' => $old['dev_pass'],
            ]);

            if (file_put_contents($configFile, $cfg) === false) {
                $errors[] = 'No se pudo escribir config/config.php (permisos).';
            } else {
                @file_put_contents($lockFile, 'Instalado: ' . date('c'));
                $done = true;
            }
        } catch (Throwable $ex) {
            $errors[] = 'Error de base de datos: ' . $ex->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<base href="<?= he($BASE) ?>">
<meta name="theme-color" content="#642a72">
<title>Instalación · Academia JOSEPAN 360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head><body>
<div style="max-width:680px;margin:36px auto;padding:0 16px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:10px">
    <div class="brand"><div class="logo">J</div><div><span class="name">JOSEPAN 360</span><small>Instalador de la Academia</small></div></div>
    <div style="display:flex;gap:8px">
      <a class="btn ghost sm" href="manual-usuario.php">📖 Manual de usuario</a>
      <a class="btn ghost sm" href="manual-tecnico.html">🛠️ Manual técnico</a>
    </div>
  </div>

<?php if ($done): ?>
  <div class="alert ok">✅ Instalación completada. La base de datos quedó desplegada y la plataforma está operativa.</div>
  <?php if ($seedStats): ?>
  <div class="card pad" style="margin-bottom:14px">
    <h2 style="margin-top:0;font-size:16px">Datos maestros cargados (seeders)</h2>
    <div class="stats-row">
      <div class="stat"><b><?= (int)$seedStats['cursos'] ?></b><span>Cursos</span></div>
      <div class="stat"><b><?= (int)$seedStats['sesiones'] ?></b><span>Sesiones</span></div>
      <div class="stat"><b><?= (int)$seedStats['niveles'] ?></b><span>Niveles</span></div>
      <div class="stat"><b><?= (int)$seedStats['parametros'] ?></b><span>Parámetros</span></div>
    </div>
  </div>
  <?php endif; ?>
  <div class="card pad">
    <h2 style="margin-top:0;font-size:18px">Siguientes pasos</h2>
    <div class="guia"><ul>
      <li>Sube los PDF de la biblioteca a la carpeta <code>downloads/</code> con los nombres de <code>data/documentos.php</code>.</li>
      <li>Por seguridad, elimina o renombra el directorio <code>public/install/</code>.</li>
    </ul></div>
    <a class="btn" href="login.php">🔒 Entrar a la plataforma</a>
  </div>

<?php elseif ($yaInstalado && !$forzar): ?>
  <div class="alert ok">✅ La plataforma ya está instalada.</div>
  <div class="card pad">
    <p>Puedes acceder directamente o, si necesitas reconfigurar, reinstalar (sobrescribe <code>config.php</code>).</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="login.php">🔒 Entrar</a>
      <a class="btn ghost" href="install/?reinstalar=1">⚙️ Reinstalar</a>
    </div>
  </div>

<?php else: ?>
  <div class="card pad" style="margin-bottom:18px">
    <h2 style="margin-top:0;font-size:17px">1 · Verificación del entorno</h2>
    <ul class="doclist" style="margin:0 -22px -10px">
      <?php foreach ($req as $r): ?>
        <li>
          <div class="ic"><?= $r['ok'] ? '✅' : '⛔' ?></div>
          <div class="info"><b><?= he($r['l']) ?></b><?php if($r['d']):?><span class="muted" style="font-size:12px"><?= he($r['d']) ?></span><?php endif;?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <?php foreach ($errors as $er): ?><div class="alert err">⚠️ <span><?= he($er) ?></span></div><?php endforeach; ?>

  <form method="post" class="card pad">
    <input type="hidden" name="accion" value="instalar">
    <h2 style="margin-top:0;font-size:17px">2 · Parámetros de instalación</h2>

    <p class="muted" style="font-size:13px;margin-top:0">Base de datos (MySQL / MariaDB)</p>
    <div class="grid cols-2">
      <div><label>Host</label><input type="text" name="db_host" value="<?= he($old['db_host']) ?>" required></div>
      <div><label>Puerto</label><input type="text" name="db_port" value="<?= he($old['db_port']) ?>"></div>
    </div>
    <div style="margin-top:12px"><label>Nombre de la base de datos</label><input type="text" name="db_name" value="<?= he($old['db_name']) ?>" required></div>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Usuario</label><input type="text" name="db_user" value="<?= he($old['db_user']) ?>" required></div>
      <div><label>Contraseña</label><input type="password" name="db_pass" value="<?= he($old['db_pass']) ?>"></div>
    </div>

    <p class="muted" style="font-size:13px;margin-bottom:4px;margin-top:20px">OMNI API CORE</p>
    <div><label>URL base de la API CORE</label><input type="text" name="api_base" value="<?= he($old['api_base']) ?>" placeholder="https://omni.josepan.es" required></div>

    <p class="muted" style="font-size:13px;margin-bottom:4px;margin-top:20px">Seguridad y roles</p>
    <div class="grid cols-2">
      <div><label>Minutos de inactividad</label><input type="text" name="idle" value="<?= he($old['idle']) ?>"></div>
      <div style="display:flex;align-items:flex-end;padding-bottom:10px"><label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="session_secure" <?= $old['session_secure']?'checked':'' ?>> Cookies solo por HTTPS</label></div>
    </div>
    <div style="margin-top:12px"><label>Roles con acceso de administración (separados por coma)</label><input type="text" name="admin_roles" value="<?= he($old['admin_roles']) ?>"></div>

    <p class="muted" style="font-size:13px;margin-bottom:4px;margin-top:20px">Modo desarrollo (acceso de prueba si la API CORE no responde)</p>
    <label style="display:flex;align-items:center;gap:8px;font-weight:500"><input type="checkbox" name="dev_mode" <?= $old['dev_mode']?'checked':'' ?>> Habilitar acceso de prueba</label>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Usuario demo</label><input type="text" name="dev_user" value="<?= he($old['dev_user']) ?>"></div>
      <div><label>Contraseña demo</label><input type="text" name="dev_pass" value="<?= he($old['dev_pass']) ?>"></div>
    </div>

    <div style="margin-top:22px">
      <button class="btn block" type="submit" <?= $reqOk ? '' : 'disabled' ?>>⚙️ Instalar y desplegar</button>
      <?php if (!$reqOk): ?><p class="muted" style="text-align:center;margin-top:10px">Corrige los puntos ⛔ del entorno para continuar.</p><?php endif; ?>
    </div>
  </form>
<?php endif; ?>

  <p class="muted" style="text-align:center;font-size:12px;margin-top:18px">Academia JOSEPAN 360 · plataforma interna de formación</p>
</div>
</body></html>
