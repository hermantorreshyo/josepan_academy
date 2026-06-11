<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$adminId = (int)current_user()['id'];
$error = null; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { http_response_code(419); die('CSRF inválido'); }
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $slug = strtolower(trim($_POST['curso_id'] ?? ''));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $titulo = trim($_POST['titulo'] ?? '');
        $nSes = max(1, min(30, (int)($_POST['num_sesiones'] ?? 1)));

        if ($slug === '' || $titulo === '') {
            $error = 'Identificador y título son obligatorios.';
        } elseif (Curso::existe($slug)) {
            $error = 'Ya existe un curso con ese identificador.';
        } else {
            Curso::crear([
                'curso_id' => $slug, 'titulo' => $titulo,
                'resumen' => trim($_POST['resumen'] ?? ''),
                'categoria' => trim($_POST['categoria'] ?? ''),
                'horas' => (int)($_POST['horas'] ?? 0),
                'obligatorio' => isset($_POST['obligatorio']),
                'visibilidad' => $_POST['visibilidad'] ?? 'todos',
            ]);
            for ($i = 1; $i <= $nSes; $i++) {
                Curso::crearSesion($slug, ['sesion_num' => $i, 'titulo' => 'Sesión ' . $i]);
            }
            redirect(url('admin/curso_editar.php?curso=' . urlencode($slug)));
        }
    }

    if ($accion === 'eliminar') {
        $cid = (string)($_POST['curso_id'] ?? '');
        if (Curso::existe($cid)) { Curso::eliminar($cid); $ok = 'Curso eliminado.'; }
    }
}

$cursos = Curso::all();
$csrf = csrf_token();
$pageTitle = 'Gestión de cursos';
$pageActive = 'cursos_admin';
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/index.php">← Volver al panel</a>
<div class="page-head">
  <p class="eyebrow">Gestión de cursos</p>
  <h1>Cursos de la academia</h1>
  <p class="muted">Crea, edita y asigna cursos. Cada curso puede tener cualquier número de sesiones, contenido y archivos adjuntos.</p>
</div>

<?php if ($error): ?><div class="alert err">⚠️ <span><?= e($error) ?></span></div><?php endif; ?>
<?php if ($ok): ?><div class="alert ok">✅ <span><?= e($ok) ?></span></div><?php endif; ?>

<div class="table-wrap" style="margin-bottom:26px">
  <table>
    <thead><tr><th>Curso</th><th>Categoría</th><th>Sesiones</th><th>Visibilidad</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php if (!$cursos): ?>
      <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">Aún no hay cursos. Crea el primero abajo.</td></tr>
    <?php endif; ?>
    <?php foreach ($cursos as $c): ?>
      <tr>
        <td><b><?= e($c['titulo']) ?></b><br><span class="code"><?= e($c['curso_id']) ?></span>
            <?php if (!empty($c['obligatorio'])): ?> <span class="badge obl">Obligatorio</span><?php endif; ?></td>
        <td><?= e($c['categoria']) ?></td>
        <td class="num"><?= (int)$c['num_sesiones'] ?></td>
        <td>
          <?php if (($c['visibilidad'] ?? 'todos') === 'todos'): ?>
            <span class="badge pend">Todos</span>
          <?php else: ?>
            <span class="badge ok"><?= (int)$c['num_asignados'] ?> asignados</span>
          <?php endif; ?>
        </td>
        <td style="white-space:nowrap">
          <a class="btn sm" href="admin/curso_editar.php?curso=<?= e($c['curso_id']) ?>">Editar</a>
          <a class="btn ghost sm" href="admin/curso_asignar.php?curso=<?= e($c['curso_id']) ?>">Asignar</a>
          <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar el curso y todo su contenido y progreso asociado? Esta acción no se puede deshacer.');">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="curso_id" value="<?= e($c['curso_id']) ?>">
            <button class="btn ghost sm" type="submit" style="color:var(--err)">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card pad">
  <h2 style="margin-top:0;font-size:18px">Nuevo curso</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="accion" value="crear">
    <div class="grid cols-2">
      <div><label>Título</label><input type="text" name="titulo" required placeholder="Programa de Formación..."></div>
      <div><label>Identificador (slug)</label><input type="text" name="curso_id" required placeholder="ej. atencion-cliente-2026" pattern="[A-Za-z0-9\-]+"></div>
    </div>
    <div style="margin-top:12px"><label>Resumen</label><input type="text" name="resumen" placeholder="Descripción breve del curso"></div>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Categoría</label><input type="text" name="categoria" placeholder="Liderazgo, Operaciones..."></div>
      <div><label>Horas estimadas</label><input type="text" name="horas" value="0"></div>
    </div>
    <div class="grid cols-2" style="margin-top:12px">
      <div><label>Número de sesiones</label><input type="number" name="num_sesiones" min="1" max="30" value="4"></div>
      <div><label>Visibilidad</label>
        <select name="visibilidad">
          <option value="todos">Visible para todos</option>
          <option value="asignados">Solo empleados asignados</option>
        </select>
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;margin-top:14px;font-weight:500">
      <input type="checkbox" name="obligatorio"> Marcar como curso obligatorio
    </label>
    <div style="margin-top:18px"><button class="btn" type="submit">➕ Crear curso y sesiones</button></div>
    <p class="muted" style="font-size:12px;margin-top:10px">Se crearán las sesiones vacías; luego podrás editar el contenido y subir adjuntos de cada una.</p>
  </form>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
