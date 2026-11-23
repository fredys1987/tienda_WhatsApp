<?php
// Datos de la tienda: nombre, enlace, WhatsApp, descripción, logo y color.
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/imagenes.php';
require __DIR__ . '/../inc/layout.php';

$uid = requerir_login();
$t = tienda_de_usuario($uid);
if (!$t) {
    exit('Tu cuenta no tiene tienda.');
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $t['nombre'] = trim((string)($_POST['nombre'] ?? ''));
    $t['slug'] = slugify((string)($_POST['slug'] ?? ''));
    $t['whatsapp'] = normalizar_whatsapp((string)($_POST['whatsapp'] ?? ''));
    $t['descripcion'] = trim((string)($_POST['descripcion'] ?? ''));
    $color = (string)($_POST['color'] ?? '');
    $t['color'] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : '#16a34a';

    if ($t['nombre'] === '' || mb_strlen($t['nombre']) > 120) $errores[] = 'Escribe el nombre de la tienda.';
    if (strlen($t['slug']) < 3) $errores[] = 'El enlace debe tener al menos 3 letras o números.';
    if (in_array($t['slug'], SLUGS_RESERVADOS, true)) $errores[] = 'Ese enlace no está disponible.';
    if (strlen($t['whatsapp']) < 10 || strlen($t['whatsapp']) > 15) $errores[] = 'El número de WhatsApp no es válido.';
    if (mb_strlen($t['descripcion']) > 500) $errores[] = 'La descripción es muy larga (máximo 500 caracteres).';

    if (!$errores) {
        $st = db()->prepare('SELECT 1 FROM tiendas WHERE slug = ? AND id <> ?');
        $st->execute([$t['slug'], $t['id']]);
        if ($st->fetch()) $errores[] = 'Ese enlace ya lo usa otra tienda.';
    }

    $nuevoLogo = null;
    if (!$errores) {
        try {
            $nuevoLogo = guardar_imagen('logo');
        } catch (RuntimeException $ex) {
            $errores[] = $ex->getMessage();
        }
    }

    if (!$errores) {
        $anterior = $t['logo'];
        $logo = $nuevoLogo ?? (isset($_POST['quitar_logo']) ? null : $anterior);
        db()->prepare('UPDATE tiendas SET nombre = ?, slug = ?, whatsapp = ?, descripcion = ?, color = ?, logo = ? WHERE id = ? AND usuario_id = ?')
            ->execute([$t['nombre'], $t['slug'], $t['whatsapp'], $t['descripcion'], $t['color'], $logo, $t['id'], $uid]);
        if ($anterior && $anterior !== $logo) {
            borrar_imagen($anterior);
        }
        flash('Datos de la tienda guardados.');
        redirigir('panel/tienda.php');
    }
}

cabecera('Mi tienda', true);
?>
<div class="caja-form">
  <h1>Mi tienda</h1>
  <?php if ($errores): ?>
    <div class="error"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label class="foto foto-logo">
      <?php if ($t['logo']): ?>
        <img id="vista" src="<?= e(url_upload($t['logo'])) ?>" alt="">
      <?php else: ?>
        <img id="vista" alt="" hidden>
      <?php endif; ?>
      <span>Logo (opcional)</span>
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" id="imagen">
    </label>
    <?php if ($t['logo']): ?>
      <label class="check"><input type="checkbox" name="quitar_logo"> Quitar el logo</label>
    <?php endif; ?>
    <label>Nombre de la tienda <input name="nombre" value="<?= e($t['nombre']) ?>" required maxlength="120"></label>
    <label>Enlace
      <span class="prefijo"><span><?= e(url_absoluta(base_url('tienda/'))) ?></span>
      <input name="slug" value="<?= e($t['slug']) ?>" required pattern="[a-z0-9-]{3,60}"></span>
    </label>
    <label>WhatsApp para pedidos <input type="tel" name="whatsapp" value="<?= e($t['whatsapp']) ?>" required inputmode="tel"></label>
    <label>Descripción corta <textarea name="descripcion" rows="3" maxlength="500" placeholder="Ej: Domicilios en toda la ciudad. Pagos por Nequi o efectivo."><?= e($t['descripcion']) ?></textarea></label>
    <label>Color de la tienda <input type="color" name="color" value="<?= e($t['color']) ?>"></label>
    <button class="btn btn-ancho">Guardar</button>
  </form>
  <p class="nota"><a href="<?= e(url_tienda($t['slug'])) ?>" target="_blank">Ver mi tienda →</a></p>
</div>
<script>
document.getElementById('imagen').addEventListener('change', function () {
  var f = this.files[0], v = document.getElementById('vista');
  if (f) { v.src = URL.createObjectURL(f); v.hidden = false; }
});
</script>
<?php pie();
