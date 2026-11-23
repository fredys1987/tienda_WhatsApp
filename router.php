<?php
// Solo para desarrollo con el servidor integrado de PHP (sin Apache):
//   php -S localhost:8000 router.php
// Hace lo mismo que el archivo .htaccess.
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(inc|sql)/|^/config(\.example)?\.php$#', $ruta)) {
    http_response_code(403);
    exit;
}
if (preg_match('#^/uploads/.*\.php#i', $ruta)) {
    http_response_code(403);
    exit;
}
if (preg_match('#^/tienda/([a-z0-9-]+)/?$#', $ruta, $m)) {
    $_GET['s'] = $m[1];
    require __DIR__ . '/tienda.php';
    return true;
}
if (preg_match('#^/tienda/([a-z0-9-]+)/manifest\.json$#', $ruta, $m)) {
    $_GET['s'] = $m[1];
    require __DIR__ . '/manifest.php';
    return true;
}
return false; // archivos normales
