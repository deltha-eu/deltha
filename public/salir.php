<?php
require __DIR__ . '/../lib/app.php';
if (isset($_COOKIE[DDD_SESSION])) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION = [];
    session_destroy();
    setcookie(DDD_SESSION, '', ['expires' => time() - 3600, 'path' => '/']);
}
redirect('/');
