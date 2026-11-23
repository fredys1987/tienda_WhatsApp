<?php
// Ícono cuadrado PNG de la tienda: el logo recortado, o la inicial sobre el color de la tienda.
require __DIR__ . '/inc/app.php';

$slug = (string)($_GET['s'] ?? '');
$tam = (int)($_GET['t'] ?? 192) === 512 ? 512 : 192;
$t = null;
if (preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
    $st = db()->prepare('SELECT nombre, color, logo FROM tiendas WHERE slug = ?');
    $st->execute([$slug]);
    $t = $st->fetch() ?: null;
}
if (!$t) {
    http_response_code(404);
    exit;
}

$img = imagecreatetruecolor($tam, $tam);
[$r, $g, $b] = sscanf($t['color'], '#%02x%02x%02x');
imagefill($img, 0, 0, imagecolorallocate($img, $r, $g, $b));

$logo = $t['logo'] && preg_match('/^[a-f0-9]{24}\.jpg$/', $t['logo']) ? @imagecreatefromjpeg(UPLOAD_DIR . '/' . $t['logo']) : false;
if ($logo) {
    $w = imagesx($logo); $h = imagesy($logo); $lado = min($w, $h);
    imagecopyresampled($img, $logo, 0, 0, (int)(($w - $lado) / 2), (int)(($h - $lado) / 2), $tam, $tam, $lado, $lado);
    imagedestroy($logo);
} else {
    // Inicial dibujada con la fuente interna de GD, escalada
    $letra = strtoupper(substr(slugify($t['nombre']) ?: 'T', 0, 1));
    $chico = imagecreatetruecolor(8, 13);
    imagefill($chico, 0, 0, imagecolorallocate($chico, $r, $g, $b));
    imagestring($chico, 4, 0, -1, $letra, imagecolorallocate($chico, 255, 255, 255));
    $lw = (int)($tam * 0.42); $lh = (int)($lw * 13 / 8);
    imagecopyresized($img, $chico, (int)(($tam - $lw) / 2), (int)(($tam - $lh) / 2), 0, 0, $lw, $lh, 8, 13);
    imagedestroy($chico);
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($img);
imagedestroy($img);
