<?php
// Arranque común: configuración, base de datos, sesión y utilidades.

declare(strict_types=1);

$configFile = __DIR__ . '/../config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Falta config.php. Copia config.example.php como config.php y ajusta los datos de la base de datos.');
}
$CONFIG = require $configFile;

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/uploads');

// URL base del proyecto (funciona en http://localhost/catalogo/ y en la raíz de un dominio)
function base_url(string $ruta = ''): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
        $rel = ($docRoot !== '' && str_starts_with($appRoot, $docRoot))
            ? substr($appRoot, strlen($docRoot))
            : '';
        $base = rtrim(str_replace('\\', '/', $rel), '/') . '/';
    }
    return $base . ltrim($ruta, '/');
}

function db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        $c = $CONFIG['db'];
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            http_response_code(500);
            exit('No se pudo conectar a la base de datos. Revisa config.php.');
        }
    }
    return $pdo;
}

// ---------- Sesión ----------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verificar_csrf(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('La sesión expiró. Vuelve atrás y recarga la página.');
    }
}

function redirigir(string $ruta): never
{
    header('Location: ' . base_url($ruta));
    exit;
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ---------- Usuario y tienda actuales ----------
function usuario_id(): ?int
{
    return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : null;
}

function requerir_login(): int
{
    $uid = usuario_id();
    if ($uid === null) {
        redirigir('login.php');
    }
    return $uid;
}

function tienda_de_usuario(int $uid): ?array
{
    $st = db()->prepare('SELECT * FROM tiendas WHERE usuario_id = ?');
    $st->execute([$uid]);
    return $st->fetch() ?: null;
}

// ---------- Formatos ----------
function precio(float|string $valor): string
{
    global $CONFIG;
    $v = (float)$valor;
    $dec = (floor($v) == $v) ? 0 : 2;
    return ($CONFIG['moneda'] ?? '$') . number_format($v, $dec, ',', '.');
}

function normalizar_whatsapp(string $num): string
{
    global $CONFIG;
    $d = preg_replace('/\D+/', '', $num) ?? '';
    // Número local de 10 dígitos (celular en Colombia): se le agrega el indicativo
    if (strlen($d) === 10 && !empty($CONFIG['indicativo_por_defecto'])) {
        $d = $CONFIG['indicativo_por_defecto'] . $d;
    }
    return $d;
}

function slugify(string $texto): string
{
    $t = mb_strtolower(trim($texto), 'UTF-8');
    $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
    return substr(trim($t, '-'), 0, 60);
}

// Slugs que chocarían con archivos o rutas del sistema
const SLUGS_RESERVADOS = ['admin', 'panel', 'login', 'registro', 'logout', 'tienda', 'uploads', 'assets', 'inc', 'sql', 'api'];

function url_tienda(string $slug): string
{
    return base_url('tienda/' . rawurlencode($slug));
}

function url_absoluta(string $ruta): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . $ruta;
}

function url_upload(?string $archivo): ?string
{
    return $archivo ? base_url('uploads/' . rawurlencode($archivo)) : null;
}
