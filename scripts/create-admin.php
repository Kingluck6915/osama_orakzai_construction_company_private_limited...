<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require dirname(__DIR__) . '/config.php';
if (str_contains($config['app_key'], 'replace-this-with')) {
    fwrite(STDERR, "Create the application key first by running php scripts/create-app-key.php.\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']),
    $config['db_user'],
    $config['db_password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

fwrite(STDOUT, 'Admin email: ');
$email = strtolower(trim((string) fgets(STDIN)));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fwrite(STDERR, "Please enter a valid email address.\n");
    exit(1);
}
fwrite(STDOUT, "Admin password (12+ characters): ");
$password = trim((string) fgets(STDIN));
if (strlen($password) < 12) {
    fwrite(STDERR, "The password must be at least 12 characters long.\n");
    exit(1);
}

$stmt = $pdo->prepare('INSERT INTO admins (email, password_hash, created_at) VALUES (?, ?, NOW())');
$stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
fwrite(STDOUT, "Admin account created. Delete this script from the server after use.\n");
