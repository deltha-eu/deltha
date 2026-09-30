// Votar, guardar, seguir un medio o comentar recarga la pagina (POST + redireccion) y el navegador la abre por el ancla, arriba:
// el scroll «sube». Aqui el formulario avisa de que hay JS (js=1: el servidor redirige sin ancla), se guarda
// donde estabas y se vuelve ahi al cargar. Sin JS, manda el ancla como siempre.
(function () {
  var K = 'ddd-scroll', url = location.pathname;   // solo la ruta: el chat vuelve con ?ok=1
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches('form.vote, form.cvote, form.fav, form.seguir, form#responder, form#escribir')) return;
    if (!f.querySelector('input[name="js"]')) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'js'; i.value = '1'; f.appendChild(i); }
    try { sessionStorage.setItem(K, JSON.stringify({ u: url, y: window.scrollY, t: Date.now() })); } catch (_) {}
  }, true);
  // «responder» en el chat es un enlace (?r=N): el cuadro se pinta bajo el mensaje, asi que se guarda el sitio y se va sin ancla
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a.replylink');
    if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
    try { sessionStorage.setItem(K, JSON.stringify({ u: url, y: window.scrollY, t: Date.now() })); } catch (_) {}
    e.preventDefault(); location.href = a.href.split('#')[0];
  });
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  var s = null;
  try { s = JSON.parse(sessionStorage.getItem(K) || 'null'); sessionStorage.removeItem(K); } catch (_) {}
  if (!s || s.u !== url || Date.now() - s.t > 15000) return;
  var go = function () { window.scrollTo({ top: s.y, left: 0, behavior: 'instant' }); };
  go(); requestAnimationFrame(go);
  window.addEventListener('load', function () { go(); setTimeout(go, 0); setTimeout(go, 100); }, { once: true });
})();
