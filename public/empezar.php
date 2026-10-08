<?php
/** /empezar — «Como empezar»: la FAQ de arranque para quien llega sin haber usado un foro (cuenta, portadas, radar, votar, enviar,
 *  comentar, Ghost, Chat, fuentes, aura, ajustes). Visible sin clave y abierta a buscadores; texto en pages (slug 'empezar'),
 *  editable en admin -> Paginas (migra-122). Enlazada en el pie y en el Chat. */
define('DDD_PUBLIC', true);
define('DDD_INDEX', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'empezar');
page_header(t('Cómo empezar'), '', '', $pg['title']);
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
