<?php
require __DIR__ . '/inc/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
}
redirigir('');
