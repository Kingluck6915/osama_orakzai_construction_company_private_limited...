<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
session_name('osama_construction_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' https://images.unsplash.com data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

function app_path(string $path = '/'): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = dirname($scriptName);
    if ($directory === '.' || $directory === '/') {
        $directory = '';
    } elseif (str_ends_with($directory, '/admin')) {
        $directory = substr($directory, 0, -strlen('/admin'));
    }

    return rtrim($directory, '/') . '/' . ltrim($path, '/');
}

function render_database_error(PDOException $exception): never
{
    error_log((string) $exception);
    $driverCode = (int) ($exception->errorInfo[1] ?? 0);
    if ($exception->getCode() === '1049' || $driverCode === 1049) {
        $message = 'The website database has not been created yet. Create the configured database, then import database/schema.sql. If the database already exists from an older setup, import database/migrations/001_services.sql.';
    } elseif ($exception->getCode() === '1045' || $driverCode === 1045) {
        $message = 'The website cannot sign in to its database. Check DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD in config.php.';
    } elseif (in_array($driverCode, [2002, 2003], true)) {
        $message = 'The database server is not available. Start MySQL in the XAMPP Control Panel, then reload this page.';
    } elseif ($exception->getCode() === '42S02' || $driverCode === 1146) {
        $message = 'A required website table is missing. Import database/schema.sql for a new installation, or apply the database migrations for an existing installation.';
    } else {
        $message = 'The website could not complete a database operation. Check the PHP error log for details.';
    }

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Website temporarily unavailable</title><body><main><h1>Website temporarily unavailable</h1><p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p><p><a href="' . htmlspecialchars(app_path('/'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Try again</a></p></main></body></html>';
    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    if ($exception instanceof PDOException) {
        render_database_error($exception);
    }

    error_log((string) $exception);
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Website temporarily unavailable</title><body><main><h1>Website temporarily unavailable</h1><p>The website hit an unexpected error. Please try again later.</p><p><a href="' . htmlspecialchars(app_path('/'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">Try again</a></p></main></body></html>';
});

function db(): PDO
{
    static $pdo;
    global $config;

    if (!$pdo) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_name']
        );
        try {
            $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            render_database_error($exception);
        }
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

function require_admin(): void
{
    $expiresAt = (int) ($_SESSION['admin_expires_at'] ?? 0);
    if (empty($_SESSION['admin_id']) || $expiresAt < time()) {
        unset($_SESSION['admin_id'], $_SESSION['admin_expires_at']);
        header('Location: ' . app_path('/admin/login.php'));
        exit;
    }
    $_SESSION['admin_expires_at'] = time() + 1800;
}

function redirect(string $path): never
{
    header('Location: ' . app_path($path), true, 303);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function admin_layout_start(string $title): void
{
    $flash = take_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title><?= e($title) ?> · OOCC Admin</title>
        <link rel="stylesheet" href="<?= e(app_path('/assets/css/site.css')) ?>">
        <script src="<?= e(app_path('/assets/js/site.js')) ?>" defer></script>
    </head>
    <body class="admin-body">
    <header class="admin-topbar">
        <a class="admin-brand" href="<?= e(app_path('/admin/index.php')) ?>"><span class="brand-mark">OO</span><span>OOCC <small>ADMIN</small></span></a>
        <nav class="admin-nav" aria-label="Admin navigation">
            <a href="<?= e(app_path('/admin/index.php')) ?>">Overview</a>
            <a href="<?= e(app_path('/admin/projects.php')) ?>">Projects</a>
            <a href="<?= e(app_path('/admin/services.php')) ?>">Services</a>
            <a href="<?= e(app_path('/admin/inquiries.php')) ?>">Inquiries</a>
        </nav>
        <form method="post" action="<?= e(app_path('/admin/logout.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button button-outline button-small" type="submit">Sign out</button>
        </form>
    </header>
    <main class="admin-main">
        <?php if ($flash): ?>
            <p class="admin-flash admin-flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endif; ?>
    <?php
}

function admin_layout_end(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
