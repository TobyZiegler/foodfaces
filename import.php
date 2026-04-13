<?php
// ============================================================
// Food Faces — CSV importer
// Run once locally: php import.php
// Assumes schema.sql has already been run against the database.
// ============================================================

// -- Database connection (local Herd dev) ---------------------
$host   = '127.0.0.1';
$db     = 'foodfaces';       // adjust to your local DB name
$user   = 'root';
$pass   = '';
$dsn    = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$csv    = __DIR__ . '/foodfaces_curation.csv';

// -------------------------------------------------------------

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage() . "\n");
}

if (!file_exists($csv)) {
    die("CSV not found: $csv\n");
}

$fh = fopen($csv, 'r');
$headers = fgetcsv($fh); // consume header row

// Normalize header names to lowercase trimmed keys
$headers = array_map('trim', $headers);

$stmt = $pdo->prepare("
    INSERT INTO foodfaces
        (filename, original_timestamp, face_date, title, caption,
         face_type, construction_date, construction_comment, sort_order)
    VALUES
        (:filename, :original_timestamp, :face_date, :title, :caption,
         :face_type, :construction_date, :construction_comment, :sort_order)
");

$inserted = 0;
$skipped  = 0;
$sort     = 0;

while (($row = fgetcsv($fh)) !== false) {
    // Map row to associative array using headers
    $data = array_combine($headers, array_pad($row, count($headers), ''));

    // Skip rows marked "no"
    if (strtolower(trim($data['keep'])) === 'no') {
        $skipped++;
        continue;
    }

    // Parse date — CSV has formats like "1/7/10", "1/13/10"
    $raw_date = trim($data['date']);
    $dt = DateTime::createFromFormat('n/j/y', $raw_date);
    if (!$dt) {
        $dt = DateTime::createFromFormat('n/j/Y', $raw_date);
    }
    $face_date = $dt ? $dt->format('Y-m-d') : '2010-01-01';

    // Caption from curated column
    $caption = trim($data['caption'] ?? '');

    $sort++;

    $stmt->execute([
        ':filename'             => trim($data['filename']),
        ':original_timestamp'   => (int) trim($data['timestamp']),
        ':face_date'            => $face_date,
        ':title'                => trim($data['title'] ?? ''),
        ':caption'              => $caption,
        ':face_type'            => trim($data['type']) ?: 'foodface',
        ':construction_date'    => trim($data['construction_date'] ?? ''),
        ':construction_comment' => trim($data['construction_comment'] ?? ''),
        ':sort_order'           => $sort,
    ]);

    $inserted++;
}

fclose($fh);

echo "Done. Inserted: $inserted  |  Skipped (no): $skipped\n";
