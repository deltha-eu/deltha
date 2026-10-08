/* Deltha — el cuadro de escribir y el refresco del Chat. Sin dependencias, sin terceros.

   1) Cursor al final. Cuando el cuadro llega con texto puesto («#12 » al responder en el Chat), el navegador
      deja el cursor delante y lo escrito acaba antes de la cita. Se coloca al final una sola vez, al cargar;
      después no se toca, para que al pulsar en medio del texto el cursor caiga donde la persona pulsa.

   2) Refresco del Chat sin recargar la página (07/10/2026). Cada N segundos (el elemento con data-refresh dice
      cuántos y data-refresh-url adónde: la misma página con auto=1, para que la analítica no lo cuente como visita)
      se pide la página con fetch y se cambia solo el bloque del Chat por el que llega: aparecen los mensajes nuevos
      (con un realce de un segundo), se actualizan contadores y respuestas, y todo lo demás se queda como estaba:
      el scroll (anclado al mensaje que se estaba leyendo, no a una altura: aguanta aunque entren mensajes arriba),
      el cuadro de escribir con su texto, su foco y su cursor. Con la pestaña oculta no se pide nada. Si el servidor
      no responde o la respuesta no trae el Chat, no se toca nada y se vuelve a intentar al siguiente turno.
      Lo que llega es la misma página que vería quien recarga a mano: no hay otra ruta ni otro formato.
      Sin JS no hay nada de esto: de recargar se encarga el <noscript><meta refresh> del <head>, como siempre. */
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
  var reloj = null, pidiendo = false;

  function topH() { return parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--top-h')) || 0; }
  /** El mensaje que se esta leyendo: el primero cuyo borde inferior ya asoma bajo la cabecera, y a que altura esta. */
  function ancla() {
    var ms = document.querySelectorAll('article.cm[id^="m"]'), lim = topH() + 4;
    for (var i = 0; i < ms.length; i++) {
      var r = ms[i].getBoundingClientRect();
      if (r.bottom > lim) return { id: ms[i].id, top: r.top };
    }
    return null;
  }
  function ids(raiz) {
    var s = {}, ms = raiz.querySelectorAll('article.cm[id^="m"]');
    for (var i = 0; i < ms.length; i++) s[ms[i].id] = true;
    return s;
  }

  function refrescar() {
    if (pidiendo) return;
    pidiendo = true;
    fetch(destino, { credentials: 'same-origin', cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var nuevo = doc.querySelector('.chat'), viejo = document.querySelector('.chat');
        if (!nuevo || !viejo) return;
        var habia = ids(viejo), a = ancla();
        var activo = document.activeElement;
        // los cuadros de escribir que ya estan (con su texto, su foco y su cursor) sustituyen a los que vienen: el de arriba
        // (id escribir) y, si lo hubiera, el de responder bajo un mensaje
        var cajasV = viejo.querySelectorAll('form.chatbox'), cajasN = nuevo.querySelectorAll('form.chatbox');
        for (var i = 0; i < cajasN.length && i < cajasV.length; i++) cajasN[i].replaceWith(cajasV[i]);
        // realce de lo que acaba de llegar
        var msN = nuevo.querySelectorAll('article.cm[id^="m"]');
        for (var j = 0; j < msN.length; j++) if (!habia[msN[j].id]) msN[j].classList.add('nuevo');
        viejo.replaceWith(nuevo);
        // el scroll, donde estaba: el mismo mensaje a la misma altura
        if (a) {
          var el = document.getElementById(a.id);
          if (el) window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - a.top, left: 0, behavior: 'instant' });
        }
        if (activo && activo.isConnected && document.activeElement !== activo) { try { activo.focus({ preventScroll: true }); } catch (e) {} }
        setTimeout(function () {
          var ns = document.querySelectorAll('article.cm.nuevo');
          for (var k = 0; k < ns.length; k++) ns[k].classList.remove('nuevo');
        }, 1800);
      })
      .catch(function () { /* sin red o respuesta rara: no se toca nada; al siguiente turno se vuelve a probar */ })
      .then(function () { pidiendo = false; programar(); });
  }
  function programar() {
    if (reloj) { clearTimeout(reloj); reloj = null; }
    reloj = setTimeout(function () {
      if (document.hidden) { programar(); return; }
      refrescar();
    }, seg * 1000);
  }
  document.addEventListener('visibilitychange', function () { if (!document.hidden) refrescar(); });   // al volver a la pestaña, al dia sin esperar
  programar();
})();
