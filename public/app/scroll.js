// Votar, guardar, seguir un medio o comentar recarga la pagina (POST + redireccion) y el navegador la abre por el ancla, arriba:
// el scroll «sube». Aqui el formulario avisa de que hay JS (js=1: el servidor redirige sin ancla), se guarda
// donde estabas y se vuelve ahi al cargar. Sin JS, manda el ancla como siempre.
(function () {
  var K = 'ddd-scroll', url = location.pathname;   // solo la ruta: el chat vuelve con ?ok=1
  // Votar, sol/lagrima y guardar (10/10/2026): sin recargar. Se envia con fetch (ajax=1), el servidor devuelve solo el widget
  // actualizado y se cambia en su sitio: ni parpadeo ni salto. Si falla (red, sesion caducada), se envia como siempre.
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches('form.vote, form.cvote, form.fav') || !window.fetch || !window.FormData) return;
    if (f.matches('form.fav') && location.pathname.indexOf('/favoritos') === 0) return;   // ahi el post quitado debe desaparecer: recarga normal
    if (f.dataset.busy) { e.preventDefault(); return; }
    var btn = e.submitter, fd = new FormData(f);
    if (btn && btn.name) fd.append(btn.name, btn.value);
    fd.append('ajax', '1');
    e.preventDefault(); f.dataset.busy = '1'; f.style.opacity = '.55';
    fetch(f.action, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { if (!r.ok || (r.headers.get('content-type') || '').indexOf('text/html') < 0) throw 0; return r.text(); })
      .then(function (html) {
        html = html.trim();
        if (!html || html.indexOf('<!') === 0 || html.indexOf('<html') === 0) throw 0;   // una pagina entera (login, error): no es el widget
        var t = document.createElement('div'); t.innerHTML = html;
        if (t.firstElementChild) f.replaceWith(t.firstElementChild); else throw 0;
      })
      .catch(function () {   // recarga clasica, conservando el boton pulsado (v=1|-1), que submit() no envia
        delete f.dataset.busy; f.style.opacity = '';
        if (btn && btn.name && !f.querySelector('input[name="' + btn.name + '"]')) { var h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value; f.appendChild(h); }
        HTMLFormElement.prototype.submit.call(f);
      });
  });
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
