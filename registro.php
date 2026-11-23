<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/layout.php';

if (usuario_id()) {
    redirigir('panel/');
}

$errores = [];
$v = ['nombre' => '', 'email' => '', 'tienda' => '', 'slug' => '', 'whatsapp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    foreach ($v as $k => $_) {
        $v[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $clave = (string)($_POST['clave'] ?? '');
    $v['email'] = mb_strtolower($v['email']);
    $v['slug'] = slugify($v['slug'] !== '' ? $v['slug'] : $v['tienda']);
    $wa = normalizar_whatsapp($v['whatsapp']);

    if ($v['nombre'] === '' || mb_strlen($v['nombre']) > 120) $errores[] = 'Escribe tu nombre.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL) || strlen($v['email']) > 190) $errores[] = 'El correo no es válido.';
    if (strlen($clave) < 8) $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($v['tienda'] === '' || mb_strlen($v['tienda']) > 120) $errores[] = 'Escribe el nombre de tu tienda.';
    if (strlen($v['slug']) < 3) $errores[] = 'El enlace de la tienda debe tener al menos 3 letras o números.';
    if (in_array($v['slug'], SLUGS_RESERVADOS, true)) $errores[] = 'Ese enlace no está disponible, elige otro.';
    if (strlen($wa) < 10 || strlen($wa) > 15) $errores[] = 'El número de WhatsApp no es válido (ej: 3001234567).';

    if (!$errores) {
        $pdo = db();
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ?');
        $st->execute([$v['email']]);
        if ($st->fetch()) $errores[] = 'Ya existe una cuenta con ese correo. Entra con tu contraseña.';
        $st = $pdo->prepare('SELECT 1 FROM tiendas WHERE slug = ?');
        $st->execute([$v['slug']]);
        if ($st->fetch()) $errores[] = 'Ese enlace de tienda ya está en uso, elige otro.';
    }

    if (!$errores) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)')
                ->execute([$v['nombre'], $v['email'], password_hash($clave, PASSWORD_DEFAULT)]);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO tiendas (usuario_id, slug, nombre, whatsapp) VALUES (?, ?, ?, ?)')
                ->execute([$uid, $v['slug'], $v['tienda'], $wa]);
            $pdo->commit();
        } catch (PDOException $ex) {
            $pdo->rollBack();
            if ((int)($ex->errorInfo[1] ?? 0) === 1062) {
                $errores[] = 'El correo o el enlace ya están en uso. Prueba con otro.';
            } else {
                throw $ex;
            }
        }
        if (!$errores) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $uid;
            flash('¡Tu tienda está lista! Ahora agrega tu primer producto.');
            redirigir('panel/');
        }
    }
}

cabecera('Crear tienda');
?>
<div class="caja-form">
  <h1>Crea tu tienda</h1>
  <?php if ($errores): ?>
    <div class="error"><?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <label>Tu nombre <input name="nombre" value="<?= e($v['nombre']) ?>" required maxlength="120" autocomplete="name"></label>
    <label>Correo <input type="email" name="email" value="<?= e($v['email']) ?>" required autocomplete="email"></label>
    <label>Contraseña <input type="password" name="clave" required minlength="8" autocomplete="new-password"></label>
    <hr>
    <label>Nombre de la tienda <input name="tienda" id="tienda" value="<?= e($v['tienda']) ?>" required maxlength="120"></label>
    <label>Enlace de la tienda
      <span class="prefijo"><span><?= e(url_absoluta(base_url('tienda/'))) ?></span>
      <input name="slug" id="slug" value="<?= e($v['slug']) ?>" pattern="[a-z0-9-]{3,60}" placeholder="mi-tienda"></span>
    </label>
    <label>WhatsApp donde llegan los pedidos
      <input type="tel" name="whatsapp" value="<?= e($v['whatsapp']) ?>" required placeholder="3001234567" inputmode="tel">
    </label>
    <button class="btn btn-ancho">Crear mi tienda</button>
  </form>
  <p class="nota">¿Ya tienes cuenta? <a href="<?= e(base_url('login.php')) ?>">Entra aquí</a></p>
</div>
<script>
// Sugiere el enlace a partir del nombre mientras el usuario no lo edite
(function () {
  var t = document.getElementById('tienda'), s = document.getElementById('slug'), tocado = s.value !== '';
  s.addEventListener('input', function () { tocado = true; });
  t.addEventListener('input', function () {
    if (tocado) return;
    s.value = t.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60);
  });
})();
</script>
<?php pie();
