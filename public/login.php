<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) redirect(url('index.php'));

$error = null;
$next  = $_GET['next'] ?? ($_POST['next'] ?? url('index.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Sesión de formulario no válida. Recarga la página.';
    } else {
        $usuario  = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($usuario === '' || $password === '') {
            $error = 'Introduce usuario y contraseña.';
        } else {
            // Autenticación centralizada vía SDK del OMNI API CORE [1001].
            $omni = new OmniCoreClient(API_CORE_BASE, API_PREFIX, API_TIMEOUT);
            $res = $omni->login($usuario, $password);
            if ($res['ok']) {
                establish_session($res['token'], $res['user'], $res['permissions'] ?? []);
                redirect(str_starts_with($next, '/') ? $next : url('index.php'));
            } else {
                $error = $res['error'] ?? 'No se pudo iniciar sesión.';
            }
        }
    }
}
$token = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<base href="<?= e(APP_BASE) ?>">
<meta name="theme-color" content="#642a72">
<title>Acceso · Academia JOSEPAN 360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="login">
  <div class="hero">
    <div class="brand"><div class="logo">J</div><div><span class="name" style="color:#fff;font-size:16px">JOSEPAN 360</span><small>Academia Interna</small></div></div>
    <div>
      <h1>Formación que convierte encargados en líderes.</h1>
      <p>Cursos, guías metodológicas y documentación oficial de JOSEPAN 360, disponibles desde cualquier punto de venta.</p>
    </div>
    <p style="color:#b79cc2;font-size:12px">Plataforma interna · acceso exclusivo para personal autorizado</p>
  </div>

  <div class="panel">
    <div class="box">
      <div class="brand" style="margin-bottom:24px"><div class="logo">J</div><div><span class="name">JOSEPAN 360</span><small>Academia</small></div></div>
      <h2 style="font-size:22px;margin:0 0 4px">Iniciar sesión</h2>
      <p class="muted" style="margin:0 0 18px">Introduce tus credenciales corporativas.</p>

      <?php if ($error): ?>
        <div class="alert err">⚠️ <span><?= e($error) ?></span></div>
      <?php endif; ?>

      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= e($token) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div style="margin-bottom:14px">
          <label for="usuario">Usuario o correo</label>
          <input id="usuario" name="usuario" type="text" autocomplete="username" placeholder="nombre@josepan360.com" required autofocus>
        </div>
        <div style="margin-bottom:18px">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required>
        </div>
        <button class="btn block" type="submit">🔒 Acceder</button>
      </form>
      <p class="muted" style="text-align:center;font-size:12px;margin-top:18px">¿Problemas para acceder? Contacta con Coordinación de Operaciones.</p>
      <p style="text-align:center;font-size:12px;margin-top:10px">
        <a href="manual-usuario.php">📖 Manual de usuario</a> · <a href="manual-tecnico.html">🛠️ Manual técnico</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>
