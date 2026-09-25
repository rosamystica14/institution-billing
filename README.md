# Institution Billing & Fee Management System

A complete, production-ready fee billing system for a single-staff institution (coaching center, tuition, training institute, etc.). No login system — it opens straight to the Dashboard.

**Tech stack:** PHP 8+ · SQLite (via PDO) · Bootstrap 5 · Vanilla JS/AJAX · FPDF (PDF receipts) · WhatsApp Cloud API

---

## 1. Software You Need to Install

| Software | Purpose | Notes |
|---|---|---|
| **Laragon** (or XAMPP/WAMP) | Local PHP + Apache environment | [laragon.org](https://laragon.org) — free, includes PHP, Apache, and CLI tools |
| **PHP 8.0+** | Runs the app | Comes bundled with Laragon. Must have `pdo_sqlite` and `curl` extensions enabled (enabled by default in Laragon) |
| **A modern browser** | To use the app | Chrome, Edge, Firefox |
| Nothing else! | | No Composer, no Node.js, no MySQL required. The FPDF PDF library is already included in `/lib/fpdf`. |

---

## 2. Project Structure

```
institution-billing/
├── ajax/                 # All AJAX endpoints (student/course/payment CRUD, reports, whatsapp)
├── assets/
│   ├── css/style.css     # Blue theme, responsive layout
│   ├── js/                # main.js (shared helpers) + one file per page
│   ├── img/                # default avatar placeholder
│   └── uploads/
│       ├── photos/        # student photo uploads
│       └── logo/          # institution logo upload
├── config/
│   ├── config.php         # BASE_URL, paths, timezone, currency
│   └── database.php       # PDO singleton, auto-creates DB on first run
├── database/
│   ├── schema.sql          # table definitions + foreign keys + indexes
│   ├── seed.sql             # sample courses/students/payments (first run only)
│   └── institution.sqlite   # created automatically the first time you open the app
├── includes/
│   ├── header.php, sidebar.php, footer.php  # shared layout
│   └── functions.php        # db(), sanitize(), receipt numbers, balance calc, WhatsApp sender
├── lib/fpdf/                # FPDF PDF library (bundled, no Composer needed)
├── pages/                    # dashboard, students, student_details, courses, payments, receipt, reports, settings
└── index.php                  # single front-controller / router
```

---

## 3. How to Run on Laragon

1. Install Laragon and start it. Click **Start All** so Apache + your PHP version are running.
2. Copy the whole `institution-billing` folder into Laragon's www root, normally:
   ```
   C:\laragon\www\institution-billing
   ```
3. Open **`config/config.php`** and confirm this line matches your folder name:
   ```php
   define('BASE_URL', '/institution-billing');
   ```
   (If you rename the folder, update this value to match.)
4. Open your browser and go to:
   ```
   http://localhost/institution-billing/
   ```
   Laragon also gives you a pretty URL like `http://institution-billing.test` automatically — either works.
5. That's it — **the SQLite database is created automatically** the first time the page loads (see next section). The Dashboard opens directly; there is no login screen.

**Folder permissions:** Laragon on Windows doesn't need special permissions. If you deploy on Linux/macOS later, make sure `database/`, `assets/uploads/photos/`, and `assets/uploads/logo/` are writable by the web server user (`chmod 755` is usually enough).

---

## 4. How the SQLite Database Is Created

You do **not** need to create it manually. The first time any page or AJAX call runs:

1. `config/database.php` checks if `database/institution.sqlite` exists.
2. If it doesn't, it creates the file and runs `database/schema.sql` (all tables: `students`, `courses`, `payments`, `whatsapp_logs`, `settings`, with proper foreign keys and indexes).
3. It then runs `database/seed.sql`, which inserts:
   - Default settings row
   - 4 sample courses
   - 3 sample students
   - 4 sample payments

**To reset the database at any time** (e.g. to wipe test data before going live), simply delete the file:
```
database/institution.sqlite
```
It will be recreated fresh (with the sample seed data) the next time you load the app. If you want to go live with **no sample data**, delete the file, then open `database/seed.sql` and delete everything below the `INSERT OR IGNORE INTO settings...` line before reloading the app — this keeps your institution settings row but skips the demo students/courses/payments.

**Want to inspect the database directly?** Laragon includes **DBeaver** and other DB tools, or you can use the free **DB Browser for SQLite** (https://sqlitebrowser.org) to open `database/institution.sqlite` directly.

---

## 5. How to Configure WhatsApp (Cloud API)

The system uses Meta's official **WhatsApp Cloud API** (not a third-party service). Every payment can automatically text the parent; every attempt (success or failure) is logged in **Settings → Recent WhatsApp Logs**.

### Step-by-step setup:
1. Go to **https://developers.facebook.com/apps** and create a new App → select **Business** type.
2. Add the **WhatsApp** product to your app.
3. In the WhatsApp → **API Setup** page, you'll see:
   - A **Temporary access token** (valid 24h — good for testing) or generate a **permanent token** via a System User for production.
   - A **Phone Number ID** (a numeric ID, not the phone number itself).
4. In the app, go to **Settings** page and paste in:
   - **Access Token**
   - **Phone Number ID**
   - **API Version** (defaults to `v19.0` — fine to leave as-is)
5. Click **Save Settings**.
6. Test it: go to **Receive Fee**, record a payment for any student with the "Send WhatsApp confirmation" toggle on. Check **Settings → Recent WhatsApp Logs** to confirm delivery status.

### Important notes:
- **Test numbers:** While using a developer/test app, WhatsApp only delivers to numbers you've explicitly added as testers in the Meta dashboard. Move the app to **Live** mode (requires Business verification) to message any parent's real number.
- **Country code:** The system assumes **India (+91)** for any 10-digit number automatically (see `handleWhatsAppMessage()` / `sendWhatsAppMessage()` in `includes/functions.php`). If your institution is outside India, open that function and change:
  ```php
  if (strlen($cleanPhone) === 10) {
      $cleanPhone = '91' . $cleanPhone; // change '91' to your country code
  }
  ```
- **If sending fails**, the UI shows a toast with the exact error message returned by Meta (e.g. invalid token, unverified number, template required) and it's stored in the `whatsapp_logs` table.

---

## 6. Deploying to Live Hosting

This app has zero external dependencies beyond PHP + SQLite, so it works on almost any shared PHP host (Hostinger, cPanel hosts, etc.).

1. **Check requirements on your host:** PHP 8.0+, with `pdo_sqlite` and `curl` extensions enabled (ask your host's control panel → "Select PHP Version" → enable extensions if needed).
2. **Upload the files** via FTP/SFTP or your host's File Manager — upload everything inside `institution-billing/` to your target folder (e.g. `public_html/billing/` or the domain root).
3. **Update `config/config.php`:**
   ```php
   define('BASE_URL', '/billing'); // or '' if installed at the domain root
   ```
4. **Set folder permissions** (via FTP client or File Manager):
   - `database/` → `755` (needs to be writable so the SQLite file can be created)
   - `assets/uploads/photos/` and `assets/uploads/logo/` → `755`
5. Visit your domain — the database auto-creates itself on first load, exactly like on Laragon.
6. **Re-enter your WhatsApp Cloud API credentials** in Settings (these live in the database, and a fresh `institution.sqlite` won't carry over local values).
7. **Security recommended for production:**
   - Delete or rename the `seed.sql` sample data lines for students/payments (see Section 4) before going live so you don't show fake customers.
   - Since there's no login, restrict access to the app's URL itself — e.g. put it behind your host's password-protected directory feature, or a VPN/IP allowlist, since anyone with the link can see student data and record payments.
   - Confirm the bundled `.htaccess` files (in `database/`, `config/`, `includes/`, `lib/`) are actually being applied — Apache must have `AllowOverride All` enabled for the directory. On Nginx, `.htaccess` is ignored; add equivalent `location` blocks to deny access to those folders instead.

---

## 7. How to Create Backups

Because everything lives in **one SQLite file**, backup is simple:

- **Manual backup:** Just copy `database/institution.sqlite` somewhere safe (Google Drive, USB, etc.). Do this regularly, e.g. daily.
- **Automated backup (Windows/Laragon):** Create a scheduled task that copies the file nightly:
  ```
  copy C:\laragon\www\institution-billing\database\institution.sqlite C:\Backups\institution-%date%.sqlite
  ```
- **Automated backup (Linux hosting via cron):**
  ```bash
  0 2 * * * cp /home/youruser/public_html/billing/database/institution.sqlite /home/youruser/backups/institution-$(date +\%F).sqlite
  ```
- **Photos/logo:** also back up `assets/uploads/photos/` and `assets/uploads/logo/` since those are separate files, not stored inside the database.
- **Restoring:** Stop the web server (or just don't load the app), replace `institution.sqlite` with your backup copy, restart.

---

## 8. Common Troubleshooting

| Problem | Likely Cause / Fix |
|---|---|
| Blank white page | Turn on PHP errors: in `config/config.php`, confirm `error_reporting(E_ALL)` and `display_errors=1` are set (they are, by default). Check Laragon's Apache error log. |
| "Database connection failed" | The `database/` folder isn't writable. On Windows/Laragon this is rare; on Linux hosting run `chmod 755 database/`. |
| Links go to a 404 page | `BASE_URL` in `config/config.php` doesn't match your actual folder/URL path. Update it. |
| Photos/logo don't display after upload | Check that `assets/uploads/photos/` and `assets/uploads/logo/` exist and are writable. They're created automatically, but some hosts restrict `mkdir()` — create them manually via FTP if needed. |
| WhatsApp message fails every time | Go to Settings and re-check the **Access Token** and **Phone Number ID** — tokens expire (24h for temporary tokens). Check **Recent WhatsApp Logs** for Meta's exact error text. |
| PDF receipt shows a blank/broken page | Make sure the `lib/fpdf/font/` folder (with the 4 `helvetica*.json` files) was uploaded along with `lib/fpdf/fpdf.php` — some FTP clients skip hidden-looking folders. |
| "Cannot delete this course" error | By design — a course can't be deleted while students are still enrolled in it. Reassign or remove those students first. |
| Currency symbol shows as "Rs." instead of ₹ in the PDF | This is intentional — the core PDF font (Helvetica) doesn't include the ₹ glyph, so the PDF receipt substitutes "Rs." automatically. The on-screen/print receipt (via the browser) always shows ₹ correctly. |
| Rupee/₹ symbol looks broken on-screen | Make sure your file editor saved all `.php` files as **UTF-8** (all files ship as UTF-8 already — only relevant if you manually edit them later). |

---

## 9. Final Testing Checklist

Before handing this off to real institution staff, walk through:

- [ ] Dashboard loads and shows correct totals (students, today's/total collection, pending fees)
- [ ] Add a course, edit it, confirm fee updates correctly
- [ ] Try deleting a course that has enrolled students → should be blocked with a clear message
- [ ] Add a new student with a photo → photo appears in the students list and detail page
- [ ] Edit a student, change their course → balance recalculates against new course fee
- [ ] Delete a student → confirmation dialog appears, and their payments are removed too
- [ ] Search students by name, mobile, and course — all should filter instantly
- [ ] Receive a partial payment → balance updates correctly, receipt number auto-generates (`RCPT-YYYY-00001` format)
- [ ] Receive a payment with "Send WhatsApp" checked, with valid API credentials → parent receives the message; log shows "sent"
- [ ] Receive a payment with invalid/missing WhatsApp credentials → toast shows a clear failure message; log shows "failed" with Meta's error text
- [ ] Open a receipt → Print button works, Download PDF produces a valid PDF with correct amounts
- [ ] Daily Report shows only today's payments and correct total
- [ ] Monthly Report shows the right month and correct total
- [ ] Pending Fee Report lists only students with balance > 0
- [ ] Student-wise Report shows full payment history for a chosen student
- [ ] Settings: update institution name/logo/address, confirm it reflects on Dashboard navbar and receipts
- [ ] Resize the browser to mobile width → sidebar collapses into a toggle menu, tables remain usable
- [ ] Refresh any page directly (not just via nav clicks) → page still loads correctly (no broken relative paths)

---

## Notes on Design Decisions

- **No login system** — as specified, this app is meant for a single staff member. If you later need multi-user access with permissions, that would require adding an authentication layer (sessions table, password hashing, login page) — not included here by design.
- **Receipt numbers** are generated as `RCPT-{YEAR}-{5-digit sequence}` and guaranteed unique via a database check-and-retry loop.
- **Balances** are always calculated live (`course fee − sum of all payments`) rather than stored as a running total, so they can never drift out of sync even if a payment is edited or deleted directly in the database.
- **All SQL uses PDO prepared statements** — no raw string concatenation into queries anywhere in the codebase.
- **All output is escaped** via `htmlspecialchars()` (the `e()` helper) to prevent XSS.
