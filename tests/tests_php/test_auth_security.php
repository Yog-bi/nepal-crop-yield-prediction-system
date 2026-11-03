<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
ini_set('session.save_path', sys_get_temp_dir());
start_secure_session();

function check_security(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$oldToken = generate_csrf_token();
$oldId = session_id();
$password = ' ' . bin2hex(random_bytes(24)) . ' ';
$hash = password_hash($password, PASSWORD_DEFAULT);
perform_login(['id' => 1, 'username' => 'test-fixture', 'role' => 'admin', 'password_hash' => $hash]);
check_security(session_id() !== $oldId, 'Login must regenerate the session ID.');
check_security(!hash_equals($oldToken, $_SESSION['csrf_token']), 'Login must rotate CSRF tokens.');
check_security(hash_equals(hash('sha256', $hash), $_SESSION['auth_version']), 'Session must bind to the current password hash.');
check_security(!password_verify(trim($password), $hash), 'Password boundary spaces must remain significant.');

$previousPath = getenv('CROP_LOGIN_HMAC_KEY_FILE');
$previousKey = getenv('CROP_LOGIN_HMAC_KEY');
putenv('CROP_LOGIN_HMAC_KEY');
$keyFile = tempnam(sys_get_temp_dir(), 'crop-auth-test-');
try {
    $key = bin2hex(random_bytes(32));
    file_put_contents($keyFile, $key);
    putenv('CROP_LOGIN_HMAC_KEY_FILE=' . $keyFile);
    check_security(hash_equals(hash_hmac('sha256', 'fixture', $key), login_rate_limit_hash('fixture')), 'Configured secret file must be used.');
    file_put_contents($keyFile, 'invalid');
    $failedClosed = false;
    try {
        login_rate_limit_hash('fixture');
    } catch (RuntimeException $e) {
        $failedClosed = true;
    }
    check_security($failedClosed, 'Invalid keys must fail closed.');
    putenv('CROP_LOGIN_HMAC_KEY_FILE=' . $keyFile . '.missing');
    $failedClosed = false;
    try {
        login_rate_limit_hash('fixture');
    } catch (RuntimeException $e) {
        $failedClosed = true;
    }
    check_security($failedClosed, 'Missing keys must fail closed.');
    putenv('CROP_LOGIN_HMAC_KEY=' . $key);
    check_security(hash_equals(hash_hmac('sha256', 'fixture', $key), login_rate_limit_hash('fixture')), 'Environment keys must work without a key file.');
    putenv('CROP_LOGIN_HMAC_KEY=invalid');
    $failedClosed = false;
    try {
        login_rate_limit_hash('fixture');
    } catch (RuntimeException $e) {
        $failedClosed = true;
    }
    check_security($failedClosed, 'Invalid environment keys must fail closed.');
} finally {
    unlink($keyFile);
    putenv($previousPath === false ? 'CROP_LOGIN_HMAC_KEY_FILE' : 'CROP_LOGIN_HMAC_KEY_FILE=' . $previousPath);
    putenv($previousKey === false ? 'CROP_LOGIN_HMAC_KEY' : 'CROP_LOGIN_HMAC_KEY=' . $previousKey);
}
perform_logout();
echo "PASS: session rotation, CSRF rotation, password binding, password spaces, private key configuration and fail-closed checks.\n";
