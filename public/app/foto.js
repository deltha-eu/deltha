/* Deltha — previsualización de la foto elegida. Al seleccionar una imagen en un formulario (enviar, artículo,
   editar…) se enseña debajo del selector, con el mismo recuadro .media.sm que tendrá publicada, antes de enviar
   nada. Si ya había una foto (la encontrada en la página, la actual), se atenúa: la nueva la sustituye.
   Se lee con FileReader (data:) porque la CSP solo admite img-src 'self' data:, no blob:. Sin JS, todo como antes. */
(function () {
  var campos = document.querySelectorAll('input[type="file"][accept^="image"]');
  Array.prototype.forEach.call(campos, function (campo) {
    var previa = null, turno = 0;
    function anterior() { return campo.form ? campo.form.querySelector('.media:not([data-previa])') : null; }
    function quitar() {
      if (previa) { previa.remove(); previa = null; }
      var a = anterior(); if (a) a.style.opacity = '';
    }
    campo.addEventListener('change', function () {
      var f = campo.files && campo.files[0], mio = ++turno;
      if (!f || !/^image\//.test(f.type) || !window.FileReader) { quitar(); return; }
      var lector = new FileReader();
      lector.onload = function () {
        if (mio !== turno) return;   // se eligió otra mientras se leía esta
        var img = new Image();
        img.onload = function () {
          if (mio !== turno) return;
          if (!previa) {
            previa = document.createElement('div');
            previa.className = 'media sm';
            previa.setAttribute('data-previa', '1');
            campo.insertAdjacentElement('afterend', previa);
          }
          previa.style.backgroundImage = 'url("' + lector.result + '")';
          var a = anterior(); if (a) a.style.opacity = '.35';
        };
        img.onerror = function () { if (mio === turno) quitar(); };   // formato que el navegador no pinta (HEIC…): sin previa
        img.src = lector.result;
      };
      lector.onerror = function () { if (mio === turno) quitar(); };
      lector.readAsDataURL(f);
    });
    if (campo.form) campo.form.addEventListener('reset', function () { turno++; quitar(); });
  });
})();
