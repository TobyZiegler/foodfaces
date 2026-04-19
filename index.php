<?php
// ============================================================
// Food Faces - projects.tobyziegler.com/foodfaces/index.php
// ============================================================

require_once __DIR__ . '/db.php';   // gitignored; create manually on server

// -- Config ---------------------------------------------------
define('IMAGES_PATH', 'photos/');   // relative to index.php - works on Herd and production
define('PAGE_SIZE', 12);            // cards per gallery load

// -- Queries --------------------------------------------------

// Today's face - set manually in admin; persists until changed
$today_face_id = (int) $pdo->query("
    SELECT setting_value FROM ff_settings WHERE setting_key = 'today_face_id'
")->fetchColumn();

if ($today_face_id) {
    $stmt = $pdo->prepare("SELECT * FROM foodfaces WHERE id = ?");
    $stmt->execute([$today_face_id]);
    $today = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $today = null;
}

// Hero pair: fixed hero face + fixed reveal salad
$hero   = $pdo->query("SELECT * FROM foodfaces WHERE face_type = 'foodface_hero' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$reveal = $pdo->query("SELECT * FROM foodfaces WHERE face_type = 'foodface_reveal' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Random face - any foodface or paired set, not hero/reveal/other/today
$exclude_id = $today_face_id ?: 0;
$random = $pdo->query("
    SELECT * FROM foodfaces
    WHERE face_type NOT IN ('foodface_hero', 'foodface_reveal', 'other')
    AND id != $exclude_id
    ORDER BY RAND()
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Gallery - paginated, excluding reveal/other/excluded
$gallery_page   = max(1, (int) ($_GET['p'] ?? 1));
$gallery_offset = ($gallery_page - 1) * PAGE_SIZE;

$total_faces = (int) $pdo->query("
    SELECT COUNT(*) FROM foodfaces
    WHERE face_type NOT IN ('foodface_reveal', 'other', 'excluded')
")->fetchColumn();

$total_pages = (int) ceil($total_faces / PAGE_SIZE);

$gallery = $pdo->query("
    SELECT * FROM foodfaces
    WHERE face_type NOT IN ('foodface_reveal', 'other', 'excluded')
    ORDER BY sort_order ASC
    LIMIT " . PAGE_SIZE . " OFFSET $gallery_offset
")->fetchAll(PDO::FETCH_ASSOC);

// -- Helpers --------------------------------------------------

function img_url($filename) {
    return IMAGES_PATH . htmlspecialchars($filename);
}

function face_caption($row) {
    return htmlspecialchars(trim($row['caption'] ?? ''));
}

function face_title($row) {
    return htmlspecialchars(trim($row['title']));
}

// Render a single gallery card
function gallery_card($row, $index) {
    $caption    = face_caption($row);
    $title      = face_title($row);
    $src        = img_url($row['filename']);
    $is_pair    = strpos($row['face_type'], ',') !== false; // e.g. "foodface,A"
    $pair_label = $is_pair
        ? '<span class="pair-badge">' . htmlspecialchars(substr($row['face_type'], strpos($row['face_type'], ',') + 1)) . '</span>'
        : '';
    $caption_html = $caption ? '<p class="ff-card__caption">' . $caption . '</p>' : '';

    return <<<HTML
    <article class="ff-card" data-index="{$index}">
        <div class="ff-card__img-wrap">
            <img src="{$src}" alt="{$title}" loading="lazy">
            {$pair_label}
        </div>
        <div class="ff-card__body">
            <p class="ff-card__title">{$title}</p>
            {$caption_html}
        </div>
    </article>
HTML;
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Faces - Toby's Study</title>
    <link rel="stylesheet" href="https://tobyziegler.com/assets/shared.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="project-page foodfaces-page">

<!-- ============================================================
     SITE HEADER
     ============================================================ -->
<header class="site-header">
    <a href="https://tobyziegler.com" class="logo">
        <span class="room-name">Food Faces</span>
    </a>
    <nav class="header-nav">
        <a href="https://tobyziegler.com" class="nav-link">The Study</a>
        <a href="https://projects.tobyziegler.com" class="nav-link">Projects</a>
        <a href="https://dadabase.tobyziegler.com" class="nav-link">Dad-a-Base</a>
        <a href="https://resume.tobyziegler.com" class="nav-link">Resume</a>
    </nav>
</header>

<!-- ============================================================
     HERO SECTION
     Grid layout:
       Row 1, col 1-2: story copy
       Row 1-2, col 3: random face (spans both rows)
       Row 2, col 1-2: pair explanation + figures (full available width)
     ============================================================ -->
<section class="ff-hero">
    <div class="ff-hero__inner">

        <!-- Row 1, col 1+2: Story copy -->
        <div class="ff-hero__story">
            <div class="eyebrow">
                <span class="eyebrow-line"></span>
                <span class="eyebrow-text">Food Faces</span>
            </div>
            <h1 class="green">Lunch with a Personality</h1>
            <p>It started with a creative urge and a blank canvas of cottage cheese. One afternoon in January 2010, a salad became a face. The ladies at the checkout registers loved it. So the next day, there was another one.</p>
            <p>The rules were simple: make a face out of whatever was on the plate, take a picture, post it. The only standing goal was never to repeat a face. Over the next several years, <?= $total_faces ?> faces were made - each one a small, edible portrait that lived only long enough to make it to a table before becoming lunch.</p>
            <p>Below is the archive, posted here one face at a time, in the order they were made.</p>
        </div>

        <!-- Row 1+2, col 3: Random face - spans both rows -->
        <div class="ff-hero__random">
            <div class="eyebrow">
                <span class="eyebrow-line"></span>
                <span class="eyebrow-text">Face of the Moment</span>
            </div>
            <?php if ($random): ?>
            <figure class="ff-hero__random-fig">
                <img src="<?= img_url($random['filename']) ?>" alt="<?= face_title($random) ?>">
                <figcaption>
                    <strong><?= face_title($random) ?></strong>
                    <?php if (face_caption($random)): ?>
                    <span><?= face_caption($random) ?></span>
                    <?php endif; ?>
                </figcaption>
            </figure>
            <?php endif; ?>
            <button class="btn btn-secondary ff-random-btn" id="js-new-random">Another one &rarr;</button>
        </div>

        <!-- Row 2, col 1+2: Pair explanation + both figures -->
        <div class="ff-hero__pair-row">
            <p class="ff-hero__pair-note">Each face was a real meal, consumed that day. The face on the left became the meal on the right. True for every face with a cottage cheese background.</p>
            <div class="ff-hero__pair-figures">
                <?php if ($hero): ?>
                <figure class="ff-hero__face">
                    <img src="<?= img_url($hero['filename']) ?>" alt="<?= face_title($hero) ?>">
                    <figcaption>
                        <strong><?= face_title($hero) ?></strong>
                        <?php if (face_caption($hero)): ?>
                        <span><?= face_caption($hero) ?></span>
                        <?php endif; ?>
                    </figcaption>
                </figure>
                <?php endif; ?>
                <?php if ($reveal): ?>
                <figure class="ff-hero__reveal">
                    <img src="<?= img_url($reveal['filename']) ?>" alt="<?= face_title($reveal) ?>">
                    <figcaption>
                        <strong><?= face_title($reveal) ?></strong>
                        <?php if (face_caption($reveal)): ?>
                        <span><?= face_caption($reveal) ?></span>
                        <?php endif; ?>
                    </figcaption>
                </figure>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.ff-hero__inner -->
</section>

<!-- ============================================================
     TODAY'S FACE + SHARE CARD
     ============================================================ -->
<section class="ff-share">
    <div class="ff-share__inner">
        <div class="eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">Today's Face</span>
        </div>
        <h2>Today's Share Card</h2>
        <p class="ff-share__sub">Copy or download - then post to Facebook or LinkedIn.</p>

        <?php if (!$today): ?>
        <p class="ff-share__no-today">No face has been set for today. Visit the <a href="admin.php">admin panel</a> to set one.</p>
        <?php else: ?>
        <div class="ff-share__card" id="js-share-card">
            <!-- Populated by JS from FF_TODAY -->
        </div>
        <div class="ff-share__actions">
            <button class="btn btn-primary" id="js-copy-card">Copy image</button>
            <button class="btn btn-secondary" id="js-download-card">Download</button>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     GALLERY
     ============================================================ -->
<section class="ff-gallery">
    <div class="ff-gallery__inner">
        <div class="eyebrow">
            <span class="eyebrow-line"></span>
            <span class="eyebrow-text">The Archive</span>
        </div>
        <h2>All <?= $total_faces ?> Faces</h2>

        <div class="ff-gallery__grid" id="js-gallery">
            <?php foreach ($gallery as $i => $row): ?>
                <?php echo gallery_card($row, ($gallery_page - 1) * PAGE_SIZE + $i); ?>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="ff-gallery__pagination">
            <?php if ($gallery_page > 1): ?>
                <a href="?p=<?= $gallery_page - 1 ?>" class="btn btn-secondary">&larr; Prev</a>
            <?php endif; ?>
            <span class="ff-gallery__page-info">
                Page <?= $gallery_page ?> of <?= $total_pages ?>
                &nbsp;-&nbsp; <?= $total_faces ?> faces total
            </span>
            <?php if ($gallery_page < $total_pages): ?>
                <a href="?p=<?= $gallery_page + 1 ?>" class="btn btn-secondary">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ============================================================
     SITE FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div>
        <span class="room-name">Food Faces</span>
        <span class="tagline">Every face was lunch.</span>
    </div>
    <nav class="footer-nav">
        <a href="admin.php" class="footer-link">Admin</a>
        <a href="https://tobyziegler.com" class="footer-link">Toby's Study</a>
        <a href="https://projects.tobyziegler.com" class="footer-link">All Projects</a>
    </nav>
</footer>

<!-- ============================================================
     DATA FOR JS
     ============================================================ -->
<script>
var FF_FACES          = <?= json_encode(array_values(array_filter($gallery, function($r) {
    return strpos($r['face_type'], 'foodface') === 0;
}))) ?>;
var FF_IMAGES_PATH    = '<?= IMAGES_PATH ?>';
var FF_RANDOM_CURRENT = <?= json_encode($random) ?>;
var FF_TODAY          = <?= json_encode($today) ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="foodfaces.js"></script>

</body>
</html>
