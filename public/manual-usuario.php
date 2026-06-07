<?php
/**
 * Manual de usuario — PÚBLICO. Visible siempre, esté o no instalada la plataforma.
 */
$manualTitle = 'Manual de usuario';
$manualActive = 'usuario';
require __DIR__ . '/../templates/manual_header.php';

$vista = ($_GET['rol'] ?? 'empleado') === 'admin' ? 'admin' : 'empleado';
?>
<div class="page-head">
  <p class="eyebrow">Centro de ayuda</p>
  <h1>Manual de usuario</h1>
  <p class="muted">Guía paso a paso de la Academia JOSEPAN 360.</p>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px">
  <a class="btn <?= $vista==='empleado'?'':'ghost' ?> sm" href="manual-usuario.php?rol=empleado">👤 Para el empleado</a>
  <a class="btn <?= $vista==='admin'?'':'ghost' ?> sm" href="manual-usuario.php?rol=admin">🛠️ Para el administrador</a>
</div>

<div class="prose">
<?php if ($vista === 'empleado'): ?>
  <h2>1. Acceso a la plataforma</h2>
  <p>Entra con tu <strong>usuario o correo corporativo</strong> y tu contraseña. Son las mismas credenciales del resto de sistemas JOSEPAN: la academia valida tu identidad contra el <strong>OMNI API CORE</strong>, no guarda contraseñas. Si tu sesión queda inactiva, se cerrará automáticamente por seguridad.</p>

  <h2>2. Hacer un curso</h2>
  <ul>
    <li>En <strong>Cursos</strong> verás tus formaciones. Las marcadas como <em>Obligatorio</em> son prioritarias.</li>
    <li>Abre un curso para ver sus sesiones en orden, con el video corporativo y la guía metodológica.</li>
    <li>Al terminar de leer una sesión, pulsa <strong>“Marcar como leída”</strong>: sumas puntos y se marca con un ✓.</li>
    <li>La plataforma registra automáticamente el tiempo que dedicas; no tienes que hacer nada.</li>
  </ul>

  <h2>3. Tu perfil y niveles</h2>
  <p>En <strong>Mi perfil</strong> ves tu <strong>Nivel de Capacitación</strong>, los puntos acumulados, el tiempo total en la plataforma y el historial de formaciones. Cada sesión leída te acerca al siguiente nivel.</p>

  <h2>4. Certificado</h2>
  <p>Cuando completes el 100% de las sesiones de un curso <strong>y</strong> un administrador marque el módulo como <em>Aprobado</em>, aparecerá el botón <strong>⬇ Certificado</strong> en el curso y en tu perfil para descargar tu Certificado de Finalización en PDF.</p>

  <h2>5. Biblioteca de documentos</h2>
  <p>En <strong>Biblioteca</strong> encuentras manuales de funciones, el Reglamento Interno y material de apoyo. Busca por cargo o código (p. ej. <code>MN-RRHH-001</code>) y pulsa <strong>⬇ PDF</strong>. Las descargas requieren tu sesión activa.</p>
<?php else: ?>
  <h2>1. Acceso al panel</h2>
  <p>Los perfiles cuyo rol en OMNI sea <strong>Director, RRHH, Administrador o Coordinación</strong> (o que tengan el permiso <code>academia.admin</code>) ven la sección <strong>Administración</strong>.</p>

  <h2>2. Dashboard de reportes</h2>
  <p>En <strong>Administración → Reportes</strong> ves, por empleado: módulos abiertos, tiempo total acumulado, puntos y nivel. Es la foto de actividad de toda la red.</p>

  <h2>3. Control de asistencia</h2>
  <ul>
    <li>Entra en <strong>Asistencia</strong> y elige el curso.</li>
    <li>Matriz de empleados con un checkbox por sesión (1, 2, 3, 4).</li>
    <li>Marca la asistencia individual a cada sesión; el guardado es inmediato.</li>
  </ul>

  <h2>4. Aprobación del módulo</h2>
  <p>En <strong>Aprobación</strong>, tras evaluar el desempeño en tienda, marca a cada empleado como <strong>Aprobado</strong> o <strong>Reprobado</strong>. Aprobar suma puntos extra y, junto al 100% de sesiones leídas, habilita el certificado.</p>

  <h2>5. Instalación de la plataforma</h2>
  <p>La primera puesta en marcha se hace desde <code>/install/</code>: el asistente solicita los datos de la base de datos local y la URL del OMNI API CORE, despliega el esquema automáticamente y deja la plataforma operativa. Recuerda eliminar el directorio <code>install/</code> tras instalar.</p>
<?php endif; ?>
</div>

<?php require __DIR__ . '/../templates/manual_footer.php'; ?>
