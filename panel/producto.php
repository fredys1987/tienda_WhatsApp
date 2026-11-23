<?php
// Crear, editar y eliminar un producto de la tienda del usuario.
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/imagenes.php';
require __DIR__ . '/../inc/layout.php';

$uid = requerir_login();
$tienda = tienda_de_usuario($uid);
if (!$tienda) {
    exit('Tu cuenta no tiene tienda.');
}
$tid = (int)$tienda['id'];
$pdo = db();

// Siempre se busca el producto filtrando por la tienda del usuario,
// así nadie puede editar productos de otra tienda cambiando el id.
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = ['id' => 0, 'nombre' => '', 'descripcion' => '', 'precio' => '', 'imagen' => null, 'activo' => 1];
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM productos WHERE id = ? AND tienda_id = ?');
    $st->execute([$id, $tid]);
    $p = $st->fetch();
    if (!$p) {
        http_response_code(404);
        exit('Producto no encontrado.');
    }
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    if (isset($_POST['eliminar']) && $p['id']) {
        $pdo->prepare('DELETE FROM productos WHERE id = ? AND tienda_id = ?')->execute([$p['id'], $tid]);
        borrar_imagen($p['imagen']);
        flash('Producto eliminado.');
        redirigir('panel/');
    }

    $p['nombre'] = trim((string)($_POST['nombre'] ?? ''));
    $p['descripcion'] = trim((string)($_POST['descripcion'] ?? ''));
    // Acepta "25.000", "25000" o "25000,50"
    $precioTxt = str_replace(['$', ' '], '', (string)($_POST['precio'] ?? ''));
    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $precioTxt)) {
        $precioTxt = str_replace(['.', ','], ['', '.'], $precioTxt);
    } else {
        $precioTxt = str_replace(',', '.', $precioTxt);
    }
    $p['precio'] = $precioTxt;
    $p['activo'] = isset($_POST['activo']) ? 1 : 0;

    if ($p['nombre'] === '' || mb_strlen($p['nombre']) > 150) $errores[] = 'Escribe el nombre del producto.';
    if (!is_numeric($precioTxt) || (float)$precioTxt < 0 || (float)$precioTxt > 9999999999) $errores[] = 'El precio no es válido.';
    if (mb_strlen($p['descripcion']) > 2000) $errores[] = 'La descripción es muy larga (máximo 2000 caracteres).';

    $nueva = null;
    if (!$errores) {
        try {
            $nueva = guardar_imagen('imagen');
        } catch (RuntimeException $ex) {
            $errores[] = $ex->getMessage();
        }
    }

    if (!$errores) {
        $anterior = $p['imagen'];
        $imagen = $nueva ?? (isset($_POST['quitar_foto']) ? null : $anterior);
        if ($p['id']) {
            $pdo->prepare('UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, imagen = ?, activo = ? WHERE id = ? AND tienda_id = ?')
                ->execute([$p['nombre'], $p['descripcion'], $precioTxt, $imagen, $p['activo'], $p['id'], $tid]);
            flash('Producto actualizado.');
        } else {
            $pdo->prepare('INSERT INTO productos (tienda_id, nombre, descripcion, precio, imagen, activo) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$tid, $p['nombre'], $p['descripcion'], $precioTxt, $imagen, $p['activo']]);
            flash('Producto agregado.');
        }
        if ($anterior && $anterior !== $imagen) {
            borrar_imagen($anterior);
        }
        redirigir('panel/');
    }
}

cabecera($p['id'] ? 'Editar producto' : 'Nuevo producto', true);
?>
<div class="caja-form">
  <h1><?= $p['id'] ? 'Editar producto' : 'Nuevo producto' ?></h1>
  <?php if ($errores): ?>
    <div class="error"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

    <label class="foto">
      <?php if ($p['imagen']): ?>
        <img id="vista" src="<?= e(url_upload($p['imagen'])) ?>" alt="">
      <?php else: ?>
        <img id="vista" alt="" hidden>
      <?php endif; ?>
      <span>Foto del producto (JPG, PNG o WEBP)</span>
      <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" id="imagen">
    </label>
    <?php if ($p['imagen']): ?>
      <label class="check"><input type="checkbox" name="quitar_foto"> Quitar la foto</label>
    <?php endif; ?>

    <label>Nombre <input name="nombre" value="<?= e($p['nombre']) ?>" required maxlength="150"></label>
    <label>Precio <input name="precio" value="<?= e(is_numeric($p['precio']) ? (string)(float)$p['precio'] : (string)$p['precio']) ?>" required inputmode="decimal" placeholder="25000"></label>
    <label>Descripción <textarea name="descripcion" rows="4" maxlength="2000"><?= e($p['descripcion']) ?></textarea></label>
    <label class="check"><input type="checkbox" name="activo" <?= $p['activo'] ? 'checked' : '' ?>> Visible en la tienda</label>

    <button class="btn btn-ancho">Guardar</button>
  </form>

  <?php if ($p['id']): ?>
    <form method="post" onsubmit="return confirm('¿Eliminar este producto?')" class="separado">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-ancho btn-peligro" name="eliminar" value="1">Eliminar producto</button>
    </form>
  <?php endif; ?>
  <p class="nota"><a href="<?= e(base_url('panel/')) ?>">← Volver</a></p>
</div>
<script>
document.getElementById('imagen').addEventListener('change', function () {
  var f = this.files[0], v = document.getElementById('vista');
  if (f) { v.src = URL.createObjectURL(f); v.hidden = false; }
});
</script>
<?php pie();
