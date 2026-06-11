<?php
/**
 * Cabecera común. Espera que $bootstrap ya esté incluido y require_login() hecho.
 * Variable opcional: $pageActive ('cursos' | 'biblioteca' | 'perfil' | 'admin').
 */
$u = current_user();
$active = $pageActive ?? '';
$inicial = strtoupper(mb_substr($u['nombre'] ?? '?', 0, 1));
function navlink($key, $href, $icon, $label, $active) {
    $cls = $active === $key ? 'active' : '';
    echo "<a class=\"$cls\" href=\"$href\"><span aria-hidden=\"true\">$icon</span> " . e($label) . "</a>";
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<base href="<?= e(APP_BASE) ?>">
<meta name="theme-color" content="#642a72">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>Academia JOSEPAN 360</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Franklin:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app">
  <header class="topbar">
    <div class="brand mobile-only">
      <div class="logo">J</div>
      <div><span class="name">JOSEPAN 360</span><small>Academia</small></div>
    </div>
    <div class="userchip">
      <div class="meta">
        <b><?= e($u['nombre'] ?? '') ?></b>
        <span><?= e($u['tienda'] ?? '') ?> · <?= e($u['rol'] ?? '') ?></span>
      </div>
      <div class="av"><?= e($inicial) ?></div>
    </div>
  </header>

  <div class="shell">
    <nav class="sidebar">
      <div class="logo-wrap brand">
        <div class="logo">J</div>
        <div><span class="name" style="color:#fff">JOSEPAN 360</span><small>Academia Interna</small></div>
      </div>
      <?php
        navlink('cursos', 'index.php', '📚', 'Cursos', $active);
        navlink('biblioteca', 'biblioteca.php', '📁', 'Biblioteca', $active);
        navlink('perfil', 'perfil.php', '🎖️', 'Mi perfil', $active);
        if (is_admin()) navlink('admin', 'admin/index.php', '📊', 'Administración', $active);
        if (is_admin()) navlink('cursos_admin', 'admin/cursos.php', '🗂️', 'Gestión de cursos', $active);
        if (is_tech())  navlink('docs', 'manual-tecnico.html', '🛠️', 'Manual técnico', $active);
      ?>
      <a href="manual-usuario.php"><span aria-hidden="true">❓</span> Ayuda</a>
      <div class="sp"></div>
      <a href="logout.php"><span aria-hidden="true">⎋</span> Cerrar sesión</a>
    </nav>

    <main class="content">
      <div class="container">
