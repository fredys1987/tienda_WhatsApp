<?php
// Página pública de una tienda: catálogo + carrito + pedido por WhatsApp.
require __DIR__ . '/inc/app.php';

$slug = (string)($_GET['s'] ?? '');
$t = null;
if (preg_match('/^[a-z0-9-]{3,60}$/', $slug)) {
    $st = db()->prepare('SELECT id, slug, nombre, whatsapp, descripcion, logo, color FROM tiendas WHERE slug = ?');
    $st->execute([$slug]);
    $t = $st->fetch() ?: null;
}
if (!$t) {
    http_response_code(404);
    ?><!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tienda no encontrada</title><link rel="stylesheet" href="<?= e(base_url('assets/estilos.css')) ?>">
    <body><main class="contenedor"><div class="vacio"><h1>Esta tienda no existe</h1><p>Revisa el enlace que te compartieron.</p></div></main></body></html><?php
    exit;
}

$st = db()->prepare('SELECT id, nombre, descripcion, precio, imagen FROM productos WHERE tienda_id = ? AND activo = 1 ORDER BY creado_en DESC, id DESC');
$st->execute([$t['id']]);
$productos = $st->fetchAll();

$datos = [
    'slug'     => $t['slug'],
    'tienda'   => $t['nombre'],
    'whatsapp' => $t['whatsapp'],
    'moneda'   => $CONFIG['moneda'] ?? '$',
    'productos' => array_map(fn($p) => [
        'id'     => (int)$p['id'],
        'nombre' => $p['nombre'],
        'precio' => (float)$p['precio'],
    ], $productos),
];
$color = $t['color'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($t['nombre']) ?></title>
  <meta name="description" content="<?= e($t['descripcion'] ?: 'Catálogo de ' . $t['nombre']) ?>">
  <meta name="theme-color" content="<?= e($color) ?>">
  <meta property="og:title" content="<?= e($t['nombre']) ?>">
  <meta property="og:description" content="<?= e($t['descripcion'] ?: 'Mira el catálogo y haz tu pedido por WhatsApp') ?>">
  <?php if ($t['logo']): ?><meta property="og:image" content="<?= e(url_absoluta(url_upload($t['logo']))) ?>"><?php endif; ?>
  <link rel="manifest" href="<?= e(url_tienda($t['slug']) . '/manifest.json') ?>">
  <link rel="icon" href="<?= e(base_url('icono.php?s=' . rawurlencode($t['slug']) . '&t=192')) ?>">
  <link rel="apple-touch-icon" href="<?= e(base_url('icono.php?s=' . rawurlencode($t['slug']) . '&t=192')) ?>">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="stylesheet" href="<?= e(base_url('assets/estilos.css')) ?>">
  <style>:root { --marca: <?= e($color) ?>; }</style>
</head>
<body class="tienda">
<header class="cabecera-tienda">
  <?php if ($t['logo']): ?>
    <img class="logo" src="<?= e(url_upload($t['logo'])) ?>" alt="">
  <?php else: ?>
    <div class="logo logo-letra"><?= e(mb_strtoupper(mb_substr($t['nombre'], 0, 1))) ?></div>
  <?php endif; ?>
  <div>
    <h1><?= e($t['nombre']) ?></h1>
    <?php if ($t['descripcion']): ?><p><?= nl2br(e($t['descripcion'])) ?></p><?php endif; ?>
  </div>
</header>

<main class="contenedor">
  <?php if (count($productos) > 6): ?>
    <input type="search" id="buscar" class="buscar" placeholder="Buscar producto…" autocomplete="off">
  <?php endif; ?>

  <?php if (!$productos): ?>
    <div class="vacio">Esta tienda todavía no tiene productos.</div>
  <?php else: ?>
    <div class="grilla" id="grilla">
      <?php foreach ($productos as $p): ?>
        <article class="producto" data-id="<?= (int)$p['id'] ?>" data-nombre="<?= e(mb_strtolower($p['nombre'])) ?>">
          <?php if ($p['imagen']): ?>
            <img src="<?= e(url_upload($p['imagen'])) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
          <?php else: ?>
            <div class="sin-foto"></div>
          <?php endif; ?>
          <div class="cuerpo">
            <h2><?= e($p['nombre']) ?></h2>
            <?php if ($p['descripcion']): ?><p class="desc"><?= nl2br(e($p['descripcion'])) ?></p><?php endif; ?>
            <div class="precio"><?= e(precio($p['precio'])) ?></div>
            <div class="controles" data-id="<?= (int)$p['id'] ?>">
              <button type="button" class="btn btn-ancho agregar">Agregar</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Barra inferior del carrito -->
<button type="button" class="barra-carrito" id="barraCarrito" hidden>
  <span id="barraCant">0 productos</span>
  <strong id="barraTotal"></strong>
  <span>Ver pedido →</span>
</button>

<!-- Panel del pedido -->
<div class="velo" id="velo" hidden></div>
<section class="carrito" id="carrito" hidden aria-label="Tu pedido">
  <div class="titulo-fila">
    <h2>Tu pedido</h2>
    <button type="button" class="enlace" id="cerrar">Cerrar ✕</button>
  </div>
  <ul id="items"></ul>
  <div class="total">Total: <strong id="total"></strong></div>
  <form id="formPedido">
    <label>Tu nombre <input id="cNombre" required maxlength="80" autocomplete="name"></label>
    <label>Dirección o notas (opcional)
      <textarea id="cNotas" rows="2" maxlength="300" placeholder="Ej: Calle 10 #5-20, pago contra entrega"></textarea>
    </label>
    <button class="btn btn-ancho btn-wa">Enviar pedido por WhatsApp</button>
  </form>
  <button type="button" class="enlace vaciar" id="vaciar">Vaciar pedido</button>
</section>

<script>window.TIENDA = <?= json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= e(base_url('assets/tienda.js')) ?>"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register(<?= json_encode(base_url('sw.js')) ?>).catch(function () {});
}
</script>
</body>
</html>
