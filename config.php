<?php
declare(strict_types=1);

$appKey = getenv('APP_KEY');
if (!$appKey) {
    $keyFile = __DIR__ . '/.app-key';
    $appKey = is_file($keyFile) ? trim((string) file_get_contents($keyFile)) : 'replace-this-with-a-random-secret-before-deployment';
}

return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_name' => getenv('DB_NAME') ?: 'osama_construction',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'site_url' => rtrim(getenv('SITE_URL') ?: '', '/'),
    'app_key' => $appKey,
    'phone' => '+92 333 6703761',
    'whatsapp' => '923336703761',
    'email' => 'osamaorakzai63@gmail.com',
];
