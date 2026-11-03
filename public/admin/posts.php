<?php
/**
 * Redesigned Editorial Admin Posts CRUD
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$config = require __DIR__ . '/../../includes/config.php';

require_admin_login();
$pdo = get_db_connection();

$error = null;
$success = null;
$editingPost = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrfToken)) {
        $error = "CSRF verification failed. Action aborted.";
    } elseif ($action === 'create' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $sourceName = trim($_POST['source_name'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $publishedAt = $_POST['published_at'] ?? date('Y-m-d');
        $postId = (int)($_POST['post_id'] ?? 0);

        if (empty($title) || empty($body) || empty($sourceName)) {
            $error = "Title, Content Body, and Source Name are required fields.";
        } else {
            if ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO posts (title, body, source_name, source_url, category, published_at, created_by)
                    VALUES (:title, :body, :sname, :surl, :cat, :pub, :by)
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':body' => $body,
                    ':sname' => $sourceName,
                    ':surl' => $sourceUrl ?: null,
                    ':cat' => $category,
                    ':pub' => $publishedAt,
                    ':by' => $_SESSION['user_id']
                ]);
                $success = "Information post created successfully.";
            } elseif ($action === 'update' && $postId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE posts 
                    SET title = :title, body = :body, source_name = :sname, source_url = :surl, category = :cat, published_at = :pub
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':title' => $title,
                    ':body' => $body,
                    ':sname' => $sourceName,
                    ':surl' => $sourceUrl ?: null,
                    ':cat' => $category,
                    ':pub' => $publishedAt,
                    ':id' => $postId
                ]);
                $success = "Information post updated successfully.";
            }
        }
    } elseif ($action === 'delete') {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
            $stmt->execute([':id' => $postId]);
            $success = "Post deleted successfully.";
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editingPost = $stmt->fetch();
}

$postsStmt = $pdo->query("SELECT * FROM posts ORDER BY published_at DESC, created_at DESC");
$posts = $postsStmt->fetchAll();
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Information Posts — <?= htmlspecialchars($config['app_name']) ?></title>
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
                <a class="nav-link text-white small active" href="posts.php">Manage Posts</a>
                <a class="nav-link text-white small" href="../index.php" target="_blank">Public Site &rarr;</a>
                <a class="btn btn-outline-light btn-sm ms-2" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="app-container">
            
            <div class="mb-4">
                <span class="section-label">Content Management</span>
                <h2>Manage Information Posts</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom alert-custom-danger mb-4"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-custom alert-custom-success mb-4"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="card-custom mb-4">
                <span class="section-label mb-3"><?= $editingPost ? 'Edit Post #' . htmlspecialchars((string)$editingPost['id']) : 'Create New Article' ?></span>

                <form action="posts.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="<?= $editingPost ? 'update' : 'create' ?>">
                    <?php if ($editingPost): ?>
                        <input type="hidden" name="post_id" value="<?= htmlspecialchars((string)$editingPost['id']) ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Article Title *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editingPost['title'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category *</label>
                            <select name="category" class="form-select" required>
                                <?php
                                $categories = ['Agro-Climatic', 'Crop Management', 'Soil & Moisture', 'General'];
                                $curCat = $editingPost['category'] ?? 'General';
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= $curCat === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Published Date *</label>
                            <input type="date" name="published_at" class="form-control" value="<?= htmlspecialchars($editingPost['published_at'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Article Content Body *</label>
                        <textarea name="body" class="form-control" rows="5" required><?= htmlspecialchars($editingPost['body'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Source Name (Attribution) *</label>
                            <input type="text" name="source_name" class="form-control" placeholder="e.g. MoALD / NASA POWER" value="<?= htmlspecialchars($editingPost['source_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Source URL</label>
                            <input type="url" name="source_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($editingPost['source_url'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <?php if ($editingPost): ?>
                            <a href="posts.php" class="btn btn-secondary-custom">Cancel Edit</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary-custom">
                            <?= $editingPost ? 'Save Changes' : 'Publish Article' ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Posts Listing Table -->
            <div class="card-custom">
                <span class="section-label mb-3">Published Information Posts</span>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Source</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No posts published.</td></tr>
                            <?php else: ?>
                                <?php foreach ($posts as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($p['title']) ?></td>
                                        <td><span class="badge-muted"><?= htmlspecialchars($p['category']) ?></span></td>
                                        <td><?= htmlspecialchars($p['source_name']) ?></td>
                                        <td class="monospace small text-muted"><?= htmlspecialchars($p['published_at'] ?: 'N/A') ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="posts.php?edit=<?= htmlspecialchars((string)$p['id']) ?>" class="btn btn-secondary-custom btn-sm py-1 px-2" style="font-size: 0.8rem;">Edit</a>
                                                <form action="posts.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="post_id" value="<?= htmlspecialchars((string)$p['id']) ?>">
                                                    <button type="submit" class="btn btn-secondary-custom btn-sm py-1 px-2 text-danger" style="font-size: 0.8rem;">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
