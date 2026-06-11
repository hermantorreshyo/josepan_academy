<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cursoId = (string)($_GET['curso'] ?? '');
$curso = Curso::find($cursoId);
if (!$curso) { http_response_code(404); die('Curso no encontrado.'); }
$adminId = (int)current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cambio de visibilidad (formulario normal).
    if (($_POST['accion'] ?? '') === 'visibilidad') {
        if (!csrf_check($_POST['csrf'] ?? null)) { http_response_code(419); die('CSRF inválido'); }
        Curso::setVisibilidad($cursoId, $_POST['visibilidad'] ?? 'todos');
        redirect(url('admin/curso_asignar.php?curso=' . urlencode($cursoId)));
    }
    // Asignar / desasignar (AJAX).
    if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        json_response(['ok' => false, 'error' => 'CSRF inválido'], 419);
    }
    $in = read_json_body();
    $emp = (int)($in['empleado_id'] ?? 0);
    $asignar = !empty($in['asignar']);
    if ($emp <= 0) json_response(['ok' => false, 'error' => 'Empleado no válido'], 422);
    if ($asignar) Curso::asignar($cursoId, $emp, $adminId);
    else Curso::desasignar($cursoId, $emp);
    json_response(['ok' => true]);
}

$empleados = Empleado::all();
$asignados = array_flip(Curso::asignados($cursoId));
$csrf = csrf_token();
$pageTitle = 'Asignar curso';
$pageActive = 'cursos_admin';
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/cursos.php">← Volver a cursos</a>
<div class="page-head">
  <p class="eyebrow">Asignación de curso</p>
  <h1><?= e($curso['titulo']) ?></h1>
  <p class="muted">Define quién puede ver este curso.</p>
</div>

<!-- Visibilidad -->
<div class="card pad" style="margin-bottom:20px">
  <form method="post" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="accion" value="visibilidad">
    <div style="flex:1;min-width:240px">
      <label>Visibilidad del curso</label>
      <select name="visibilidad">
        <option value="todos" <?= ($curso['visibilidad']??'todos')==='todos'?'selected':'' ?>>Visible para todos los empleados</option>
        <option value="asignados" <?= ($curso['visibilidad']??'')==='asignados'?'selected':'' ?>>Solo empleados asignados (lista de abajo)</option>
      </select>
    </div>
    <button class="btn" type="submit">Guardar visibilidad</button>
  </form>
  <?php if (($curso['visibilidad']??'todos')==='todos'): ?>
    <p class="muted" style="font-size:13px;margin-bottom:0;margin-top:10px">Ahora mismo lo ven todos. La lista de abajo se usará si cambias a “solo asignados”.</p>
  <?php endif; ?>
</div>

<!-- Matriz de asignación -->
<div class="table-wrap" data-csrf="<?= e($csrf) ?>">
  <table>
    <thead><tr><th>Empleado</th><th>Tienda</th><th>Rol</th><th style="text-align:center">Asignado</th></tr></thead>
    <tbody>
    <?php if (!$empleados): ?>
      <tr><td colspan="4" class="muted" style="text-align:center;padding:24px">No hay empleados registrados. Aparecerán a medida que inicien sesión en la academia.</td></tr>
    <?php endif; ?>
    <?php foreach ($empleados as $emp): $eid=(int)$emp['empleado_id']; ?>
      <tr>
        <td><b><?= e($emp['nombre']) ?></b></td>
        <td><?= e($emp['tienda']) ?></td>
        <td><?= e($emp['rol']) ?></td>
        <td style="text-align:center">
          <input type="checkbox" class="js-asignar" data-emp="<?= $eid ?>" <?= isset($asignados[$eid]) ? 'checked' : '' ?>>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$inlineScript = <<<JS
(function(){
  var csrf = document.querySelector('.table-wrap').getAttribute('data-csrf');
  document.querySelectorAll('.js-asignar').forEach(function(cb){
    cb.addEventListener('change', function(){
      var prev = !cb.checked;
      cb.disabled = true;
      fetch(window.location.pathname + window.location.search, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        credentials:'same-origin',
        body:JSON.stringify({empleado_id:parseInt(cb.dataset.emp,10),asignar:cb.checked})
      }).then(function(r){return r.json();}).then(function(d){
        cb.disabled=false; if(!d||!d.ok){cb.checked=prev;}
      }).catch(function(){cb.disabled=false;cb.checked=prev;});
    });
  });
})();
JS;
require __DIR__ . '/../../templates/footer.php';
?>
