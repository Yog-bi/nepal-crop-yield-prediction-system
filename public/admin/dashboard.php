<?php
/**
 * Redesigned Editorial Admin Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';

require_admin_login();

$pdo = get_db_connection();

$totalPredictions = (int)$pdo->query("SELECT COUNT(*) FROM predictions")->fetchColumn();
$totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$totalDistricts = (int)$pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();

$stmt = $pdo->query("
    SELECT pr.id, d.name AS district, c.name AS crop, pr.area_ha,
           pr.predicted_yield, pr.model_version, pr.created_at
    FROM predictions pr
    INNER JOIN districts d ON d.id = pr.district_id
    INNER JOIN crops c ON c.id = pr.crop_id
    ORDER BY pr.created_at DESC
    LIMIT 10
");
$recentPredictions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= htmlspecialchars($config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="app-container d-flex justify-content-between align-items-center">
            <a class="navbar-brand-text text-decoration-none" href="dashboard.php">
                System Admin Portal
            </a>
            <div class="d-flex align-items-center gap-3">
                <a class="nav-link text-white small" href="dashboard.php">Dashboard</a>
                <a class="nav-link text-white small" href="posts.php">Manage Posts</a>
                <a class="nav-link text-white small" href="../index.php" target="_blank">Public Site &rarr;</a>
                <a class="btn btn-outline-light btn-sm ms-2" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="app-container">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <span class="section-label">System Control</span>
                    <h2>Admin Control Center</h2>
                </div>
                <span class="text-muted small">Signed in as <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
            </div>

            <!-- Metric Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <span class="section-label">Audit Logs</span>
                        <h2 class="mb-0 monospace fw-bold text-success"><?= number_format($totalPredictions) ?></h2>
                        <small class="text-muted">Predictions Executed</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <span class="section-label">Information Hub</span>
                        <h2 class="mb-0 monospace fw-bold text-primary"><?= number_format($totalPosts) ?></h2>
                        <small class="text-muted">Articles Published</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom text-center">
                        <span class="section-label">Geographic Coverage</span>
                        <h2 class="mb-0 monospace fw-bold text-dark"><?= number_format($totalDistricts) ?></h2>
                        <small class="text-muted">Districts Seeded</small>
                    </div>
                </div>
            </div>

            <!-- Recent Predictions Audit Log -->
            <div class="card-custom">
                <span class="section-label mb-3">Recent Model Predictions Log</span>
                <?php if (empty($recentPredictions)): ?>
                    <p class="text-muted mb-0">No predictions logged yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>District</th>
                                    <th>Crop</th>
                                    <th>Area</th>
                                    <th>Predicted Yield</th>
                                    <th>Model Version</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPredictions as $p): ?>
                                    <tr>
                                        <td class="monospace"><?= htmlspecialchars((string)$p['id']) ?></td>
                                        <td><?= htmlspecialchars($p['district']) ?></td>
                                        <td><?= htmlspecialchars($p['crop']) ?></td>
                                        <td class="monospace"><?= htmlspecialchars((string)$p['area_ha']) ?> ha</td>
                                        <td class="fw-semibold text-success monospace"><?= number_format((float)$p['predicted_yield'], 2) ?> kg/ha</td>
                                        <td><span class="badge-muted"><?= htmlspecialchars($p['model_version']) ?></span></td>
                                        <td class="small text-muted monospace"><?= htmlspecialchars($p['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>
