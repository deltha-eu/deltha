// Votar, guardar o comentar recarga la pagina (POST + redireccion con ancla) y el navegador coloca el ancla
// arriba: el scroll «sube». Aqui se guarda donde estabas al enviar y se vuelve ahi al cargar. Sin JS, manda el ancla.
(function () {
  var K = 'ddd-scroll', url = location.pathname + location.search;
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches('form.vote, form.cvote, form.fav, form#responder')) return;
    try { sessionStorage.setItem(K, JSON.stringify({ u: url, y: window.scrollY, t: Date.now() })); } catch (_) {}
  }, true);
  var s = null;
  try { s = JSON.parse(sessionStorage.getItem(K) || 'null'); sessionStorage.removeItem(K); } catch (_) {}
  if (!s || s.u !== url || Date.now() - s.t > 15000) return;
  var go = function () { window.scrollTo({ top: s.y, left: 0, behavior: 'instant' }); };
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  go(); window.addEventListener('load', go, { once: true }); requestAnimationFrame(go);
})();
