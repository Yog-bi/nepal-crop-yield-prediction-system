<?php
/**
 * Admin Logout Action Script
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';

perform_logout();

header('Location: login.php');
exit;
