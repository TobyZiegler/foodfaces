<?php
// ============================================================
// Food Faces — admin.php
// projects.tobyziegler.com/foodfaces/admin.php
// Simple session-based password auth — no DB table needed.
// ============================================================

session_start();
require_once __DIR__ . '/db.php';

// -- Auth config — change this password ----------------------
define('ADMIN_PASSWORD', 'Just4dafaces!');

// -- Auth handling -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['ff_admin'] = true;
    } else {
        $login_error = 'Wrong password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}
$authed = $_SESSION['ff_admin'] ?? false;

// -- Actions (authed only) -----------------------------------
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save edits to a single face
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $id      = (int) $_POST['id'];
        $title   = trim($_POST['title']     ?? '');
        $caption = trim($_POST['caption']   ?? '');
        $type    = trim($_POST['face_type'] ?? 'foodface');
        $stmt = $pdo->prepare("UPDATE foodfaces SET title=?, caption=?, face_type=? WHERE id=?");
        $stmt->execute([$title, $caption, $type, $id]);
        $saved_id = $id;
    }

    // Exclude (soft-delete) a face — set type to 'excluded'
    if (isset($_POST['action']) && $_POST['action'] === 'exclude') {
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE foodfaces SET face_type='excluded' WHERE id=?")->execute([$id]);
    }

    // Restore an excluded face back to foodface
    if (isset($_POST['action']) && $_POST['action'] === 'restore') {
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE foodfaces SET face_type='foodface' WHERE id=?")->execute([$id]);
    }
}

// -- Pagination ----------------------------------------------
$page     = max(1, (int) ($_GET['p'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$filter   = $_GET['filter'] ?? 'all';   // all | excluded | foodface_hero | foodface_reveal

$where = match($filter) {
    'excluded'         => "WHERE face_type = 'excluded'",
    'foodface_hero'    => "WHERE face_type = 'foodface_hero'",
    'foodface_reveal'  => "WHERE face_type = 'foodface_reveal'",
    default            => ''
};

$total = (int) $pdo->query("SELECT COUNT(*) FROM foodfaces $where")->fetchColumn();
$pages = (int) ceil($total / $per_page);

$rows = $pdo->query("
    SELECT * FROM foodfaces $where
    ORDER BY sort_order ASC
    LIMIT $per_page OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);

// Valid face_type options for the select
$type_options = [
    'foodface'         => 'foodface',
    'foodface_hero'    => 'foodface_hero',
    'foodface_reveal'  => 'foodface_reveal',
    'foodface,A'       => 'foodface,A',
    'foodface,B'       => 'foodface,B',
    'foodface,C'       => 'foodface,C',
    'foodface,D'       => 'foodface,D',
    'foodface,E'       => 'foodface,E',
    'foodface,F'       => 'foodface,F',
    'other'            => 'other',
    'excluded'         => 'excluded',
];

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Food Faces</title>
    <link rel="stylesheet" href="https://tobyziegler.com/assets/shared.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="ff-admin-page">

<?php if (!$authed): ?>
<!-- ============================================================
     LOGIN
     ============================================================ -->
<div class="ffa-login-wrap">
    <div class="ffa-login-card">
        <div class="eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">Food Faces</span>
        </div>
        <h1>Admin</h1>
        <?php if (isset($login_error)): ?>
            <p class="ffa-error"><?= htmlspecialchars($login_error) ?></p>
        <?php endif ?>
        <form method="POST">
            <div class="ffa-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autofocus autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary ffa-btn-full">Enter →</button>
        </form>
        <p class="ffa-back"><a href="index.php">← Back to Food Faces</a></p>
    </div>
</div>

<?php else: ?>
<!-- ============================================================
     ADMIN PANEL
     ============================================================ -->
<header class="site-header">
    <a href="index.php" class="logo">
        <span class="room-name">Food Faces</span>
        <span class="logo-badge">Admin</span>
    </a>
    <nav class="header-nav">
        <a href="index.php" class="nav-link">View Site</a>
        <a href="?logout=1" class="nav-link">Sign Out</a>
    </nav>
</header>

<main class="ffa-main">

    <!-- Filter bar -->
    <div class="ffa-toolbar">
        <div class="ffa-filters">
            <?php
            $filters = ['all' => 'All', 'excluded' => 'Excluded', 'foodface_hero' => 'Hero', 'foodface_reveal' => 'Reveal'];
            foreach ($filters as $key => $label):
                $active = $filter === $key ? ' ffa-filter--active' : '';
            ?>
            <a href="?filter=<?= $key ?>" class="ffa-filter<?= $active ?>"><?= $label ?></a>
            <?php endforeach ?>
        </div>
        <span class="ffa-count"><?= $total ?> faces &mdash; page <?= $page ?> of <?= max(1,$pages) ?></span>
    </div>

    <?php if (isset($saved_id)): ?>
    <div class="ffa-flash">Saved.</div>
    <?php endif ?>

    <!-- Face rows -->
    <?php foreach ($rows as $row):
        $is_excluded = $row['face_type'] === 'excluded';
    ?>
    <div class="ffa-row <?= $is_excluded ? 'ffa-row--excluded' : '' ?>" id="row-<?= $row['id'] ?>">

        <!-- Thumbnail -->
        <div class="ffa-thumb">
            <img src="photos/<?= htmlspecialchars($row['filename']) ?>"
                 alt="<?= htmlspecialchars($row['title']) ?>">
        </div>

        <!-- Edit form -->
        <form class="ffa-form" method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">

            <div class="ffa-meta">
                <span class="ffa-sort">#<?= $row['sort_order'] ?></span>
                <span class="ffa-date"><?= htmlspecialchars($row['face_date']) ?></span>
                <span class="ffa-filename"><?= htmlspecialchars($row['filename']) ?></span>
            </div>

            <div class="ffa-fields">
                <div class="ffa-field-group">
                    <label>Title</label>
                    <input type="text" name="title"
                           value="<?= htmlspecialchars($row['title']) ?>">
                </div>
                <div class="ffa-field-group">
                    <label>Caption</label>
                    <textarea name="caption" rows="2"><?= htmlspecialchars($row['caption'] ?? '') ?></textarea>
                </div>
                <div class="ffa-field-row">
                    <div class="ffa-field-group ffa-field-group--short">
                        <label>Type</label>
                        <select name="face_type">
                            <?php foreach ($type_options as $val => $label): ?>
                            <option value="<?= $val ?>"<?= $row['face_type'] === $val ? ' selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="ffa-actions">
                        <button type="submit" class="btn btn-primary ffa-save-btn">Save</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Exclude / Restore -->
        <form class="ffa-exclude-form" method="POST">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <?php if ($is_excluded): ?>
                <input type="hidden" name="action" value="restore">
                <button type="submit" class="btn btn-secondary ffa-exclude-btn">Restore</button>
            <?php else: ?>
                <input type="hidden" name="action" value="exclude">
                <button type="submit" class="btn btn-secondary ffa-exclude-btn ffa-exclude-btn--danger"
                        onclick="return confirm('Exclude this face from the gallery?')">Exclude</button>
            <?php endif ?>
        </form>

    </div>
    <?php endforeach ?>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="ffa-pagination">
        <?php if ($page > 1): ?>
            <a href="?filter=<?= $filter ?>&p=<?= $page-1 ?>" class="btn btn-secondary">← Prev</a>
        <?php endif ?>
        <span class="ffa-page-info">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
            <a href="?filter=<?= $filter ?>&p=<?= $page+1 ?>" class="btn btn-secondary">Next →</a>
        <?php endif ?>
    </div>
    <?php endif ?>

</main>

<?php endif ?>

</body>
</html>
