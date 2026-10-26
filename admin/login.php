<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (str_contains($config['app_key'], 'replace-this-with')) {
    http_response_code(503);
    exit('Admin login is not configured. Run php scripts/create-app-key.php from the website folder.');
}

if (!empty($_SESSION['admin_id'])) {
    redirect('/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $ipHash = hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $config['app_key']);
    db()->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $attempts = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $attempts->execute([$ipHash]);

    if ((int) $attempts->fetchColumn() >= 5) {
        $error = 'Too many unsuccessful sign-in attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = db()->prepare('SELECT id, email, password_hash FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            db()->prepare('DELETE FROM login_attempts WHERE ip_hash = ?')->execute([$ipHash]);
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_expires_at'] = time() + 1800;
            redirect('/admin/index.php');
        }
        db()->prepare('INSERT INTO login_attempts (ip_hash, attempted_at) VALUES (?, NOW())')->execute([$ipHash]);
        $error = 'The email or password was not recognised.';
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Admin sign in · OOCC</title><link rel="stylesheet" href="<?= e(app_path('/assets/css/site.css')) ?>"></head>
<body class="login-body"><main class="login-card">
    <a class="brand login-brand" href="<?= e(app_path('/')) ?>"><span class="brand-mark">OO</span><span class="brand-name">OSAMA ORAKZAI<small>CONSTRUCTION COMPANY</small></span></a>
    <span class="eyebrow"><span class="eyebrow-line"></span> PRIVATE WORKSPACE</span><h1>Admin sign in</h1><p class="login-copy">Sign in to manage project work and enquiries.</p>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Email address<input type="email" name="email" required maxlength="190" autocomplete="username" autofocus></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit" class="button button-dark">Sign in <span aria-hidden="true">↗</span></button>
    </form>
    <?php if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0): ?><a class="login-back" href="<?= e(app_path('/admin/setup.php')) ?>">First time here? Create your admin account</a><?php endif; ?>
    <a class="login-back" href="<?= e(app_path('/')) ?>">← Return to website</a>
</main></body></html>
