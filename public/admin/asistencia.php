<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cursoId = $_GET['curso'] ?? Curso::primero();
$curso = Curso::find($cursoId);
if (!$curso) { $cursoId = Curso::primero(); $curso = Curso::find($cursoId); }
if (!$curso) { http_response_code(404); die('No hay cursos cargados. Ejecuta el instalador.'); }
$sesiones = $curso['sesiones'];
$adminId = (int)current_user()['id'];

// Guardado AJAX de un checkbox individual.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        json_response(['ok' => false, 'error' => 'CSRF inválido'], 419);
    }
    $in = read_json_body();
    $emp = (int)($in['empleado_id'] ?? 0);
    $ses = (int)($in['sesion_id'] ?? 0);
    $pres = !empty($in['presente']);
    if ($emp <= 0 || $ses <= 0) json_response(['ok' => false, 'error' => 'Datos incompletos'], 422);
    Asistencia::marcar($emp, $cursoId, $ses, $pres, $adminId);
    json_response(['ok' => true]);
}

$empleados = Empleado::all();

$pageTitle = 'Asistencia';
$pageActive = 'admin';
$csrf = csrf_token();
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/index.php">← Volver al panel</a>
<div class="page-head">
  <p class="eyebrow">Control de asistencia</p>
  <h1>Asistencia por sesión</h1>
  <p class="muted">Marca la asistencia individual a cada sesión. El guardado es automático.</p>
</div>

<form method="get" style="margin-bottom:16px;max-width:520px">
  <label for="curso">Curso</label>
  <select id="curso" name="curso" onchange="this.form.submit()">
    <?php foreach (Curso::all() as $c): ?>
      <option value="<?= e($c['id']) ?>" <?= $c['id']===$cursoId?'selected':'' ?>><?= e($c['titulo']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="table-wrap" data-csrf="<?= e($csrf) ?>">
  <table>
    <thead>
      <tr>
        <th>Empleado</th><th>Tienda</th>
        <?php foreach ($sesiones as $s): ?><th style="text-align:center">S<?= (int)$s['id'] ?></th><?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
    <?php if (!$empleados): ?>
      <tr><td colspan="<?= count($sesiones)+2 ?>" class="muted" style="text-align:center;padding:30px">No hay empleados registrados todavía.</td></tr>
    <?php endif; ?>
    <?php foreach ($empleados as $emp):
        $mapa = Asistencia::mapa((int)$emp['empleado_id'], $cursoId);
    ?>
      <tr>
        <td><b><?= e($emp['nombre']) ?></b><br><span class="muted" style="font-size:12px"><?= e($emp['rol']) ?></span></td>
        <td><?= e($emp['tienda']) ?></td>
        <?php foreach ($sesiones as $s): $sid=(int)$s['id']; ?>
          <td style="text-align:center">
            <input type="checkbox" class="js-asis"
                   data-emp="<?= (int)$emp['empleado_id'] ?>"
                   data-sesion="<?= $sid ?>"
                   <?= !empty($mapa[$sid]) ? 'checked' : '' ?>>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$inlineScript = <<<JS
(function(){
  var csrf = document.querySelector('.table-wrap').getAttribute('data-csrf');
  document.querySelectorAll('.js-asis').forEach(function(cb){
    cb.addEventListener('change', function(){
      var prev = !cb.checked;
      cb.disabled = true;
      fetch(window.location.pathname + window.location.search, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        credentials:'same-origin',
        body:JSON.stringify({empleado_id:parseInt(cb.dataset.emp,10),sesion_id:parseInt(cb.dataset.sesion,10),presente:cb.checked})
      }).then(function(r){return r.json();}).then(function(d){
        cb.disabled=false; if(!d||!d.ok){cb.checked=prev;}
      }).catch(function(){cb.disabled=false;cb.checked=prev;});
    });
  });
})();
JS;
require __DIR__ . '/../../templates/footer.php';
?>
