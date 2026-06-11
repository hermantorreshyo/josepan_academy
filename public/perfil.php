<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$u = current_user();
$empId = (int)($u['id'] ?? 0);
$cursos = Curso::visiblesPara($empId, is_admin());

$puntosGlobales = Progreso::puntosGlobales($empId);
$nivel = Gamification::estado($puntosGlobales);
$minutos = Telemetria::minutosTotales($empId);
$modulos = Telemetria::modulosAbiertos($empId);

$pageTitle = 'Mi perfil';
$pageActive = 'perfil';
require __DIR__ . '/../templates/header.php';
?>
<div class="page-head">
  <p class="eyebrow">Mi perfil</p>
  <h1><?= e($u['nombre']) ?></h1>
  <p class="muted"><?= e($u['rol']) ?> · <?= e($u['tienda']) ?><?= $u['email'] ? ' · ' . e($u['email']) : '' ?></p>
</div>

<!-- Nivel -->
<div class="card pad" style="margin-bottom:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
    <div>
      <div class="muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.1em">Nivel de capacitación</div>
      <div style="font-family:Georgia,serif;font-size:24px;color:var(--purpura-800)">Nivel <?= $nivel['nivel'] ?> · <?= e($nivel['nivel_nombre']) ?></div>
    </div>
    <div class="stat" style="text-align:right"><b><?= $puntosGlobales ?></b><span>puntos acumulados</span></div>
  </div>
  <div class="progress" style="margin-top:14px"><i style="width:<?= $nivel['progreso'] ?>%"></i></div>
  <div class="muted" style="font-size:12px;margin-top:6px">
    <?php if ($nivel['siguiente']): ?>
      Te faltan <?= $nivel['faltan'] ?> pts para alcanzar “<?= e($nivel['siguiente']) ?>”.
    <?php else: ?>
      Has alcanzado el nivel máximo. ¡Enhorabuena!
    <?php endif; ?>
  </div>
</div>

<!-- Métricas de uso -->
<div class="stats-row" style="margin-bottom:24px">
  <div class="card pad stat"><b><?= fmt_minutos($minutos) ?></b><span>Tiempo en plataforma</span></div>
  <div class="card pad stat"><b><?= $modulos ?></b><span>Módulos abiertos</span></div>
  <div class="card pad stat"><b><?= $nivel['nivel'] ?></b><span>Nivel actual</span></div>
  <div class="card pad stat"><b><?= $puntosGlobales ?></b><span>Puntos</span></div>
</div>

<!-- Historial de formaciones -->
<div class="sec-title"><h2>Mis formaciones</h2></div>
<div class="card">
  <ul class="doclist">
  <?php foreach ($cursos as $c):
      $total = (int)$c['num_sesiones'];
      $hechas = Progreso::sesionesCompletadas($empId, $c['id']);
      $resumen = Progreso::resumenCurso($empId, $c['id']);
      $estado = $resumen['estado_aprobacion'] ?? 'pendiente';
      $completo = $hechas >= $total;
      $certificable = $completo && $estado === 'aprobado';
  ?>
    <li>
      <div class="ic">📘</div>
      <div class="info">
        <b><?= e($c['titulo']) ?></b>
        <span class="muted" style="font-size:13px"><?= $hechas ?>/<?= $total ?> sesiones · <?= (int)($resumen['puntos'] ?? 0) ?> pts</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <?php if ($estado === 'aprobado'): ?><span class="badge ok">Aprobado</span>
        <?php elseif ($estado === 'reprobado'): ?><span class="badge no">Reprobado</span>
        <?php else: ?><span class="badge pend">En curso</span><?php endif; ?>
        <?php if ($certificable): ?>
          <a class="btn horno sm" href="certificado.php?curso=<?= e($c['id']) ?>" target="_blank">⬇ Certificado</a>
        <?php endif; ?>
      </div>
    </li>
  <?php endforeach; ?>
  </ul>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
