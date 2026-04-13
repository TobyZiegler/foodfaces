# Food Faces — Project README

**URL:** `projects.tobyziegler.com/foodfaces/`  
**Local:** `http://foodfaces.test` (Laravel Herd)  
**Stack:** PHP 8.1 · MySQL · Vanilla JS · No frameworks

---

## What It Is

An archive of lunch-plate portraits made from food, originally posted to
Facebook starting January 2010. The site displays them chronologically, one
card at a time, with a hero section, a random daily face, and a share card
generator for reposting to Facebook or LinkedIn.

---

## File Inventory

| File | Purpose |
|---|---|
| `index.php` | Main page — hero, share card, gallery |
| `style.css` | Page stylesheet (depends on shared.css v2.4) |
| `foodfaces.js` | Random face, load more, share card capture |
| `schema.sql` | Database table definition — run once |
| `import.php` | CSV → MySQL importer — run once |
| `db.php` | Database credentials — **gitignored, create manually** |
| `photos/` | Image folder — jpg files from Facebook export |

---

## First-Time Setup

### 1. Create the database (Herd)
See **Herd MySQL Setup** section below.

### 2. Create db.php
Copy the template and fill in local credentials:
```php
$host = '127.0.0.1';
$db   = 'foodfaces';
$user = 'root';
$pass = '';
```
Production credentials go in the commented block beneath.
This file is gitignored — create it manually on the server too.

### 3. Place the CSV
Put `foodfaces_curation.csv` in the same folder as `import.php`.

### 4. Run the importer
```bash
php import.php
```
Expected output: `Inserted: 131  |  Skipped (no): 66`

### 5. Add photos
Drop all `.jpg` files from the Facebook export into `photos/`.
Filenames must match the `filename` column in the database exactly.

### 6. Test locally
Open `http://foodfaces.test` in a browser.

---

## Herd MySQL Setup

Herd ships with MySQL but does not create your databases for you.
The easiest way in is via **TablePlus** (free tier works fine).

### Option A — TablePlus (recommended)

1. Open TablePlus → New Connection → MySQL
2. Host: `127.0.0.1` · Port: `3306` · User: `root` · Password: *(leave blank)*
3. Connect → click **+** (new database) → name it `foodfaces` → Create
4. Open a new query tab: **File → New Query** (or ⌘T)
5. Paste the contents of `schema.sql` → Run (⌘R)
6. Confirm the `foodfaces` table appears in the left panel

### Option B — Herd CLI

```bash
# Open the MySQL shell
mysql -u root

# Inside the shell:
CREATE DATABASE foodfaces CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foodfaces;
source /path/to/your/foodfaces/schema.sql;
exit;
```

Replace `/path/to/your/foodfaces/` with the actual path to your local project folder.

### Option C — php artisan / Sequel Pro / any MySQL GUI
Any MySQL client that can connect to `127.0.0.1:3306` with user `root`
and no password will work. Create the database, then run `schema.sql`.

---

## Database Schema

**Table: `foodfaces`**

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `filename` | VARCHAR(100) | e.g. `1220521231190.jpg` |
| `original_timestamp` | INT UNSIGNED | Unix timestamp from Facebook export |
| `face_date` | DATE | Display date, parsed from CSV |
| `title` | VARCHAR(255) | Label line from original Facebook post |
| `caption` | TEXT | Curated display caption (may be blank) |
| `face_type` | VARCHAR(30) | See taxonomy below |
| `construction_date` | VARCHAR(50) | Curation metadata, reference only |
| `construction_comment` | TEXT | Curation metadata, reference only |
| `sort_order` | SMALLINT UNSIGNED | Import row order — never changes |
| `created_at` | TIMESTAMP | Auto |

**face_type taxonomy:**

| Value | Meaning |
|---|---|
| `foodface` | Standard entry |
| `foodface_hero` | The fixed hero image (left of hero section) |
| `foodface_reveal` | The deconstructed salad reveal (right of hero section) |
| `foodface,A` … `foodface,F` | Paired/connected sets — display as individual cards |
| `other` | Non-food-face entries — imported but not displayed |

---

## Caption Logic

The page always reaches for `caption` first.
If `caption` is blank, the card renders with the title only — no fallback
to the raw `title` field in the card body. Blank captions are valid;
they will be filled in during a second curation pass.

---

## Share Card

The share card is generated from whichever random face is currently
showing in the hero column. Clicking **Another one →** updates both
the random face and the share card simultaneously.

Copy and Download use `html2canvas` (loaded from cdnjs). If the CDN
is unavailable, both buttons will alert and gracefully fail.

The share card includes:
- The face photo
- Title and caption
- A link back to `projects.tobyziegler.com/foodfaces/`

Posting is manual — copy or download the card, then post to
Facebook or LinkedIn. Automation is deferred pending confirmation
that the daily posting habit sticks.

---

## Deployment (Namecheap → cPanel Git)

1. Push to GitHub as normal
2. Pull in cPanel Git → confirm
3. Create `db.php` manually on the server with production credentials
4. Run `schema.sql` and `import.php` once on the server
   (either via cPanel phpMyAdmin for the schema, or SSH for import.php)
5. Confirm `photos/` folder exists and images are uploaded

**Note:** `db.php` is gitignored. It will never be pushed.
Create it fresh on every new environment.

---

## Known Gaps (as of initial build)

- 65 `caption` entries are blank — second curation pass pending
- `html2canvas` script tag needs to be added to `index.php` before
  the share card copy/download buttons will work:
  ```html
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  ```
- No lightbox on gallery cards yet — clicking a card does nothing
- No admin interface — all edits go through the database directly or
  via a fresh CSV import

---

*Part of Toby's Study — tobyziegler.com*
