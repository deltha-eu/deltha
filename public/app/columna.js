/* /mi-columna: ordenar las cajas de la columna derecha arrastrando (API nativa de drag and drop, sin librerias) y con las
 * flechas sin recargar. Sin JS todo sigue funcionando: las flechas son botones que guardan, y «Guardar» manda el orden del DOM
 * (los input w[] van en el orden de las filas, asi que mover un <li> ya cambia lo que se envia). En movil/tactil el arrastre
 * nativo no va bien: quedan las flechas. */
(function () {
  var ol = document.getElementById('colsel');
  if (!ol) return;
  ol.classList.add('dnd');
  var dragging = null;

  function flechas() {   // la primera no sube, la ultima no baja
    var lis = ol.querySelectorAll('li');
    for (var i = 0; i < lis.length; i++) {
      var b = lis[i].querySelectorAll('.mv button');
      if (b.length < 2) continue;
      b[0].disabled = i === 0;
      b[1].disabled = i === lis.length - 1;
    }
  }
  function mover(li, arriba) {
    var otro = arriba ? li.previousElementSibling : li.nextElementSibling;
    if (!otro) return;
    if (arriba) ol.insertBefore(li, otro); else ol.insertBefore(otro, li);
    flechas();
    li.classList.add('moved'); setTimeout(function () { li.classList.remove('moved'); }, 350);
  }

  ol.addEventListener('click', function (e) {
    var b = e.target.closest('.mv button');
    if (!b) return;
    e.preventDefault();
    mover(b.closest('li'), b.value.indexOf('up:') === 0);
  });
  ol.addEventListener('dragstart', function (e) {
    var li = e.target.closest('li');
    if (!li) return;
    dragging = li;
    li.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    try { e.dataTransfer.setData('text/plain', li.dataset.k); } catch (x) {}
  });
  ol.addEventListener('dragover', function (e) {
    if (!dragging) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var li = e.target.closest('li');
    if (!li || li === dragging) return;
    var r = li.getBoundingClientRect();
    var despues = (e.clientY - r.top) > r.height / 2;
    ol.insertBefore(dragging, despues ? li.nextSibling : li);
  });
  ol.addEventListener('drop', function (e) { e.preventDefault(); });
  ol.addEventListener('dragend', function () {
    if (dragging) dragging.classList.remove('dragging');
    dragging = null;
    flechas();
  });
  flechas();
})();
