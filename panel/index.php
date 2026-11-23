<?php
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/layout.php';

$uid = requerir_login();
$tienda = tienda_de_usuario($uid);
if (!$tienda) {
    exit('Tu cuenta no tiene tienda.');
}

$st = db()->prepare('SELECT * FROM productos WHERE tienda_id = ? ORDER BY creado_en DESC, id DESC');
$st->execute([$tienda['id']]);
$productos = $st->fetchAll();

$enlace = url_absoluta(url_tienda($tienda['slug']));
cabecera('Mis productos', true);
?>
<div class="compartir">
  <div>
    <div class="nota">Enlace de tu tienda</div>
    <a href="<?= e(url_tienda($tienda['slug'])) ?>" target="_blank" id="enlace"><?= e($enlace) ?></a>
  </div>
  <button class="btn btn-chico btn-sec" type="button" onclick="navigator.clipboard.writeText(document.getElementById('enlace').textContent).then(function(){this.textContent='¡Copiado!'}.bind(this))">Copiar</button>
  <a class="btn btn-chico btn-sec" target="_blank" href="https://wa.me/?text=<?= e(rawurlencode('Mira mi catálogo: ' . $enlace)) ?>">Compartir</a>
</div>

<div class="titulo-fila">
  <h1>Productos (<?= count($productos) ?>)</h1>
  <a class="btn" href="<?= e(base_url('panel/producto.php')) ?>">+ Agregar</a>
</div>

<?php if (!$productos): ?>
  <div class="vacio">Aún no tienes productos. <a href="<?= e(base_url('panel/producto.php')) ?>">Agrega el primero</a>.</div>
<?php else: ?>
  <ul class="lista-panel">
    <?php foreach ($productos as $p): ?>
      <li class="<?= $p['activo'] ? '' : 'inactivo' ?>">
        <?php if ($p['imagen']): ?>
          <img src="<?= e(url_upload($p['imagen'])) ?>" alt="" loading="lazy">
        <?php else: ?>
          <div class="sin-foto"></div>
        <?php endif; ?>
        <div class="info">
          <strong><?= e($p['nombre']) ?></strong>
          <span><?= e(precio($p['precio'])) ?><?= $p['activo'] ? '' : ' · Oculto' ?></span>
        </div>
        <a class="btn btn-chico btn-sec" href="<?= e(base_url('panel/producto.php?id=' . (int)$p['id'])) ?>">Editar</a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php pie();
