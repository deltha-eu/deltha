/* Deltha — videos de YouTube en su sitio. La miniatura es una copia local: nada de YouTube se carga hasta que se pulsa.
   Al pulsar a.pic[data-yt], la foto se cambia por el reproductor de youtube-nocookie.com. Sin JS, o si la politica de
   seguridad del servidor (CSP, frame-src) no admite el marco, el enlace hace lo de siempre. Sin dependencias; defer. */
document.addEventListener('click', function (e) {
  var a = e.target.closest && e.target.closest('a.pic[data-yt]');
  if (!a || e.button || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
  var id = a.getAttribute('data-yt'), box = a.parentNode;
  if (!/^[A-Za-z0-9_-]{11}$/.test(id) || !box) return;
  e.preventDefault();
  var f = document.createElement('iframe');
  f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0';
  f.title = 'YouTube';
  f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
  f.allowFullscreen = true;
  f.referrerPolicy = 'strict-origin-when-cross-origin';
  var fuera = a.href.indexOf('youtu') > -1 ? a.href : 'https://www.youtube.com/watch?v=' + id;
  var bloqueado = function (ev) {   // la CSP no admite el marco: se deshace y se abre el video en YouTube
    if (ev.violatedDirective.indexOf('frame-src') < 0 && ev.violatedDirective.indexOf('default-src') < 0) return;
    document.removeEventListener('securitypolicyviolation', bloqueado);
    if (f.parentNode) f.parentNode.removeChild(f);
    box.classList.remove('playing');
    location.href = fuera;
  };
  document.addEventListener('securitypolicyviolation', bloqueado);
  setTimeout(function () { document.removeEventListener('securitypolicyviolation', bloqueado); }, 4000);
  box.classList.add('playing');
  box.appendChild(f);
});
