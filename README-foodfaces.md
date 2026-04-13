# Food Faces — Project README

**URL:** `projects.tobyziegler.com/foodfaces/`  
**Local:** `http://foodfaces.test` (Laravel Herd)  
**Stack:** PHP 8.1 · MySQL · Vanilla JS · No frameworks

---

## What It Is

An archive of lunch-plate portraits made from food, originally posted to
Facebook starting January 2010. The site displays them chronologically,
12 per page, with a hero section, a "Face of the Moment" random column,
and a share card generator for reposting to Facebook or LinkedIn.

---

## File Inventory

| File | Purpose |
|---|---|
| `index.php` | Main page — hero, share card, gallery |
| `style.css` | Page stylesheet (depends on shared.css v2.4) |
| `foodfaces.js` | Random face swap, share card capture |
| `admin.php` | Admin panel — edit, exclude, restore faces |
| `admin.css` | Admin panel styles |
| `schema.sql` | Database table definition — run once |
| `import.php` | CSV → MySQL importer — run once |
| `db.php` | Database credentials — **gitignored, create manually** |
| `photos/` | Image folder — jpg files from Facebook export |

---

## First-Time Setup

### 1. Create the database (Herd)

```bash
# In Terminal, cd to the project folder first
cd ~/Library/CloudStorage/iCloudDrive/Sites/tobyjhmw/projects.tobyziegler.com/foodfaces
mysql -u root
```

```sql
-- Inside MySQL
CREATE DATABASE foodfaces;
USE foodfaces;
source schema.sql;
exit;
```

### 2. Create db.php
Fill in local credentials (file is gitignored — create manually on server too):
```php
$host = '127.0.0.1';
$db   = 'foodfaces';
$user = 'root';
$pass = '';
```

### 3. Place the CSV
Put `foodfaces_curation_fixed.csv` (the corrected version) in the project
folder alongside `import.php`. Rename it to `foodfaces_curation.csv` if
needed, or update the path in import.php.

### 4. Run the importer
```bash
php import.php
```
Expected: `Inserted: 131  |  Skipped (no): 66`

### 5. Add photos
Drop all `.jpg` files from the Facebook export into `photos/`.
Filenames must match the `filename` column in the database exactly.

### 6. Set admin password
Open `admin.php` and change `'changeme'` on line 13 to a real password.

### 7. Test locally
Open `http://foodfaces.test` in a browser.

---

## Database Schema

**Table: `foodfaces`**

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | Primary key |
| `filename` | VARCHAR(100) | e.g. `1220521231190.jpg` |
| `original_timestamp` | INT UNSIGNED | Unix timestamp from Facebook export |
| `face_date` | DATE | Display date, parsed from CSV |
| `title` | VARCHAR(255) | Label line: "Food Face for Thursday, January 7th, 2010." |
| `caption` | TEXT | Display caption — may be blank |
| `face_type` | VARCHAR(30) | See taxonomy below |
| `construction_date` | VARCHAR(50) | Curation metadata, reference only |
| `construction_comment` | TEXT | Curation metadata, reference only |
| `sort_order` | SMALLINT UNSIGNED | Import row order — never changes |
| `created_at` | TIMESTAMP | Auto |

**face_type taxonomy:**

| Value | Meaning |
|---|---|
| `foodface` | Standard entry |
| `foodface_hero` | Fixed hero image (row 2, col A of hero section) |
| `foodface_reveal` | Deconstructed salad reveal (row 2, col B of hero section) |
| `foodface,A` … `foodface,F` | Paired/connected sets — display as individual cards |
| `other` | Non-food-face entries — in DB but not displayed |
| `excluded` | Soft-deleted — hidden from gallery and random face |

---

## Hero Section Layout

Three equal columns, two rows:

```
Row 1: [ Lunch with a Personality copy    ] [ copy cont. ] [ Face of the Moment ]
Row 2: [ Hero face (fixed)               ] [ Salad reveal (fixed) ] [ ↑ cont.   ]
```

- Story copy: `grid-column 1/3, row 1`
- Face of the Moment (random): `grid-column 3, rows 1–2`, top-aligned
- Hero face: `grid-column 1, row 2` — fixed, always `foodface_hero`
- Salad reveal: `grid-column 2, row 2` — fixed, always `foodface_reveal`

The random face refreshes client-side via **Another one →** button.
Clicking it also updates the share card.

---

## Gallery

- 12 faces per page (`PAGE_SIZE` constant in index.php)
- Sorted by `sort_order` ascending — chronological, never changes
- Excludes `foodface_reveal`, `other`, and `excluded` types
- Prev/Next pagination with page X of Y and total count
- Cards are 3-across on desktop (`minmax(320px, 1fr)`)
- Paired sets (A/B/C…) show a small badge on the card thumbnail

---

## Caption Logic

`caption` is the display field. If blank, the card shows the title only.
`title` is always "Food Face for [Day], [Month] [date], [year]." format.
65 captions are intentionally blank pending a second curation pass.

---

## Admin Panel

**URL:** `foodfaces.test/admin.php` (local) · `…/foodfaces/admin.php` (production)  
**Auth:** Single password set as `ADMIN_PASSWORD` constant in admin.php

### What it does
- Lists all faces, 20 per page, in sort_order
- Inline edit: title, caption, face_type
- Exclude button: sets face_type to `excluded`, hides from gallery
- Restore button: returns excluded face to `foodface`
- Filter tabs: All · Excluded · Hero · Reveal

### What it does not do
- No image upload (photos are managed via file system)
- No permanent delete (use Exclude to hide; edit DB directly for hard delete)

---

## Share Card

Generated from whichever face is showing in "Face of the Moment."
Includes: photo, title, caption, link back to the Food Faces page.
Copy and Download use `html2canvas` (cdnjs CDN).

**Required script tag** — add to index.php before foodfaces.js loads:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
```

Posting is manual — copy or download, then post to Facebook or LinkedIn.
Automation deferred pending confirmation the daily posting habit sticks.

---

## Deployment (Namecheap → cPanel Git)

1. Push to GitHub
2. Pull in cPanel Git
3. Create `db.php` manually on server with production credentials
4. In cPanel phpMyAdmin: create `tobyjhmw_foodfaces` database, run `schema.sql`
5. SSH or cPanel terminal: `php import.php` (with CSV in place)
6. Confirm `photos/` folder exists and images are uploaded
7. Visit `/foodfaces/admin.php` and verify admin password works

**db.php is gitignored.** Never pushed. Create fresh on every environment.

---

## Known Gaps / Next Session

- 65 `caption` entries are blank — second curation pass pending
- `html2canvas` script tag not yet added to index.php (share card copy/download non-functional until added)
- No lightbox on gallery cards — clicking does nothing
- Daily social posting is manual for now; automation deferred
- Title/caption data fixes: run `foodfaces_update_captions.sql` against DB, and replace working CSV with `foodfaces_curation_fixed.csv`

---

*Part of Toby's Study — tobyziegler.com*
