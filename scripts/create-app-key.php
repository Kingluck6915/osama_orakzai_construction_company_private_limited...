<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$keyPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.app-key';
if (is_file($keyPath)) {
    fwrite(STDERR, "An application key already exists. Leave it unchanged.\n");
    exit(1);
}

$key = bin2hex(random_bytes(32));
if (file_put_contents($keyPath, $key . PHP_EOL, LOCK_EX) === false) {
    fwrite(STDERR, "Could not save the application key. Check folder permissions.\n");
    exit(1);
}

fwrite(STDOUT, "Application key created. Keep .app-key private and do not delete it.\n");
