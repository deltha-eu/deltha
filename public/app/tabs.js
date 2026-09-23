/* Pestañas de arriba en movil: si los cinco rotulos no caben (idiomas de palabras largas, skins de letra ancha),
 * primero se estrecha el aire entre ellas y luego se reduce la letra, como mucho a un 78 %. Si ni asi caben, la barra
 * se desliza de lado (CSS). Se recalcula al girar el movil y cuando llegan las fuentes de la skin. */
(function () {
  var n = document.querySelector('nav.tabs-top');
  if (!n) return;
  var as = n.querySelectorAll('a'), base = [];
  function fit() {
    var i, s, p;
    for (i = 0; i < as.length; i++) { as[i].style.fontSize = ''; as[i].style.paddingLeft = as[i].style.paddingRight = ''; }
    if (n.scrollWidth <= n.clientWidth + 1) return;
    for (i = 0; i < as.length; i++) base[i] = parseFloat(getComputedStyle(as[i]).fontSize);
    for (p = 6; p >= 2 && n.scrollWidth > n.clientWidth + 1; p -= 2)
      for (i = 0; i < as.length; i++) as[i].style.paddingLeft = as[i].style.paddingRight = p + 'px';
    for (s = 0.96; s >= 0.78 && n.scrollWidth > n.clientWidth + 1; s -= 0.03)
      for (i = 0; i < as.length; i++) as[i].style.fontSize = (base[i] * s).toFixed(2) + 'px';
    var on = n.querySelector('a.on');   // si aun desliza, que se vea la pestaña activa
    if (on && n.scrollWidth > n.clientWidth + 1) n.scrollLeft = on.offsetLeft - (n.clientWidth - on.offsetWidth) / 2;
  }
  fit();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
  var t; addEventListener('resize', function () { clearTimeout(t); t = setTimeout(fit, 120); });
})();
