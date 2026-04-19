# Food Faces - Project README

**URL:** `projects.tobyziegler.com/foodfaces/`
**Local:** `http://foodfaces.test` (Laravel Herd)
**Stack:** PHP 8.1 - MySQL - Vanilla JS - No frameworks

## What It Is

An archive of lunch-plate portraits made from food, originally posted to
Facebook starting January 2010. The site displays them chronologically,
12 per page, with a hero section, a share card generator for reposting
to Facebook or LinkedIn, and an admin panel for curating and scheduling
daily posts.

## File Inventory

| File | Purpose |
|---|---|
| `index.php` | Main page - hero, share cards, gallery |
| `style.css` | Page stylesheet (depends on shared.css v2.4) |
| `foodfaces.js` | Random face swap, canvas-based share card capture |
| `admin.php` | Admin panel - edit, exclude, restore, set today's face |
| `admin.css` | Admin panel styles |
| `schema.sql` | Database table definitions - run once |
| `import.php` | CSV to MySQL importer - run once |
| `db.php` | Database credentials - **gitignored, create manually** |
| `photos/` | Image folder - jpg files from Facebook export |

## First-Time Setup

### 1. Create the database (Herd)

```bash
cd ~/Library/CloudStorage/iCloudDrive/Sites/tobyjhmw/projects.tobyziegler.com/foodfaces
mysql -u root
```

```sql
CREATE DATABASE foodfaces;
USE foodfaces;
source schema.sql;
exit;
```

### 2. Create db.php

File is gitignored - create manually on every environment:

```php
<?php
$host   = '127.0.0.1';   // use localhost on Namecheap
$dbname = 'foodfaces';   // use tobyjhmw_foodfaces on Namecheap
$user   = 'root';        // use cPanel DB user on Namecheap
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}
```

### 3. Place the CSV and run the importer

Put `foodfaces_curation_fixed.csv` in the project folder alongside
`import.php`, then:

```bash
php import.php
```

Expected output: `Inserted: 131  |  Skipped (no): 66`

### 4. Add photos

Drop all `.jpg` files from the Facebook export into `photos/`.
Filenames must match the `filename` column in the database exactly.

### 5. Set admin password

Open `admin.php` and set `ADMIN_PASSWORD` to a real password.

### 6. Test locally

Open `http://foodfaces.test` in a browser.

## Database Schema

**Table: `foodfaces`**

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `filename` | VARCHAR(100) | e.g. `1220521231190.jpg` |
| `original_timestamp` | INT UNSIGNED | Unix timestamp from Facebook export |
| `face_date` | DATE | Display date, parsed from CSV |
| `title` | VARCHAR(255) | e.g. "Food Face for Thursday, January 7th, 2010." |
| `caption` | TEXT | Display caption - may be blank |
| `face_type` | VARCHAR(30) | See taxonomy below |
| `construction_date` | VARCHAR(50) | Curation metadata, reference only |
| `construction_comment` | TEXT | Curation metadata, reference only |
| `sort_order` | SMALLINT UNSIGNED | Import row order - never changes |
| `created_at` | TIMESTAMP | Auto |

**Table: `ff_settings`**

| Column | Type | Notes |
|---|---|---|
| `setting_key` | VARCHAR(64) PRIMARY KEY | e.g. `today_face_id` |
| `setting_value` | VARCHAR(255) | e.g. `42` |

Seed row required on first setup:

```sql
CREATE TABLE ff_settings (
    setting_key   VARCHAR(64)  PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL DEFAULT ''
);
INSERT INTO ff_settings (setting_key, setting_value)
VALUES ('today_face_id', '');
```

**face_type taxonomy:**

| Value | Meaning |
|---|---|
| `foodface` | Standard entry |
| `foodface_hero` | Fixed hero image in the pair row |
| `foodface_reveal` | Deconstructed salad reveal in the pair row |
| `foodface,A` through `foodface,F` | Paired sets - display as individual cards with badge |
| `other` | Non-food-face entries - in DB but never displayed |
| `excluded` | Soft-deleted - hidden from gallery and random selection |

## Hero Section Layout

Six-column grid, three rows:

```
Row 1: [ Lunch with a Personality (col 1-4) ] [ Face of the Moment (col 5-6) ]
Row 2: [ Pair note - full width (col 1-6)                                     ]
Row 3: [ Hero face (col 1-3)               ] [ Salad reveal (col 4-6)        ]
```

- Story copy: `grid-column 1/5, row 1`
- Face of the Moment (random): `grid-column 5/7, row 1`
- Pair note: `grid-column 1/7, row 2` - full-width italic caption explaining the face/meal relationship
- Hero face: `grid-column 1/4, row 3` - fixed, always `foodface_hero`
- Salad reveal: `grid-column 4/7, row 3` - fixed, always `foodface_reveal`

The random face refreshes client-side via the "Another one" button.
Clicking it also updates the Face of the Moment share card.

## Share Cards

Two cards side by side in the Daily Post section:

- **Today's Face (left)** - set manually in the admin panel; stays constant
  until changed. Shows a "no face set" message if the admin hasn't picked one.
- **Face of the Moment (right)** - the same random face from the hero section;
  updates when the user clicks "Another one."

Each card has its own Copy image and Download button. Capture is done via
a custom canvas renderer (`buildCanvas` in foodfaces.js) - no html2canvas
dependency. The renderer draws the photo at full width, then the title,
caption, and meta text on a white background below it.

On browsers that block clipboard write (mainly Safari), Copy image opens
a modal showing the rendered card as an image the user can long-press or
right-click to copy manually.

Posting is manual - copy or download, then post to Facebook or LinkedIn.

## Gallery

- 12 faces per page (`PAGE_SIZE` constant in index.php)
- Sorted by `sort_order` ascending - chronological, never changes
- Excludes `foodface_reveal`, `other`, and `excluded` types
- Prev/Next pagination with page X of Y and total count
- Cards are 3-across on desktop (`minmax(320px, 1fr)`)
- Paired sets show a letter badge on the card thumbnail

## Caption Logic

`caption` is the display field. If blank, the card shows the title only.
`title` is always in "Food Face for [Day], [Month] [date], [year]." format.
65 captions are intentionally blank pending a second curation pass.

## Admin Panel

**URL:** `foodfaces.test/admin.php` (local) - `.../foodfaces/admin.php` (production)
**Auth:** Single password set as `ADMIN_PASSWORD` constant in admin.php

### What it does

- Lists all faces 20 per page in sort_order
- Inline edit: title, caption, face_type
- Set as Today: writes the face ID to `ff_settings`; highlights the current
  today face with a green border and "Today" badge on the thumbnail
- Exclude: sets face_type to `excluded`, hides from gallery and random selection
- Restore: returns an excluded face to `foodface`
- Filter tabs: All - Excluded - Hero - Reveal

### What it does not do

- No image upload (photos are managed via file system)
- No permanent delete (use Exclude to hide; edit DB directly for hard delete)

## Deployment (Namecheap)

1. Push to GitHub
2. Pull in cPanel Git Version Control
3. Create `db.php` manually on server with production credentials
4. In cPanel phpMyAdmin: create `tobyjhmw_foodfaces` database, import
   mysqldump export from Herd (includes both tables and all data)
5. Confirm `photos/` folder exists and all images are uploaded
6. Visit `/foodfaces/admin.php` and confirm login and face list work
7. Set a today face and confirm the share card section renders correctly

To export from Herd for import to Namecheap:

```bash
mysqldump -u root -p --host=127.0.0.1 --port=3306 --add-drop-table foodfaces > ~/Desktop/foodfaces-export.sql
```

Then import via phpMyAdmin Import tab on Namecheap.

**db.php is gitignored.** Never committed. Create fresh on every environment.

## Design System

Depends on shared.css v2.4 loaded from the canonical absolute URL:
`https://tobyziegler.com/assets/shared.css`

Page-specific styles are in `style.css`. Admin styles are in `admin.css`.
Both files follow the tobyziegler-web token system - no raw hex values,
no dead tokens, no em dashes anywhere in the codebase.

## Known Gaps / Next Session

- 65 `caption` entries are blank - second curation pass pending
- No lightbox on gallery cards - clicking does nothing
- Daily social posting is manual for now; automation deferred
- Bookshelf entry on The Study (tobyziegler.com) not yet built

---

*Part of Toby's Study - tobyziegler.com*
