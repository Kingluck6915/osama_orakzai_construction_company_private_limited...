<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

if (str_contains($config['app_key'], 'replace-this-with') || strlen($config['app_key']) < 32) {
    http_response_code(503);
    exit('Admin setup is not configured. Run php scripts/create-app-key.php from the website folder.');
}

if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    redirect('/admin/login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $failures = (int) ($_SESSION['admin_setup_failures'] ?? 0);
    if ($failures >= 5) {
        http_response_code(429);
        $error = 'Too many attempts. Please close this page, wait 15 minutes, and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 12 || strlen($password) > 4096) {
            $error = 'Choose a password that is at least 12 characters long.';
        } elseif (!hash_equals($password, $confirmation)) {
            $error = 'The passwords do not match.';
        } else {
            $lock = db()->query("SELECT GET_LOCK('oocc_first_admin_setup', 10)")->fetchColumn();
            if ((int) $lock !== 1) {
                http_response_code(503);
                $error = 'Admin setup could not obtain a database lock. Please retry in a moment.';
            } else {
                try {
                    if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
                        redirect('/admin/login.php');
                    }
                    $stmt = db()->prepare('INSERT INTO admins (email, password_hash, created_at) VALUES (?, ?, NOW())');
                    $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                    unset($_SESSION['admin_setup_failures'], $_SESSION['csrf_token']);
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int) db()->lastInsertId();
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_expires_at'] = time() + 1800;
                    flash('Admin account created. You are signed in.');
                    redirect('/admin/index.php');
                } finally {
                    db()->query("SELECT RELEASE_LOCK('oocc_first_admin_setup')");
                }
            }
        }

        if ($error !== '') {
            $_SESSION['admin_setup_failures'] = $failures + 1;
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Create admin account · OOCC</title><link rel="stylesheet" href="<?= e(app_path('/assets/css/site.css')) ?>"></head>
<body class="login-body"><main class="login-card">
    <a class="brand login-brand" href="<?= e(app_path('/')) ?>"><span class="brand-mark">OO</span><span class="brand-name">OSAMA ORAKZAI<small>CONSTRUCTION COMPANY</small></span></a>
    <span class="eyebrow"><span class="eyebrow-line"></span> ONE-TIME SETUP</span><h1>Create admin account</h1><p class="login-copy">Choose the private email and password you’ll use to manage the website. This page closes automatically after the first account is created.</p>
    <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Admin email<input type="email" name="email" required maxlength="190" autocomplete="username" value="<?= e((string) ($_POST['email'] ?? $config['email'])) ?>"></label>
        <label>Password <span>(at least 12 characters)</span><input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
        <label>Confirm password<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
        <button type="submit" class="button button-dark">Create admin account <span aria-hidden="true">↗</span></button>
    </form>
    <a class="login-back" href="<?= e(app_path('/')) ?>">← Return to website</a>
</main></body></html>
