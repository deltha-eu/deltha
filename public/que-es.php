<?php
/** /que-es.php — «¿Que es Deltha?»: que es, de donde viene y como funciona, en una pagina. Sustituye a la antigua presentacion (/delta/).
 *  Visible sin clave; texto en pages (slug 'que-es'), editable en admin -> Paginas. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'que-es');
page_header(t('Sobre %s', cfg('site_name', 'Deltha')), '', '', $pg['title']);   // la barra dice «Sobre Deltha»; el titulo de la pagina ya va en su <h1>
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
