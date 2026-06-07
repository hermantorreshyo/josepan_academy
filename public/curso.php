<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = $_GET['id'] ?? '';
$curso = Curso::find($id);
if (!$curso) { http_response_code(404); die('Curso no encontrado.'); }

$u = current_user();
$empId = (int)($u['id'] ?? 0);
$total = count($curso['sesiones']);

$completadas = Progreso::sesionesIds($empId, $id);
$resumen = Progreso::resumenCurso($empId, $id);
$cursoCompleto = count($completadas) >= $total;
$aprobado = ($resumen['estado_aprobacion'] ?? '') === 'aprobado';
$certificable = $cursoCompleto && $aprobado;

/** Renderiza la guía (## títulos, - listas, **negrita**) a HTML seguro. */
function render_guia(string $md): string {
    $out = ''; $inList = false;
    $bold = fn($t) => preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e_keep($t));
    foreach (explode("\n", $md) as $line) {
        $line = rtrim($line);
        if (str_starts_with($line, '## ')) {
            if ($inList) { $out .= '</ul>'; $inList = false; }
            $out .= '<h3>' . $bold(substr($line, 3)) . '</h3>';
        } elseif (str_starts_with($line, '- ')) {
            if (!$inList) { $out .= '<ul>'; $inList = true; }
            $out .= '<li>' . $bold(substr($line, 2)) . '</li>';
        } elseif (trim($line) === '') {
            if ($inList) { $out .= '</ul>'; $inList = false; }
        } else {
            if ($inList) { $out .= '</ul>'; $inList = false; }
            $out .= '<p>' . $bold($line) . '</p>';
        }
    }
    if ($inList) $out .= '</ul>';
    return $out;
}
/** Escapa pero conserva los ** para que el bold se aplique después. */
function e_keep(string $t): string {
    return htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$pageTitle = $curso['titulo'];
$pageActive = 'cursos';
$pageScripts = ['assets/js/telemetria.js', 'assets/js/app.js'];
require __DIR__ . '/../templates/header.php';
?>
<a class="back" href="index.php">← Volver a cursos</a>

<div class="page-head"
     id="moduloTracker"
     data-curso="<?= e($curso['id']) ?>"
     data-csrf="<?= e(csrf_token()) ?>">
  <?php if (!empty($curso['obligatorio'])): ?><span class="badge obl">Curso obligatorio</span><?php endif; ?>
  <h1 style="margin-top:8px"><?= e($curso['titulo']) ?></h1>
  <p class="muted"><?= e($curso['resumen']) ?></p>
  <p class="muted" style="font-size:13px"><?= e($curso['categoria']) ?> · <?= $total ?> sesiones · <?= (int)$curso['horas'] ?> horas</p>
</div>

<?php if ($certificable): ?>
  <div class="alert ok" style="justify-content:space-between;align-items:center">
    <span>🎉 Has completado el curso y está aprobado. ¡Tu certificado está disponible!</span>
    <a class="btn horno sm" href="certificado.php?curso=<?= e($curso['id']) ?>" target="_blank">⬇ Certificado</a>
  </div>
<?php elseif ($cursoCompleto && !$aprobado): ?>
  <div class="alert" style="background:var(--purpura-50);border:1px solid var(--purpura-100)">
    ✔️ Has leído todas las sesiones. El certificado se habilitará cuando un administrador apruebe el módulo.
  </div>
<?php endif; ?>

<?php foreach ($curso['sesiones'] as $i => $s):
    $hecha = in_array((int)$s['id'], $completadas, true);
?>
  <details class="sesion" <?= $i === 0 ? 'open' : '' ?> data-sesion="<?= (int)$s['id'] ?>">
    <summary>
      <span class="num"><?= (int)$s['id'] ?></span>
      <span class="ttl"><b><?= e($s['titulo']) ?></b><span><?= e($s['subtitulo']) ?></span></span>
      <span class="chk" data-check="<?= (int)$s['id'] ?>" style="<?= $hecha ? '' : 'display:none' ?>">✓</span>
    </summary>
    <div class="body">
      <div class="video">
        <?php if (!empty($s['video'])): ?>
          <iframe src="<?= e($s['video']) ?>" title="Video sesión <?= (int)$s['id'] ?>" style="width:100%;height:100%;border:0;border-radius:12px" allowfullscreen></iframe>
        <?php else: ?>
          <div style="text-align:center"><div style="font-size:32px">▶</div><div style="font-size:13px">Videocapacitación próximamente</div></div>
        <?php endif; ?>
      </div>

      <div class="guia"><?= render_guia($s['guia']) ?></div>

      <?php if (!empty($s['materiales'])): ?>
        <div style="background:var(--purpura-50);border-radius:10px;padding:12px 14px;margin-top:14px">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--purpura-500);margin-bottom:8px">Material de apoyo</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach ($s['materiales'] as $m): ?>
              <a class="code" style="background:#fff;border:1px solid var(--linea);border-radius:7px;padding:4px 8px" href="biblioteca.php?q=<?= e($m) ?>"><?= e($m) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div style="margin-top:18px">
        <button class="btn sm js-completar" data-sesion="<?= (int)$s['id'] ?>" <?= $hecha ? 'disabled' : '' ?>>
          <?= $hecha ? '✓ Sesión completada' : 'Marcar como leída (+' . PUNTOS_POR_SESION . ' pts)' ?>
        </button>
      </div>
    </div>
  </details>
<?php endforeach; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
