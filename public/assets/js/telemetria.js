/**
 * Telemetría de uso (JS Vanilla, sin dependencias).
 * - Registra la apertura del módulo (track_open) al cargar.
 * - Acumula el tiempo ACTIVO (solo cuando la pestaña está visible) y lo envía
 *   con heartbeats cada 30 s, en visibilitychange y en beforeunload (sendBeacon).
 */
(function () {
  var tracker = document.getElementById('moduloTracker');
  if (!tracker) return;

  var cursoId = tracker.getAttribute('data-curso');
  var csrf = tracker.getAttribute('data-csrf');
  var telemetriaId = null;

  var PING_MS = 30000;       // 30 segundos
  var lastTick = Date.now(); // marca para calcular el delta activo
  var pendientes = 0;        // segundos activos aún no enviados
  var visible = (document.visibilityState === 'visible');

  function post(url, payload, useBeacon) {
    var body = JSON.stringify(payload);
    if (useBeacon && navigator.sendBeacon) {
      navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
      return Promise.resolve(null);
    }
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: body,
      keepalive: true,
      credentials: 'same-origin'
    }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
  }

  // 1) Apertura del módulo.
  post('api/track_open.php', { curso_id: cursoId, sesion_id: null }).then(function (data) {
    if (data && data.ok) telemetriaId = data.id;
  });

  // Acumula el tiempo transcurrido desde el último tick si la pestaña está visible.
  function acumular() {
    var now = Date.now();
    if (visible) {
      pendientes += Math.round((now - lastTick) / 1000);
    }
    lastTick = now;
  }

  // Envía los segundos pendientes al servidor.
  function flush(useBeacon) {
    acumular();
    if (!telemetriaId || pendientes <= 0) return;
    var seg = pendientes;
    pendientes = 0;
    post('api/track_ping.php', { id: telemetriaId, segundos: seg }, useBeacon);
  }

  // 2) Heartbeat periódico.
  setInterval(function () { flush(false); }, PING_MS);

  // 3) Cambios de visibilidad: al ocultar, vacía; al volver, reinicia el tick.
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      flush(true);
      visible = false;
    } else {
      visible = true;
      lastTick = Date.now();
    }
  });

  // 4) Salida de la página: envío final fiable vía beacon.
  window.addEventListener('beforeunload', function () { flush(true); });
  window.addEventListener('pagehide', function () { flush(true); });
})();
