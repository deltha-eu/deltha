<?php
/** /algoritmo.php — «Como funciona el δ»: el algoritmo publico de Deltha, con los parametros en vigor. Visible sin clave; texto en pages (slug 'algoritmo'), editable en admin -> Paginas. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'algoritmo');
page_header(t('Sobre %s', cfg('site_name', 'Deltha')), '', '', $pg['title']);   // la barra dice «Sobre Deltha»; el titulo de la pagina ya va en su <h1>
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
