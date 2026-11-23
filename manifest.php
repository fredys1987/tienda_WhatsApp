<?php
// Manifest para que cada tienda se pueda "instalar" en el celular como una app.
require __DIR__ . '/inc/app.php';

$slug = (string)($_GET['s'] ?? '');
$t = null;
if (preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
    $st = db()->prepare('SELECT slug, nombre, color FROM tiendas WHERE slug = ?');
    $st->execute([$slug]);
    $t = $st->fetch() ?: null;
}
if (!$t) {
    http_response_code(404);
    exit;
}

$icono = fn(int $tam) => [
    'src'   => base_url('icono.php?s=' . rawurlencode($t['slug']) . '&t=' . $tam),
    'sizes' => "{$tam}x{$tam}",
    'type'  => 'image/png',
];

header('Content-Type: application/manifest+json; charset=utf-8');
echo json_encode([
    'name'             => $t['nombre'],
    'short_name'       => mb_substr($t['nombre'], 0, 12),
    'start_url'        => url_tienda($t['slug']),
    'scope'            => url_tienda($t['slug']),
    'display'          => 'standalone',
    'background_color' => '#ffffff',
    'theme_color'      => $t['color'],
    'icons'            => [$icono(192), $icono(512)],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
