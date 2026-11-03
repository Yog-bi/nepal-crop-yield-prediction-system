<?php
/**
 * Redesigned Editorial Agricultural Information Hub Page
 */

declare(strict_types=1);

$currentPage = 'info';
$pageTitle = 'Agricultural Information';

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db_connection();
$categoryFilter = $_GET['category'] ?? '';

$sql = "SELECT id, title, body, source_name, source_url, category, published_at FROM posts";
$params = [];

if (!empty($categoryFilter)) {
    $sql .= " WHERE category = :cat";
    $params[':cat'] = $categoryFilter;
}

$sql .= " ORDER BY published_at DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$categoriesStmt = $pdo->query("SELECT DISTINCT category FROM posts ORDER BY category ASC");
$allCategories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content">
    <div class="app-container">
        
        <div class="mb-4">
            <span class="section-label">Information Hub</span>
            <h2>Agricultural &amp; Climate Reference Archive</h2>
            <p class="text-muted">
                Curated articles and statistical reports with explicit data source attribution. Non-advisory informational summaries only.
            </p>
        </div>

        <!-- Category Filters -->
        <div class="mb-4 d-flex flex-wrap gap-2">
            <a href="info.php" class="btn btn-sm <?= empty($categoryFilter) ? 'btn-primary-custom' : 'btn-secondary-custom' ?>">All Categories</a>
            <?php foreach ($allCategories as $cat): ?>
                <a href="info.php?category=<?= urlencode($cat) ?>" class="btn btn-sm <?= $categoryFilter === $cat ? 'btn-primary-custom' : 'btn-secondary-custom' ?>">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="alert-custom alert-custom-warning mb-4">
            <strong>Source Notice &amp; Scope:</strong> Content published in this archive is extracted directly from official literature by MoALD, NASA POWER, and ICIMOD. This application does not generate personalized agronomic advisory.
        </div>

        <?php if (empty($posts)): ?>
            <div class="card-custom p-4 text-center text-muted">No published articles found matching this category filter.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($posts as $post): ?>
                    <div class="col-12">
                        <article class="card-custom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge-muted"><?= htmlspecialchars($post['category']) ?></span>
                                <time class="small text-muted" datetime="<?= htmlspecialchars($post['published_at'] ?: '') ?>">
                                    Published: <?= htmlspecialchars($post['published_at'] ?: 'N/A') ?>
                                </time>
                            </div>
                            
                            <h3 class="mb-3"><?= htmlspecialchars($post['title']) ?></h3>
                            
                            <p class="text-muted mb-4" style="white-space: pre-line; line-height: 1.65; max-width: 950px;">
                                <?= htmlspecialchars($post['body']) ?>
                            </p>
                            
                            <div class="border-top pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="small text-muted">
                                    Attribution: <strong><?= htmlspecialchars($post['source_name']) ?></strong>
                                </span>
                                <?php if (!empty($post['source_url'])): ?>
                                    <a href="<?= htmlspecialchars($post['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-custom btn-sm">
                                        Access Original Source &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
