<?php
/** /privacidad.php — politica de privacidad, aviso legal y contacto. Visible sin clave.
 *  El texto vive en la tabla pages (slug 'privacidad') y se edita en admin. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'privacidad');
page_header(t('Sobre %s', cfg('site_name', 'Deltha')), '', '', $pg['title']);   // la barra dice «Sobre Deltha»; el titulo de la pagina ya va en su <h1>
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
