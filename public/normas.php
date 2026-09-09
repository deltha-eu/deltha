<?php
/** /normas.php — normas de uso. Visible sin clave. El texto vive en la tabla pages (slug 'normas') y se edita en admin -> Paginas. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
$pg = page_get($pdo, 'normas');
page_header($pg['title']);
echo "<div class=\"legal\">" . page_body($pg) . "</div>";
page_footer();
