<?php
/** /que-es.php — «¿Que es Deltha?»: que es, de donde viene y como funciona, en una pagina. Sustituye a la antigua presentacion (/delta/).
 *  Visible sin clave; texto en pages (slug 'que-es'), editable en admin -> Paginas. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'que-es');
page_header($pg['title']);
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
