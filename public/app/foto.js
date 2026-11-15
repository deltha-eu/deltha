/* Deltha — previsualización de la foto elegida. Al seleccionar una imagen en un formulario (enviar, artículo,
   editar…) se enseña debajo del selector, con el mismo recuadro .media.sm que tendrá publicada, antes de enviar
   nada. Si ya había una foto (la encontrada en la página, la actual), se atenúa: la nueva la sustituye.
   Se lee con FileReader (data:) porque la CSP solo admite img-src 'self' data:, no blob:. Sin JS, todo como antes.

   Comentarios y Chat (15/11/2026): el botón «Imagen» (label.cimgpick) esconde el selector, así que la previa es una
   miniatura encima de la fila de botones, con una × para quitarla. Y la imagen no hace falta ir a buscarla al disco:
   se puede PEGAR con Ctrl+V en el cuadro de texto (una captura, «copiar imagen» de otra página) o ARRASTRARLA encima.
   Por debajo es la misma subida: la imagen pegada se mete en ese mismo selector (DataTransfer) y viaja con el formulario
   al pulsar «Comentar» o «Enviar»; el servidor no distingue un camino del otro. Si lo copiado trae texto (una celda de
   una hoja de cálculo, un párrafo de un documento, que llegan como texto y como imagen a la vez), se pega el texto,
   como siempre. En un navegador que no deja rellenar el selector desde código, pegar no hace nada y queda el botón. */
(function () {
  function prepara(campo) {
    var previa = null, turno = 0;
    var pick = campo.closest ? campo.closest('label.cimgpick') : null;   // comentarios y Chat: selector escondido tras un botón
    function anterior() { return campo.form ? campo.form.querySelector('.media:not([data-previa])') : null; }
    function quitar() {
      if (previa) { previa.remove(); previa = null; }
      var a = anterior(); if (a) a.style.opacity = '';
    }
    function pinta(url) {
      if (pick) {
        if (!previa) {
          previa = document.createElement('div');
          previa.className = 'cimgprev';
          previa.setAttribute('data-previa', '1');
          var im = document.createElement('img'); im.alt = '';
          var x = document.createElement('button'); x.type = 'button'; x.className = 'x'; x.textContent = '×';
          var rotulo = pick.getAttribute('data-quitar') || '';
          if (rotulo) { x.title = rotulo; x.setAttribute('aria-label', rotulo); }
          x.addEventListener('click', function () { campo.value = ''; turno++; quitar(); });
          previa.appendChild(im); previa.appendChild(x);
          var fila = pick.parentNode;   // la fila de botones: la miniatura va justo encima
          fila.parentNode.insertBefore(previa, fila);
        }
        previa.querySelector('img').src = url;
        return;
      }
      if (!previa) {
        previa = document.createElement('div');
        previa.className = 'media sm';
        previa.setAttribute('data-previa', '1');
        campo.insertAdjacentElement('afterend', previa);
      }
      previa.style.backgroundImage = 'url("' + url + '")';
      var a = anterior(); if (a) a.style.opacity = '.35';
    }
    campo.addEventListener('change', function () {
      var f = campo.files && campo.files[0], mio = ++turno;
      if (!f || !/^image\//.test(f.type) || !window.FileReader) { quitar(); return; }
      var lector = new FileReader();
      lector.onload = function () {
        if (mio !== turno) return;   // se eligió otra mientras se leía esta
        var img = new Image();
        img.onload = function () { if (mio === turno) pinta(lector.result); };
        img.onerror = function () { if (mio === turno) quitar(); };   // formato que el navegador no pinta (HEIC…): sin previa
        img.src = lector.result;
      };
      lector.onerror = function () { if (mio === turno) quitar(); };
      lector.readAsDataURL(f);
    });
    if (campo.form) campo.form.addEventListener('reset', function () { turno++; quitar(); });
  }
  Array.prototype.forEach.call(document.querySelectorAll('input[type="file"][accept^="image"]'), prepara);

  /* Pegar o arrastrar una imagen al cuadro de un comentario o del Chat. Los avisos van en el documento, no en cada cuadro. */
  function campoDe(el) {
    var f = el && el.closest ? el.closest('form') : null;
    return f ? f.querySelector('label.cimgpick input[type="file"]') : null;
  }
  function imagenDe(dt) {
    var fs = dt && dt.files ? dt.files : [];
    for (var i = 0; i < fs.length; i++) if (/^image\//.test(fs[i].type)) return fs[i];
    return null;
  }
  function poner(campo, f) {
    try {
      var d = new DataTransfer();
      d.items.add(f);
      campo.files = d.files;
    } catch (e) { return false; }
    if (!campo.files || !campo.files.length) return false;
    campo.dispatchEvent(new Event('change', { bubbles: true }));   // la previa, como si se hubiera elegido con el botón
    return true;
  }
  document.addEventListener('paste', function (e) {
    var t = e.target;
    if (!t || t.tagName !== 'TEXTAREA') return;
    var campo = campoDe(t), cd = e.clipboardData;
    if (!campo || !cd) return;
    var f = imagenDe(cd);
    if (!f) return;
    var texto = '';
    try { texto = cd.getData('text/plain') || ''; } catch (_) {}
    if (texto) return;   // lo copiado trae texto: se pega el texto
    if (poner(campo, f)) e.preventDefault();
  });
  function conFicheros(e) {
    var ts = e.dataTransfer && e.dataTransfer.types;
    if (!ts) return false;
    for (var i = 0; i < ts.length; i++) if (ts[i] === 'Files') return true;
    return false;
  }
  function cuadro(e) {
    var f = e.target && e.target.closest ? e.target.closest('form.composer, form.chatbox') : null;
    return f && f.querySelector('label.cimgpick input[type="file"]') ? f : null;
  }
  document.addEventListener('dragover', function (e) {
    var f = cuadro(e);
    if (!f || !conFicheros(e)) return;
    e.preventDefault();   // sin esto el navegador no deja soltar (y abriría la imagen en la pestaña)
    f.classList.add('soltar');
  });
  document.addEventListener('dragleave', function (e) {
    var f = cuadro(e);
    if (f && !f.contains(e.relatedTarget)) f.classList.remove('soltar');
  });
  document.addEventListener('drop', function (e) {
    var f = cuadro(e);
    if (!f || !conFicheros(e)) return;
    f.classList.remove('soltar');
    e.preventDefault();
    var img = imagenDe(e.dataTransfer);
    if (img) poner(campoDe(f), img);
  });
})();
