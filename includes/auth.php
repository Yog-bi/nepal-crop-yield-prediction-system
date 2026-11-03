<?php
/**
 * Authentication and Security Helper Functions
 * Implements session security, CSRF protection, and rate limiting.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        $isHttps = !empty($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

        session_start();
    }

    $lifetime = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function is_logged_in(): bool
{
    start_secure_session();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_admin_login(): void
{
    if (!is_logged_in()) {
        $config = require __DIR__ . '/config.php';
        header('Location: ' . $config['base_url'] . '/admin/login.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator privileges required.');
    }

    // Password rotation or role removal must also revoke existing sessions.
    $stmt = get_db_connection()->prepare(
        'SELECT password_hash, role FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $account = $stmt->fetch();
    $version = $_SESSION['auth_version'] ?? '';
    if (!$account || $account['role'] !== 'admin' || !is_string($version)
        || !hash_equals(hash('sha256', $account['password_hash']), $version)) {
        perform_logout();
        $config = require __DIR__ . '/config.php';
        header('Location: ' . $config['base_url'] . '/admin/login.php');
        exit;
    }
}

function generate_csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    start_secure_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function verify_admin_credentials(string $username, string $password): ?array
{
    // Basic rate limit / attempt delay (500ms)
    usleep(500000);

    $pdo = get_db_connection();
    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return null;
}

function perform_login(array $user): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['auth_version'] = hash('sha256', $user['password_hash']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['last_activity'] = time();
}

function perform_logout(): void
{
    start_secure_session();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

function login_rate_limit_hash(string $value): string
{
    $config = require __DIR__ . '/config.php';
    // Hosted installs may keep the key in private PHP configuration, with
    // direct HTTP access to includes blocked by includes/.htaccess.
    $configuredKey = getenv('CROP_LOGIN_HMAC_KEY');
    if ($configuredKey === false || $configuredKey === '') {
        $configuredKey = $config['login_hmac_key'] ?? null;
    }
    if ($configuredKey !== null) {
        if (!is_string($configuredKey)
            || !preg_match('/^[a-f0-9]{64}$/D', $configuredKey)) {
            throw new RuntimeException('Login security key unavailable.');
        }
        return hash_hmac('sha256', $value, $configuredKey);
    }
    $keyPath = getenv('CROP_LOGIN_HMAC_KEY_FILE');
    if ($keyPath === false || $keyPath === '') {
        // Preserve existing local installations while supporting hosted paths.
        $keyPath = $config['login_hmac_key_file']
            ?? (PHP_OS_FAMILY === 'Windows'
                ? 'C:/xampp/private/crop-secrets/login-hmac.key' : '');
    }
    if (!is_string($keyPath) || $keyPath === '') {
        throw new RuntimeException('Login security key path not configured.');
    }
    $resolved = realpath($keyPath);
    if ($resolved === false || !is_file($resolved)) {
        throw new RuntimeException('Login security key unavailable.');
    }
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if (PHP_SAPI !== 'cli' && $documentRoot !== false) {
        $root = rtrim(str_replace('\\', '/', $documentRoot), '/') . '/';
        $file = str_replace('\\', '/', $resolved);
        if (PHP_OS_FAMILY === 'Windows') {
            $root = strtolower($root);
            $file = strtolower($file);
        }
        if (strpos($file, $root) === 0) {
            throw new RuntimeException('Login security key must be outside the document root.');
        }
    }
    $key = @file_get_contents($resolved);

    if ($key === false || !preg_match('/^[a-f0-9]{64}$/', trim($key))) {
        throw new RuntimeException('Login security key unavailable.');
    }

    return hash_hmac('sha256', $value, trim($key));
}

function login_rate_limit_blocked(string $username, string $ip): bool
{
    $pdo = get_db_connection();

    $usernameHash = login_rate_limit_hash(
        'user:' . strtolower(trim($username))
    );

    $ipHash = login_rate_limit_hash('ip:' . $ip);

    $stmt = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT CASE
            WHEN username_hash = :username_hash
            THEN id END) AS username_attempts,
        COUNT(DISTINCT CASE
            WHEN ip_hash = :ip_hash
            THEN id END) AS ip_attempts
     FROM admin_login_attempts
     WHERE attempted_at >= NOW() - INTERVAL 15 MINUTE"
);
    $stmt->execute([
        'username_hash' => $usernameHash,
        'ip_hash' => $ipHash
    ]);

    $result = $stmt->fetch();

    return (int) ($result['username_attempts'] ?? 0) >= 5
        || (int) ($result['ip_attempts'] ?? 0) >= 5;
}

function record_failed_login(string $username, string $ip): void
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare(
        "INSERT INTO admin_login_attempts
            (username_hash, ip_hash, attempted_at)
         VALUES (:username_hash, :ip_hash, NOW())"
    );

    $stmt->execute([
        'username_hash' => login_rate_limit_hash(
            'user:' . strtolower(trim($username))
        ),
        'ip_hash' => login_rate_limit_hash('ip:' . $ip)
    ]);
}

function acquire_login_lock(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT GET_LOCK('ncyp_admin_login_rate_limit', 5)"
    );

    $stmt->execute();

    if ((int) $stmt->fetchColumn() !== 1) {
        throw new RuntimeException(
            'Unable to acquire login security lock.'
        );
    }
}

function release_login_lock(PDO $pdo): void
{
    $stmt = $pdo->prepare(
        "SELECT RELEASE_LOCK('ncyp_admin_login_rate_limit')"
    );

    $stmt->execute();

    if ((int) $stmt->fetchColumn() !== 1) {
        throw new RuntimeException(
            'Unable to release login security lock.'
        );
    }
}
