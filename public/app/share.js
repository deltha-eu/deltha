/* Deltha — «compartir»: con menú nativo si lo hay (móvil), si no copia el enlace al portapapeles.
   Sin JS el enlace lleva al hilo. Sin dependencias; se carga con defer desde page_footer(). */
document.addEventListener('click', function (e) {
  var a = e.target.closest('a[data-share]');
  if (!a) return;
  e.preventDefault();
  var url = a.href, title = a.dataset.title || document.title;
  var done = function () {
    var span = a.querySelector('span'), old = span ? span.textContent : '';
    if (span) { span.textContent = a.dataset.copied || 'ok'; a.classList.add('copied'); setTimeout(function () { span.textContent = old; a.classList.remove('copied'); }, 1800); }
  };
  if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
  if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(url).then(done, function () { window.prompt('Enlace:', url); }); return; }
  window.prompt('Enlace:', url);
});
