/* Deltha — campanita: cada data-every segundos (config.notif_poll_sec) pregunta a /avisos?n=1 cuántos avisos hay sin leer
   y actualiza la burbuja (cabecera en móvil, rail en escritorio) sin recargar la página. Solo se carga con sesión iniciada;
   se para mientras la pestaña está oculta y sin JS todo sigue igual (la burbuja sale ya pintada desde PHP). */
(function () {
  var s = document.currentScript, every = Math.max(15, parseInt(s && s.dataset.every, 10) || 60) * 1000, t = null;
  function paint(n) {
    document.querySelectorAll('a[data-bell]').forEach(function (a) {
      var b = a.querySelector('.badge'); if (!b) return;
      b.textContent = n > 99 ? '99+' : n; b.hidden = n <= 0; a.classList.toggle('has', n > 0);
    });
  }
  function ask() {
    if (document.hidden) return;
    fetch('/avisos?n=1', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (j && typeof j.n === 'number') paint(j.n); })
      .catch(function () {});
  }
  function start() { if (t) clearInterval(t); t = setInterval(ask, every); }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { ask(); start(); } });
  start();
})();
