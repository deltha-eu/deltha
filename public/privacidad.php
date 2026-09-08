<?php
/** /privacidad.php — politica de privacidad, aviso legal y contacto. Visible sin clave.
 *  El texto vive en la tabla pages (slug 'privacidad') y se edita en admin. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'privacidad');
page_header($pg['title']);
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
