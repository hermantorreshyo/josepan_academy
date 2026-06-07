<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$cursoId = $_GET['curso'] ?? Curso::primero();
$curso = Curso::find($cursoId);
if (!$curso) { $cursoId = Curso::primero(); $curso = Curso::find($cursoId); }
if (!$curso) { http_response_code(404); die('No hay cursos cargados. Ejecuta el instalador.'); }
$total = count($curso['sesiones']);
$adminId = (int)current_user()['id'];

// POST: cambia el estado de aprobación de un empleado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { http_response_code(419); die('CSRF inválido'); }
    $emp = (int)($_POST['empleado_id'] ?? 0);
    $estado = (string)($_POST['estado'] ?? 'pendiente');
    if ($emp > 0) {
        Progreso::setAprobacion($emp, $cursoId, $estado, $adminId, $total);
    }
    redirect(url('admin/aprobacion.php?curso=' . urlencode($cursoId)));
}

$empleados = Empleado::all();
$csrf = csrf_token();

$pageTitle = 'Aprobación';
$pageActive = 'admin';
require __DIR__ . '/../../templates/header.php';
?>
<a class="back" href="admin/index.php">← Volver al panel</a>
<div class="page-head">
  <p class="eyebrow">Aprobación de módulos</p>
  <h1>Calificación del curso</h1>
  <p class="muted">Tras evaluar el desempeño en tienda, marca el módulo como aprobado o reprobado.</p>
</div>

<form method="get" style="margin-bottom:16px;max-width:520px">
  <label for="curso">Curso</label>
  <select id="curso" name="curso" onchange="this.form.submit()">
    <?php foreach (Curso::all() as $c): ?>
      <option value="<?= e($c['id']) ?>" <?= $c['id']===$cursoId?'selected':'' ?>><?= e($c['titulo']) ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Empleado</th><th>Tienda</th><th>Sesiones</th><th>Estado</th><th>Acción</th></tr>
    </thead>
    <tbody>
    <?php if (!$empleados): ?>
      <tr><td colspan="5" class="muted" style="text-align:center;padding:30px">No hay empleados registrados todavía.</td></tr>
    <?php endif; ?>
    <?php foreach ($empleados as $emp):
        $eid = (int)$emp['empleado_id'];
        $hechas = Progreso::sesionesCompletadas($eid, $cursoId);
        $resumen = Progreso::resumenCurso($eid, $cursoId);
        $estado = $resumen['estado_aprobacion'] ?? 'pendiente';
        $completo = $hechas >= $total;
    ?>
      <tr>
        <td><b><?= e($emp['nombre']) ?></b><br><span class="muted" style="font-size:12px"><?= e($emp['rol']) ?></span></td>
        <td><?= e($emp['tienda']) ?></td>
        <td class="num"><?= $hechas ?>/<?= $total ?> <?= $completo ? '✓' : '' ?></td>
        <td>
          <?php if ($estado==='aprobado'): ?><span class="badge ok">Aprobado</span>
          <?php elseif ($estado==='reprobado'): ?><span class="badge no">Reprobado</span>
          <?php else: ?><span class="badge pend">Pendiente</span><?php endif; ?>
        </td>
        <td>
          <form method="post" style="display:flex;gap:6px;align-items:center">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="empleado_id" value="<?= $eid ?>">
            <select name="estado">
              <option value="pendiente" <?= $estado==='pendiente'?'selected':'' ?>>Pendiente</option>
              <option value="aprobado"  <?= $estado==='aprobado'?'selected':'' ?>>Aprobado</option>
              <option value="reprobado" <?= $estado==='reprobado'?'selected':'' ?>>Reprobado</option>
            </select>
            <button class="btn sm" type="submit">Guardar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
