<?php
declare(strict_types=1);

// Never expose administrator provisioning as a web endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/db.php';

$username = $argv[1] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/D', $username)) {
    fwrite(STDERR, "Usage: php tools/create-admin.php USERNAME (hash on stdin)\n");
    exit(1);
}
$hash = trim(stream_get_contents(STDIN, 1024));
if ((password_get_info($hash)['algoName'] ?? 'unknown') === 'unknown') {
    fwrite(STDERR, "A PHP-generated password hash is required on stdin.\n");
    exit(1);
}
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, password_hash, role) VALUES (:username, :hash, 'admin')"
    );
    $stmt->execute(['username' => $username, 'hash' => $hash]);
    fwrite(STDOUT, "Administrator created. Existing accounts were not changed.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Administrator creation failed; check configuration or an existing username.\n");
    exit(1);
}
