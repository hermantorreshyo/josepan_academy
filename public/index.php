<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$empId = (int)($u['id'] ?? 0);
$cursos = Curso::visiblesPara($empId, is_admin());

$pageTitle = 'Cursos';
$pageActive = 'cursos';
require __DIR__ . '/../templates/header.php';
?>
<div class="page-head">
  <p class="eyebrow">Hola<?= $u['nombre'] ? ', ' . e(explode(' ', $u['nombre'])[0]) : '' ?></p>
  <h1>Cursos disponibles</h1>
  <p class="muted">Tu itinerario formativo en JOSEPAN 360. Comienza por los cursos obligatorios.</p>
</div>

<div class="grid cols-2">
<?php if (!$cursos): ?>
  <div class="card pad muted">Aún no tienes cursos asignados. Cuando un administrador te asigne formaciones, aparecerán aquí.</div>
<?php endif; ?>
<?php foreach ($cursos as $c):
    $total = (int)$c['num_sesiones'];
    $hechas = Progreso::sesionesCompletadas($empId, $c['id']);
    $pct = $total ? (int)round($hechas / $total * 100) : 0;
?>
  <a class="card pad" href="curso.php?id=<?= e($c['id']) ?>" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:10px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div style="width:48px;height:48px;border-radius:12px;background:var(--purpura-50);color:var(--purpura-600);display:grid;place-items:center;font-size:22px">📘</div>
      <?php if (!empty($c['obligatorio'])): ?><span class="badge obl">Obligatorio</span><?php endif; ?>
    </div>
    <h2 style="font-size:18px;margin:6px 0 0"><?= e($c['titulo']) ?></h2>
    <p class="muted" style="margin:0;font-size:14px"><?= e($c['resumen']) ?></p>
    <div style="margin-top:8px">
      <div class="progress"><i style="width:<?= $pct ?>%"></i></div>
      <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:12px" class="muted">
        <span><?= $hechas ?>/<?= $total ?> sesiones · <?= (int)$c['horas'] ?> h</span>
        <span style="color:var(--purpura-600);font-weight:600">Ver curso →</span>
      </div>
    </div>
  </a>
<?php endforeach; ?>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
