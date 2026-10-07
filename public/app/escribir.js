/* Deltha — el cuadro de escribir. Dos cosas pequeñas, sin dependencias:

   1) Cursor al final. Cuando el cuadro llega con texto puesto («#12 » al responder en el Chat), el navegador
      deja el cursor delante y lo escrito acaba antes de la cita. Se coloca al final una sola vez, al cargar;
      después no se toca, para que al pulsar en medio del texto el cursor caiga donde la persona pulsa.

   2) Refresco del Chat sin perder lo escrito. La página se recarga sola cada N segundos (el elemento con
      data-refresh dice cuántos y data-refresh-url adónde: la misma página con auto=1, como el meta, para que
      la analítica no lo cuente como visita nueva), pero en cuanto hay algo escrito el temporizador se cancela;
      si el cuadro vuelve a quedarse vacío, se programa de nuevo. Tampoco recarga con la pestaña oculta.
      Sin JS no hay temporizador: de eso se encarga el <noscript><meta refresh> del <head>, como siempre.

   3) El refresco no mueve la lectura (07/10/2026). Antes la recarga volvía arriba del todo y quien leía un hilo
      lo perdía. Ahora, antes de recargar, se apunta el mensaje más alto que se ve (#mN) y a cuántos píxeles del
      borde está; al cargar, se vuelve a dejar ese mismo mensaje en el mismo sitio, aunque arriba hayan entrado
      mensajes nuevos (por eso se ancla a un mensaje y no a una altura). Se guarda en sessionStorage, 20 s. */
(function () {
  var t = document.querySelector('textarea[autofocus]') || document.querySelector('form.chatbox textarea, form.composer textarea');
  if (t && t.value) {   // cursor al final del texto que ya trae
    try { t.setSelectionRange(t.value.length, t.value.length); } catch (e) {}
  }

  // 3) volver al mensaje que se estaba leyendo tras un refresco (ver arriba)
  var K = 'ddd-chat-pos';
  try {
    var pos = JSON.parse(sessionStorage.getItem(K) || 'null');
    sessionStorage.removeItem(K);
    if (pos && pos.u === location.pathname && Date.now() - pos.t < 20000) {
      var el = document.getElementById(pos.id);
      if (el) {
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        var ir = function () { window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - pos.off, left: 0, behavior: 'instant' }); };
        ir(); requestAnimationFrame(ir);
        window.addEventListener('load', function () { ir(); setTimeout(ir, 100); }, { once: true });
      }
    }
  } catch (e) {}
  function apuntar() {   // el primer mensaje cuyo borde inferior ya esta por debajo de la cabecera: el que se esta leyendo
    try {
      var top = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--top-h')) || 0;
      var ms = document.querySelectorAll('article.cm[id^="m"]');
      for (var i = 0; i < ms.length; i++) {
        var r = ms[i].getBoundingClientRect();
        if (r.bottom > top + 4) { sessionStorage.setItem(K, JSON.stringify({ u: location.pathname, id: ms[i].id, off: r.top, t: Date.now() })); return; }
      }
    } catch (e) {}
  }

  var zona = document.querySelector('[data-refresh]');
  if (!zona) return;
  var seg = parseInt(zona.dataset.refresh, 10);
  if (!(seg > 0)) return;
  var destino = zona.dataset.refreshUrl || location.href;

  var caja = document.querySelector('form.chatbox textarea'), reloj = null;
  function escrito() { return caja && caja.value.trim() !== ''; }
  function programar() {
    if (reloj) { clearTimeout(reloj); reloj = null; }
    if (escrito()) return;   // hay algo escrito: no se recarga
    reloj = setTimeout(function () {
      if (document.hidden || escrito()) { programar(); return; }
      apuntar();
      location.href = destino;
    }, seg * 1000);
  }
  if (caja) caja.addEventListener('input', programar);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) programar(); });
  programar();
})();
