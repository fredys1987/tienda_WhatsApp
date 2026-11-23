<?php
// Cabecera y pie para las páginas del sitio y del panel.

function cabecera(string $titulo, bool $enPanel = false): void
{
    global $CONFIG;
    $app = $CONFIG['app_name'] ?? 'MiCatálogo';
    $uid = usuario_id();
    ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo) ?> · <?= e($app) ?></title>
  <link rel="stylesheet" href="<?= e(base_url('assets/estilos.css')) ?>">
</head>
<body>
<header class="barra">
  <a class="marca" href="<?= e(base_url($uid ? 'panel/' : '')) ?>"><?= e($app) ?></a>
  <nav>
    <?php if ($uid): ?>
      <a href="<?= e(base_url('panel/')) ?>">Productos</a>
      <a href="<?= e(base_url('panel/tienda.php')) ?>">Mi tienda</a>
      <form method="post" action="<?= e(base_url('logout.php')) ?>" class="en-linea">
        <?= csrf_field() ?><button class="enlace">Salir</button>
      </form>
    <?php else: ?>
      <a href="<?= e(base_url('login.php')) ?>">Entrar</a>
      <a class="btn btn-chico" href="<?= e(base_url('registro.php')) ?>">Crear tienda</a>
    <?php endif; ?>
  </nav>
</header>
<main class="contenedor">
<?php if ($m = flash()): ?>
  <div class="aviso"><?= e($m) ?></div>
<?php endif;
}

function pie(): void
{
    ?>
</main>
</body>
</html>
<?php
}
