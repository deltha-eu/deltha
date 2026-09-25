/* Deltha — el cuadro de escribir. Dos cosas pequeñas, sin dependencias:

   1) Cursor al final. Cuando el cuadro llega con texto puesto («#12 » al responder en el Chat), el navegador
      deja el cursor delante y lo escrito acaba antes de la cita. Se coloca al final una sola vez, al cargar;
      después no se toca, para que al pulsar en medio del texto el cursor caiga donde la persona pulsa.

   2) Refresco del Chat sin perder lo escrito. La página se recarga sola cada N segundos (el elemento con
      data-refresh dice cuántos y data-refresh-url adónde: la misma página con auto=1, como el meta, para que
      la analítica no lo cuente como visita nueva), pero en cuanto hay algo escrito el temporizador se cancela;
      si el cuadro vuelve a quedarse vacío, se programa de nuevo. Tampoco recarga con la pestaña oculta.
      Sin JS no hay temporizador: de eso se encarga el <noscript><meta refresh> del <head>, como siempre. */
(function () {
  var t = document.querySelector('textarea[autofocus]') || document.querySelector('form.chatbox textarea, form.composer textarea');
  if (t && t.value) {   // cursor al final del texto que ya trae
    try { t.setSelectionRange(t.value.length, t.value.length); } catch (e) {}
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
      location.href = destino;
    }, seg * 1000);
  }
  if (caja) caja.addEventListener('input', programar);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) programar(); });
  programar();
})();
