<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cursoId = (string)($_GET['curso'] ?? '');
$curso = Curso::find($cursoId);
if (!$curso) { http_response_code(404); die('Curso no encontrado.'); }
$adminId = (int)current_user()['id'];
$ok = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { http_response_code(419); die('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'meta') {
        Curso::actualizar($cursoId, [
            'titulo' => trim($_POST['titulo'] ?? $curso['titulo']),
            'resumen' => trim($_POST['resumen'] ?? ''),
            'categoria' => trim($_POST['categoria'] ?? ''),
            'horas' => (int)($_POST['horas'] ?? 0),
            'obligatorio' => isset($_POST['obligatorio']),
            'visibilidad' => $_POST['visibilidad'] ?? 'todos',
        ]);
        redirect(url('admin/curso_editar.php?curso=' . urlencode($cursoId) . '&ok=1'));
    }
    if ($accion === 'add_sesion') {
        $num = Curso::siguienteSesionNum($cursoId);
        Curso::crearSesion($cursoId, ['sesion_num' => $num, 'titulo' => 'Sesión ' . $num]);
        redirect(url('admin/sesion_editar.php?curso=' . urlencode($cursoId) . '&n=' . $num));
    }
    if ($accion === 'del_sesion') {
        Curso::eliminarSesion($cursoId, (int)($_POST['sesion_num'] ?? 0));
        redirect(url('admin/curso_editar.php?curso=' . urlencode($cursoId)));
    }
}

$curso = Curso::find($cursoId); // refrescar
if (isset($_GET['ok'])) $ok = 'Cambios guardados.';
$csrf = csrf_token();
$pageTitle = 'Editar curso';
$pageActive = 'cursos_admin';
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/cursos.php">← Volver a cursos</a>
<div class="page-head">
  <p class="eyebrow">Editar curso</p>
  <h1><?= e($curso['titulo']) ?></h1>
  <p class="muted"><span class="code"><?= e($curso['curso_id']) ?></span> · <?= count($curso['sesiones']) ?> sesiones</p>
</div>

<?php if ($ok): ?><div class="alert ok">✅ <span><?= e($ok) ?></span></div><?php endif; ?>

<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
  <a class="btn ghost sm" href="admin/curso_asignar.php?curso=<?= e($curso['curso_id']) ?>">👥 Asignar empleados</a>
  <a class="btn ghost sm" href="curso.php?id=<?= e($curso['curso_id']) ?>" target="_blank">👁 Ver como empleado</a>
</div>

<!-- Metadatos -->
<div class="card pad" style="margin-bottom:22px">
  <h2 style="margin-top:0;font-size:17px">Datos del curso</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="accion" value="meta">
    <div><label>Título</label><input type="text" name="titulo" value="<?= e($curso['titulo']) ?>" required></div>
    <div style="margin-top:12px"><label>Resumen</label><input type="text" name="resumen" value="<?= e($curso['resumen'] ?? '') ?>"></div>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Categoría</label><input type="text" name="categoria" value="<?= e($curso['categoria'] ?? '') ?>"></div>
      <div><label>Horas estimadas</label><input type="text" name="horas" value="<?= (int)($curso['horas'] ?? 0) ?>"></div>
    </div>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Visibilidad</label>
        <select name="visibilidad">
          <option value="todos" <?= ($curso['visibilidad']??'todos')==='todos'?'selected':'' ?>>Visible para todos</option>
          <option value="asignados" <?= ($curso['visibilidad']??'')==='asignados'?'selected':'' ?>>Solo empleados asignados</option>
        </select>
      </div>
      <div style="display:flex;align-items:flex-end;padding-bottom:10px">
        <label style="display:flex;align-items:center;gap:8px;margin:0"><input type="checkbox" name="obligatorio" <?= !empty($curso['obligatorio'])?'checked':'' ?>> Obligatorio</label>
      </div>
    </div>
    <div style="margin-top:16px"><button class="btn" type="submit">💾 Guardar datos</button></div>
  </form>
</div>

<!-- Sesiones -->
<div class="sec-title"><h2>Sesiones</h2><span class="count"><?= count($curso['sesiones']) ?></span></div>
<div class="card" style="margin-bottom:16px">
  <ul class="doclist">
    <?php if (!$curso['sesiones']): ?>
      <li class="muted">Sin sesiones todavía. Añade la primera abajo.</li>
    <?php endif; ?>
    <?php foreach ($curso['sesiones'] as $s):
        $nAdj = count(Adjunto::listar($curso['curso_id'], (int)$s['id']));
    ?>
      <li>
        <div class="ic"><?= (int)$s['id'] ?></div>
        <div class="info">
          <b><?= e($s['titulo']) ?></b>
          <span class="muted" style="font-size:12px"><?= e($s['subtitulo'] ?: 'Sin subtítulo') ?> · <?= $nAdj ?> adjunto(s)</span>
        </div>
        <a class="btn sm" href="admin/sesion_editar.php?curso=<?= e($curso['curso_id']) ?>&n=<?= (int)$s['id'] ?>">Editar</a>
        <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar esta sesión y sus adjuntos?');">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="accion" value="del_sesion">
          <input type="hidden" name="sesion_num" value="<?= (int)$s['id'] ?>">
          <button class="btn ghost sm" type="submit" style="color:var(--err)">Eliminar</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<form method="post">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="accion" value="add_sesion">
  <button class="btn horno" type="submit">➕ Añadir sesión</button>
</form>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
