<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

// Reporte agregado por empleado.
$sql = "
  SELECT e.empleado_id, e.nombre, e.rol, e.tienda, e.ultimo_acceso,
         COALESCE(t.modulos, 0)  AS modulos,
         COALESCE(t.segundos, 0) AS segundos,
         COALESCE(p.puntos, 0)   AS puntos
  FROM empleados e
  LEFT JOIN (
      SELECT empleado_id, COUNT(*) AS modulos, SUM(segundos_activos) AS segundos
      FROM telemetria_tiempos GROUP BY empleado_id
  ) t ON t.empleado_id = e.empleado_id
  LEFT JOIN (
      SELECT empleado_id, SUM(puntos) AS puntos
      FROM cursos_puntuacion GROUP BY empleado_id
  ) p ON p.empleado_id = e.empleado_id
  ORDER BY puntos DESC, e.nombre ASC
";
$rows = Database::run($sql)->fetchAll();

$totEmpleados = count($rows);
$totMin = 0; $totModulos = 0;
foreach ($rows as $r) { $totMin += (int)floor($r['segundos'] / 60); $totModulos += (int)$r['modulos']; }

$pageTitle = 'Administración';
$pageActive = 'admin';
require __DIR__ . '/../../templates/header.php';
?>
<div class="page-head">
  <p class="eyebrow">Panel de administración</p>
  <h1>Dashboard de reportes</h1>
  <p class="muted">Actividad formativa de la red JOSEPAN 360.</p>
</div>

<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
  <a class="btn ghost sm" href="admin/asistencia.php">✅ Control de asistencia</a>
  <a class="btn ghost sm" href="admin/aprobacion.php">🎓 Aprobación de módulos</a>
</div>

<div class="stats-row" style="margin-bottom:22px">
  <div class="card pad stat"><b><?= $totEmpleados ?></b><span>Empleados activos</span></div>
  <div class="card pad stat"><b><?= $totModulos ?></b><span>Módulos abiertos</span></div>
  <div class="card pad stat"><b><?= fmt_minutos($totMin) ?></b><span>Tiempo total red</span></div>
  <div class="card pad stat"><b><?= count(Gamification::niveles()) ?></b><span>Niveles definidos</span></div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Empleado</th><th>Tienda</th><th>Rol</th>
        <th>Módulos</th><th>Tiempo</th><th>Puntos</th><th>Nivel</th><th>Último acceso</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="8" class="muted" style="text-align:center;padding:30px">Aún no hay actividad registrada. Los empleados aparecerán al iniciar sesión.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r):
        $min = (int)floor($r['segundos'] / 60);
        $nivel = Gamification::estado((int)$r['puntos']);
    ?>
      <tr>
        <td><b><?= e($r['nombre']) ?></b></td>
        <td><?= e($r['tienda']) ?></td>
        <td><?= e($r['rol']) ?></td>
        <td class="num"><?= (int)$r['modulos'] ?></td>
        <td class="num"><?= fmt_minutos($min) ?></td>
        <td class="num"><?= (int)$r['puntos'] ?></td>
        <td><span class="badge pend">N<?= $nivel['nivel'] ?> · <?= e($nivel['nivel_nombre']) ?></span></td>
        <td class="muted"><?= $r['ultimo_acceso'] ? e(date('d/m/Y H:i', strtotime($r['ultimo_acceso']))) : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../templates/footer.php'; ?>
