<?php
/**
 * Cabecera autónoma para los manuales públicos. No depende de la base de datos,
 * de la configuración ni de la sesión: los manuales son visibles siempre.
 * Variables: $manualTitle, $manualActive ('tecnico' | 'usuario').
 */
if (!function_exists('mh')) {
    function mh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
require_once dirname(__DIR__) . '/includes/base.php';
$BASE = app_base_auto();
$a = $manualActive ?? '';
$instalado = file_exists(dirname(__DIR__) . '/config/installed.lock');
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<base href="<?= mh($BASE) ?>">
<meta name="theme-color" content="#642a72">
<title><?= mh($manualTitle ?? 'Manual') ?> · Academia JOSEPAN 360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head><body>
<header class="topbar" style="position:static">
  <div class="brand"><div class="logo">J</div><div><span class="name">JOSEPAN 360</span><small>Academia · Documentación</small></div></div>
  <div style="display:flex;gap:8px;align-items:center">
    <a class="btn <?= $a==='usuario'?'':'ghost' ?> sm" href="manual-usuario.php">📖 Usuario</a>
    <a class="btn <?= $a==='tecnico'?'':'ghost' ?> sm" href="manual-tecnico.html">🛠️ Técnico</a>
    <a class="btn sm" href="<?= $instalado ? 'login.php' : 'install/' ?>"><?= $instalado ? '🔒 Acceder' : '⚙️ Instalar' ?></a>
  </div>
</header>
<main class="content"><div class="container">
