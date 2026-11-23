<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/layout.php';

if (usuario_id()) {
    redirigir('panel/');
}

$error = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $clave = (string)($_POST['clave'] ?? '');

    // Freno simple contra adivinar contraseñas: 5 intentos fallidos por sesión = espera 1 minuto
    $intentos = $_SESSION['login_fallos'] ?? ['n' => 0, 't' => 0];
    if ($intentos['n'] >= 5 && time() - $intentos['t'] < 60) {
        $error = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';
    } else {
        $st = db()->prepare('SELECT id, password_hash FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($clave, $u['password_hash'])) {
            unset($_SESSION['login_fallos']);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            redirigir('panel/');
        }
        $_SESSION['login_fallos'] = ['n' => ($intentos['n'] >= 5 ? 0 : $intentos['n']) + 1, 't' => time()];
        $error = 'Correo o contraseña incorrectos.';
    }
}

cabecera('Entrar');
?>
<div class="caja-form">
  <h1>Entrar a mi tienda</h1>
  <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Correo <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
    <label>Contraseña <input type="password" name="clave" required autocomplete="current-password"></label>
    <button class="btn btn-ancho">Entrar</button>
  </form>
  <p class="nota">¿No tienes tienda? <a href="<?= e(base_url('registro.php')) ?>">Créala gratis</a></p>
</div>
<?php pie();
