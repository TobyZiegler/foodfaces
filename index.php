<?php
// ============================================================
// Food Faces — projects.tobyziegler.com/foodfaces/index.php
// ============================================================

require_once __DIR__ . '/db.php';   // gitignored; create manually on server

// -- Config ---------------------------------------------------
define('IMAGES_PATH', 'photos/');   // relative to index.php — works on Herd and production
define('PAGE_SIZE', 12);                        // cards per gallery load

// -- Queries --------------------------------------------------

// Hero pair: fixed hero face + fixed reveal salad
$hero = $pdo->query("SELECT * FROM foodfaces WHERE face_type = 'foodface_hero' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$reveal = $pdo->query("SELECT * FROM foodfaces WHERE face_type = 'foodface_reveal' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Random face — any foodface or paired set, not hero/reveal/other
$random = $pdo->query("
    SELECT * FROM foodfaces
    WHERE face_type NOT IN ('foodface_hero', 'foodface_reveal', 'other')
    ORDER BY RAND()
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Gallery — paginated, excluding reveal/other/excluded
$gallery_page = max(1, (int) ($_GET['p'] ?? 1));
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
    // Returns caption if set, empty string if not
    return htmlspecialchars(trim($row['caption']));
}

function face_title($row) {
    return htmlspecialchars(trim($row['title']));
}

// Render a single gallery card
function gallery_card($row, $index) {
    $caption = face_caption($row);
    $title   = face_title($row);
    $src     = img_url($row['filename']);
    $is_pair = strpos($row['face_type'], ',') !== false; // e.g. "foodface,A"
    $pair_label   = $is_pair ? '<span class="pair-badge">' . htmlspecialchars(substr($row['face_type'], strpos($row['face_type'], ',') + 1)) . '</span>' : '';
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
    <title>Food Faces — Toby's Study</title>
    <link rel="stylesheet" href="https://tobyziegler.com/assets/shared.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="project-page foodfaces-page">

<!-- ============================================================
     SITE HEADER
     ============================================================ -->
<header class="site-header">
    <div class="site-header__inner">
        <a class="site-header__home" href="https://tobyziegler.com">Toby's Study</a>
        <nav class="site-header__nav">
            <a href="https://projects.tobyziegler.com">Projects</a>
            <a href="https://dadabase.tobyziegler.com">Dad-a-Base</a>
            <a href="https://resume.tobyziegler.com">Résumé</a>
        </nav>
    </div>
</header>

<!-- ============================================================
     HERO SECTION — 3-column grid
     Row 1: [copy A+B] [random C]
     Row 2: [hero face A] [salad reveal B] [random C cont.]
     ============================================================ -->
<section class="ff-hero">
    <div class="ff-hero__inner">

        <!-- Row 1, Col A+B: Story copy -->
        <div class="ff-hero__story">
            <p class="eyebrow">Food Faces</p>
            <h1>Lunch with a Personality</h1>
            <p>It started with a creative urge and a blank canvas of cottage cheese. One afternoon in January 2010, a salad became a face. The ladies at the checkout registers loved it. So the next day, there was another one.</p>
            <p>The rules were simple: make a face out of whatever was on the plate, take a picture, post it. The only standing goal was never to repeat a face. Over the next several years, <?= $total_faces ?> faces were made — each one a small, edible portrait that lived only long enough to make it to a table before becoming lunch.</p>
            <p>Below is the archive, posted here one face at a time, in the order they were made.</p>
        </div>

        <!-- Rows 1+2, Col C: Random face — spans both rows -->
        <div class="ff-hero__random">
            <p class="eyebrow">Face of the Moment</p>
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
            <button class="btn btn-secondary ff-random-btn" id="js-new-random">Another one →</button>
        </div>

        <!-- Row 2, Col A: Hero face -->
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

        <!-- Row 2, Col B: Salad reveal -->
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

    </div><!-- /.ff-hero__inner -->
</section>

<!-- ============================================================
     SHARE CARD GENERATOR
     ============================================================ -->
<section class="ff-share">
    <div class="ff-share__inner">
        <p class="eyebrow">Daily Post</p>
        <h2>Today's Share Card</h2>
        <p class="ff-share__sub">Copy or download — then post to Facebook or LinkedIn.</p>

        <div class="ff-share__card" id="js-share-card">
            <!-- Populated by JS from the "Today's Face" random pick -->
        </div>

        <div class="ff-share__actions">
            <button class="btn btn-primary" id="js-copy-card">Copy image</button>
            <button class="btn btn-secondary" id="js-download-card">Download</button>
        </div>
    </div>
</section>

<!-- ============================================================
     GALLERY
     ============================================================ -->
<section class="ff-gallery">
    <div class="ff-gallery__inner">
        <p class="eyebrow">The Archive</p>
        <h2>All <?= $total_faces ?> Faces</h2>

        <div class="ff-gallery__grid" id="js-gallery">
            <?php foreach ($gallery as $i => $row): ?>
                <?php echo gallery_card($row, ($gallery_page - 1) * PAGE_SIZE + $i); ?>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="ff-gallery__pagination">
            <?php if ($gallery_page > 1): ?>
                <a href="?p=<?= $gallery_page - 1 ?>" class="btn btn-secondary">← Prev</a>
            <?php endif; ?>
            <span class="ff-gallery__page-info">
                Page <?= $gallery_page ?> of <?= $total_pages ?>
                &nbsp;&mdash;&nbsp; <?= $total_faces ?> faces total
            </span>
            <?php if ($gallery_page < $total_pages): ?>
                <a href="?p=<?= $gallery_page + 1 ?>" class="btn btn-secondary">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ============================================================
     SITE FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div class="site-footer__inner">
        <p>Food Faces &mdash; Toby Ziegler &mdash; <a href="https://tobyziegler.com">tobyziegler.com</a></p>
    </div>
</footer>

<!-- ============================================================
     RANDOM FACE DATA (JSON for JS)
     ============================================================ -->
<script>
// All gallery faces available for random selection (title + caption + filename)
var FF_FACES = <?= json_encode(array_values(array_filter($gallery, function($r) {
    return strpos($r['face_type'], 'foodface') === 0;
}))) ?>;

var FF_IMAGES_PATH = '<?= IMAGES_PATH ?>';

var FF_RANDOM_CURRENT = <?= json_encode($random) ?>;
</script>
<script src="foodfaces.js"></script>

</body>
</html>
