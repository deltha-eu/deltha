// Votar, guardar o comentar recarga la pagina (POST + redireccion) y el navegador la abre por el ancla, arriba:
// el scroll «sube». Aqui el formulario avisa de que hay JS (js=1: el servidor redirige sin ancla), se guarda
// donde estabas y se vuelve ahi al cargar. Sin JS, manda el ancla como siempre.
(function () {
  var K = 'ddd-scroll', url = location.pathname + location.search;
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches('form.vote, form.cvote, form.fav, form#responder')) return;
    if (!f.querySelector('input[name="js"]')) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'js'; i.value = '1'; f.appendChild(i); }
    try { sessionStorage.setItem(K, JSON.stringify({ u: url, y: window.scrollY, t: Date.now() })); } catch (_) {}
  }, true);
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  var s = null;
  try { s = JSON.parse(sessionStorage.getItem(K) || 'null'); sessionStorage.removeItem(K); } catch (_) {}
  if (!s || s.u !== url || Date.now() - s.t > 15000) return;
  var go = function () { window.scrollTo({ top: s.y, left: 0, behavior: 'instant' }); };
  go(); requestAnimationFrame(go);
  window.addEventListener('load', function () { go(); setTimeout(go, 0); setTimeout(go, 100); }, { once: true });
})();
