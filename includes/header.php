<?php
/**
 * Shared Header Navigation Template
 * Clean, editorial navigation without emojis or gradients.
 */

declare(strict_types=1);

if (!isset($currentPage)) {
    $currentPage = 'home';
}

$config = require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= htmlspecialchars($config['app_name']) ?></title>
    <meta name="theme-color" content="#213d30">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?php foreach (($pageStylesheets ?? []) as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet) ?>">
    <?php endforeach; ?>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="app-container d-flex justify-content-between align-items-center">
            <a class="navbar-brand-text text-decoration-none" href="index.php" aria-label="NCYPS home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 42 42" role="img">
                        <path d="M8 29c6-1 12-4 17-9 4-4 7-8 9-13-1 10-5 19-13 25"/>
                        <path d="M9 33c7-2 13-2 20 0M11 26c5 1 10 0 15-4M17 18c4 1 8 0 12-4"/>
                    </svg>
                </span>
                <span class="brand-copy">
                    <strong>NCYPS</strong>
                    <small>Nepal crop and climate observatory</small>
                </span>
            </a>
            <button class="navbar-toggler border-light text-white" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbar" aria-controls="appNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="appNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'home' ? 'active' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'predict' ? 'active' : '' ?>" href="predict.php">Predict</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'history' ? 'active' : '' ?>" href="history.php">History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'info' ? 'active' : '' ?>" href="info.php">Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>" href="about.php">About</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="nav-link nav-link-admin" href="admin/login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
