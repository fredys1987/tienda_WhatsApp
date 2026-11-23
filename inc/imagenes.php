<?php
// Subida segura de fotos: se valida que sea una imagen real y se vuelve a
// guardar con GD (eso elimina cualquier código escondido dentro del archivo).

declare(strict_types=1);

const IMG_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const IMG_MAX_LADO  = 1000;           // píxeles

/**
 * Procesa $_FILES[$campo]. Devuelve el nombre del archivo guardado,
 * null si no se subió nada, o lanza RuntimeException con un mensaje para el usuario.
 */
function guardar_imagen(string $campo): ?string
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$campo];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la foto (¿es muy pesada? máximo 5 MB).');
    }
    if ($f['size'] > IMG_MAX_BYTES) {
        throw new RuntimeException('La foto pesa más de 5 MB.');
    }
    $info = @getimagesize($f['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
    $origen = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
        IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false,
        IMAGETYPE_GIF  => @imagecreatefromgif($f['tmp_name']),
        default        => false,
    };
    if (!$origen) {
        throw new RuntimeException('Formato no soportado. Usa JPG, PNG o WEBP.');
    }

    // Respetar la orientación de las fotos tomadas con el celular
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($f['tmp_name']);
        $rot = match ((int)($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($rot !== 0) {
            $r = imagerotate($origen, $rot, 0);
            if ($r) { imagedestroy($origen); $origen = $r; }
        }
    }

    $w = imagesx($origen);
    $h = imagesy($origen);
    $escala = min(1, IMG_MAX_LADO / max($w, $h));
    $nw = max(1, (int)round($w * $escala));
    $nh = max(1, (int)round($h * $escala));

    $destino = imagecreatetruecolor($nw, $nh);
    // Fondo blanco para PNG con transparencia
    imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
    imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nombre = bin2hex(random_bytes(12)) . '.jpg';
    $ok = imagejpeg($destino, UPLOAD_DIR . '/' . $nombre, 82);
    imagedestroy($origen);
    imagedestroy($destino);
    if (!$ok) {
        throw new RuntimeException('No se pudo guardar la foto en el servidor (revisa permisos de la carpeta uploads).');
    }
    return $nombre;
}

function borrar_imagen(?string $nombre): void
{
    if ($nombre && preg_match('/^[a-f0-9]{24}\.jpg$/', $nombre)) {
        @unlink(UPLOAD_DIR . '/' . $nombre);
    }
}
