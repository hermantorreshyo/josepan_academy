<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$docs = require __DIR__ . '/../data/documentos.php';
$q = trim((string)($_GET['q'] ?? ''));
$ql = mb_strtolower($q);

// Filtra por título, código o cargo.
$filtrados = array_filter($docs, function ($d) use ($ql) {
    if ($ql === '') return true;
    foreach (['titulo', 'codigo', 'cargo', 'version'] as $k) {
        if (!empty($d[$k]) && str_contains(mb_strtolower($d[$k]), $ql)) return true;
    }
    return false;
});

// Agrupa por categoría en orden fijo.
$orden = ['Manuales de Funciones', 'Reglamento Interno', 'Material de Apoyo'];
$grupos = [];
foreach ($filtrados as $d) $grupos[$d['categoria']][] = $d;
uksort($grupos, fn($a, $b) => array_search($a, $orden) <=> array_search($b, $orden));

$pageTitle = 'Biblioteca';
$pageActive = 'biblioteca';
require __DIR__ . '/../templates/header.php';
?>
<div class="page-head">
  <h1>Biblioteca de documentos</h1>
  <p class="muted">Documentación oficial. Las descargas están protegidas por tu sesión activa.</p>
</div>

<form method="get" style="margin-bottom:18px">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por cargo, código (p. ej. MN-RRHH-001) o título…">
</form>

<?php if (empty($grupos)): ?>
  <p class="muted" style="text-align:center;padding:40px 0">No hay documentos que coincidan con la búsqueda.</p>
<?php endif; ?>

<?php foreach ($grupos as $cat => $items): ?>
  <div class="sec-title">📁 <h2><?= e($cat) ?></h2><span class="count"><?= count($items) ?></span></div>
  <div class="card">
    <ul class="doclist">
      <?php foreach ($items as $d): ?>
        <li>
          <div class="ic">📄</div>
          <div class="info">
            <b><?= e($d['titulo']) ?></b>
            <span style="font-size:12px">
              <span class="code"><?= e($d['codigo']) ?></span>
              <?php if (!empty($d['version'])): ?><span class="muted"> · <?= e($d['version']) ?></span><?php endif; ?>
              <?php if (!empty($d['cargo'])): ?><span class="muted"> · <?= e($d['cargo']) ?></span><?php endif; ?>
            </span>
          </div>
          <a class="btn ghost sm" href="descargar.php?doc=<?= e($d['codigo']) ?>">⬇ PDF</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../templates/footer.php'; ?>
