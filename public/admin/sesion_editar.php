<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cursoId = (string)($_GET['curso'] ?? '');
$num = (int)($_GET['n'] ?? 0);
if (!Curso::existe($cursoId)) { http_response_code(404); die('Curso no encontrado.'); }
$sesion = Curso::sesion($cursoId, $num);
if (!$sesion) { http_response_code(404); die('Sesión no encontrada.'); }
$adminId = (int)current_user()['id'];
$ok = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { http_response_code(419); die('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $mats = array_values(array_filter(array_map('trim', explode(',', $_POST['materiales'] ?? ''))));
        Curso::actualizarSesion($cursoId, $num, [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'subtitulo' => trim($_POST['subtitulo'] ?? ''),
            'video' => trim($_POST['video'] ?? ''),
            'guia' => $_POST['guia'] ?? '',
            'materiales' => $mats,
        ]);
        redirect(url('admin/sesion_editar.php?curso=' . urlencode($cursoId) . '&n=' . $num . '&ok=1'));
    }

    if ($accion === 'subir' && !empty($_FILES['adjunto'])) {
        $res = Adjunto::guardar($cursoId, $num, $_FILES['adjunto'], $adminId);
        if ($res['ok']) redirect(url('admin/sesion_editar.php?curso=' . urlencode($cursoId) . '&n=' . $num . '&ok=2'));
        $error = $res['error'] ?? 'No se pudo subir el archivo.';
    }

    if ($accion === 'del_adjunto') {
        Adjunto::eliminar((int)($_POST['adjunto_id'] ?? 0));
        redirect(url('admin/sesion_editar.php?curso=' . urlencode($cursoId) . '&n=' . $num));
    }
}

$sesion = Curso::sesion($cursoId, $num); // refrescar
$materiales = json_decode($sesion['materiales'] ?? '[]', true) ?: [];
$adjuntos = Adjunto::listar($cursoId, $num);
if (($_GET['ok'] ?? '') === '1') $ok = 'Contenido guardado.';
if (($_GET['ok'] ?? '') === '2') $ok = 'Archivo subido.';
$csrf = csrf_token();
$pageTitle = 'Editar sesión';
$pageActive = 'cursos_admin';
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/curso_editar.php?curso=<?= e($cursoId) ?>">← Volver al curso</a>
<div class="page-head">
  <p class="eyebrow">Sesión <?= $num ?></p>
  <h1><?= e($sesion['titulo']) ?></h1>
</div>

<?php if ($ok): ?><div class="alert ok">✅ <span><?= e($ok) ?></span></div><?php endif; ?>
<?php if ($error): ?><div class="alert err">⚠️ <span><?= e($error) ?></span></div><?php endif; ?>

<!-- Contenido -->
<div class="card pad" style="margin-bottom:22px">
  <h2 style="margin-top:0;font-size:17px">Contenido de la sesión</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="accion" value="guardar">
    <div class="grid cols-2">
      <div><label>Título</label><input type="text" name="titulo" value="<?= e($sesion['titulo']) ?>" required></div>
      <div><label>Subtítulo</label><input type="text" name="subtitulo" value="<?= e($sesion['subtitulo'] ?? '') ?>"></div>
    </div>
    <div style="margin-top:12px"><label>URL del video corporativo (opcional)</label>
      <input type="text" name="video" value="<?= e($sesion['video'] ?? '') ?>" placeholder="https://...">
    </div>
    <div style="margin-top:12px">
      <label>Guía metodológica</label>
      <textarea name="guia" rows="14" style="width:100%;padding:10px 12px;border:1px solid var(--linea);border-radius:10px;font:inherit;font-family:var(--mono);font-size:13px;line-height:1.5"><?= e($sesion['guia'] ?? '') ?></textarea>
      <p class="muted" style="font-size:12px;margin-top:6px">Formato: <code>## Título</code> para encabezados, <code>- texto</code> para viñetas y <code>**negrita**</code>.</p>
    </div>
    <div style="margin-top:12px"><label>Material de apoyo (códigos de biblioteca, separados por coma)</label>
      <input type="text" name="materiales" value="<?= e(implode(', ', $materiales)) ?>" placeholder="MN-RRHH-001, FR-SGI-042">
    </div>
    <div style="margin-top:16px"><button class="btn" type="submit">💾 Guardar contenido</button></div>
  </form>
</div>

<!-- Adjuntos -->
<div class="card pad">
  <h2 style="margin-top:0;font-size:17px">Archivos adjuntos de la sesión</h2>

  <?php if ($adjuntos): ?>
    <ul class="doclist" style="margin:0 -22px 14px">
      <?php foreach ($adjuntos as $a): ?>
        <li>
          <div class="ic">📎</div>
          <div class="info"><b><?= e($a['nombre']) ?></b>
            <span class="muted" style="font-size:12px"><?= number_format($a['tamano']/1024, 0) ?> KB · <?= e($a['archivo']) ?></span>
          </div>
          <a class="btn ghost sm" href="adjunto.php?id=<?= (int)$a['id'] ?>" target="_blank">Descargar</a>
          <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar este adjunto?');">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="del_adjunto">
            <input type="hidden" name="adjunto_id" value="<?= (int)$a['id'] ?>">
            <button class="btn ghost sm" type="submit" style="color:var(--err)">Eliminar</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="muted">Esta sesión todavía no tiene archivos adjuntos.</p>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="margin-top:8px">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="accion" value="subir">
    <label>Subir archivo (máx. 20 MB)</label>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <input type="file" name="adjunto" required style="flex:1;min-width:220px">
      <button class="btn horno" type="submit">⬆ Subir adjunto</button>
    </div>
    <p class="muted" style="font-size:12px;margin-top:8px">Permitidos: PDF, Word, Excel, PowerPoint, imágenes, TXT, CSV, ZIP.</p>
  </form>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
