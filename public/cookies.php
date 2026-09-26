<?php
/** /cookies.php — inventario de cookies y JavaScript, a la vista de todo el mundo (enlace en el pie, tras Privacidad).
 *  El texto vive en la tabla pages (slug 'cookies') y se edita en admin > Paginas, como privacidad, normas y algoritmo.
 *  Se mantiene a mano: al tocar una cookie o un script, actualizar tambien esta pagina. */
define('DDD_PUBLIC', true);
define('DDD_INDEX', true);    // pagina escaparate: de transparencia, entra en buscadores como privacidad
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'cookies');
page_header(t('Sobre %s', cfg('site_name', 'Deltha')), '', '', $pg['title']);   // la barra dice «Sobre Deltha»; el titulo real va en su <h1>
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
