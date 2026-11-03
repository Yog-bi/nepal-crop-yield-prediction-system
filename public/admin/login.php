<?php
/**
 * Redesigned Editorial Admin Login Page
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
$config = require __DIR__ . '/../../includes/config.php';

start_secure_session();

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $csrfToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';

    if (!verify_csrf_token($csrfToken)) {
        $error = "Security token mismatch (CSRF protection). Please try again.";
    } elseif ($username === '' || $password === '') {
        $error = "Please provide both username and password.";
       } else {
        $pdo = null;
        $lockAcquired = false;
        $loginUser = null;
        $securityError = false;

        try {
            $pdo = get_db_connection();

            acquire_login_lock($pdo);
            $lockAcquired = true;

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            if (login_rate_limit_blocked($username, $ip)) {
                $error = 'Too many login attempts. Please try again later.';
            } else {
                $user = verify_admin_credentials($username, $password);

                if ($user && ($user['role'] ?? '') === 'admin') {
                    $loginUser = $user;
                } else {
                    record_failed_login($username, $ip);
                    $error = 'Invalid username or password.';
                }
            }
        } catch (Throwable $e) {
            error_log('Admin login security failure: ' . $e->getMessage());
            $securityError = true;
            $error = 'Login temporarily unavailable. Please try again later.';
            http_response_code(503);
        } finally {
            if ($lockAcquired && $pdo instanceof PDO) {
                try {
                    release_login_lock($pdo);
                } catch (Throwable $e) {
                    error_log('Admin login lock release failure: ' . $e->getMessage());
                    $securityError = true;
                    $error = 'Login temporarily unavailable. Please try again later.';
                    http_response_code(503);
                }
            }
        }

        if (!$securityError && $loginUser !== null) {
            perform_login($loginUser);
            header('Location: dashboard.php');
            exit;
        }
    }
}

$token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= htmlspecialchars($config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--color-bg);">

    <div class="app-container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="card-custom shadow-sm p-4 p-md-5" style="max-width: 420px; width: 100%;">
            <div class="mb-4 text-center">
                <span class="section-label">System Authentication</span>
                <h3 class="mb-1">Admin Portal</h3>
                <p class="text-muted small">Nepal Crop Yield System Management</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom alert-custom-danger mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2">Sign In to Dashboard</button>
            </form>

            <div class="text-center mt-4">
                <a href="../index.php" class="text-muted small text-decoration-none">&larr; Return to Public Website</a>
            </div>
        </div>
    </div>

</body>
</html>
