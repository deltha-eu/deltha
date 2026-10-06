<?php
/** /reenviar — vuelve a enviar el enlace de activacion (una vez cada 10 min por cuenta). Visible sin clave. */
define('DDD_PUBLIC', true);
require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../lib/mail.php';
if (user()) redirect('/');
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $who = mb_strtolower(post('who'));
    $st = $pdo->prepare("SELECT user_id, email, verified, token_sent FROM users WHERE login = ? OR email = ?");
    $st->execute([$who, $who]);
    $u = $st->fetch();
    // respuesta identica exista o no la cuenta, para no revelar correos
    $msg = t('Si esa cuenta existe y está pendiente de activar, acabamos de enviarle un nuevo enlace.');
    if ($u && (int)$u['verified'] === 0 && (!$u['token_sent'] || strtotime($u['token_sent']) < time() - 600)) {
        send_activation($pdo, (int)$u['user_id'], $u['email']);
    }
}
page_header(t('Reenviar activación'));
if ($msg) echo "<p class=\"notice\">" . h($msg) . "</p>";
echo "<form class=\"plain\" method=\"post\">" . t('<label>Usuario o correo</label>') . "<input type=\"text\" name=\"who\" required>" . t('<button>Enviar de nuevo</button>') . "</form>";
echo "<p class=\"end\">" . t('Se puede pedir un nuevo enlace cada 10 minutos.') . "</p>";
page_footer();
