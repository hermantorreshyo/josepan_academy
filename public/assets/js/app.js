/** Interacciones de la vista de curso (JS Vanilla). */
(function () {
  var tracker = document.getElementById('moduloTracker');
  if (!tracker) return;
  var cursoId = tracker.getAttribute('data-curso');
  var csrf = tracker.getAttribute('data-csrf');

  document.querySelectorAll('.js-completar').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sesion = parseInt(btn.getAttribute('data-sesion'), 10);
      btn.disabled = true;
      btn.textContent = 'Guardando…';

      fetch('api/complete_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        credentials: 'same-origin',
        body: JSON.stringify({ curso_id: cursoId, sesion_id: sesion })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data || !data.ok) {
            btn.disabled = false;
            btn.textContent = 'Reintentar';
            return;
          }
          btn.textContent = '✓ Sesión completada';
          var chk = document.querySelector('[data-check="' + sesion + '"]');
          if (chk) chk.style.display = '';

          // Si el curso quedó completo y aprobado, recarga para mostrar el certificado.
          if (data.progreso && data.progreso.certificable) {
            window.location.reload();
          }
        })
        .catch(function () {
          btn.disabled = false;
          btn.textContent = 'Reintentar';
        });
    });
  });
})();
