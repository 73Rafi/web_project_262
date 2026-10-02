# UIU Research Portal — PHP + MySQL

Simple PHP pages, HTML forms, sessions and MySQL. No framework, Composer,
Node.js, npm or JavaScript build step is required.

## Run with XAMPP

1. Use PHP **8.2 or newer** with `mysqli` and `fileinfo` enabled.
2. Copy this folder into `C:/xampp/htdocs/web_project_262`.
3. Start **Apache** and **MySQL** in the XAMPP Control Panel.
4. Open `http://localhost/phpmyadmin` and import `backend/database.sql`.
5. Check `backend/config.php`: database `research_portal`, user `root`, empty
   password, host `127.0.0.1`, port `3306`. Change these if needed.
6. Open `http://localhost/web_project_262/` in the browser.
7. Register with a PDF CV (maximum 5 MB), then sign in.

PHP does not run by double-clicking HTML files or using VS Code Live Server.
Use Apache or the PHP development server. Old `.html` links redirect to `.php`.

For 50 MB paper uploads, set `upload_max_filesize=50M` and `post_max_size=55M`
in XAMPP's `php.ini`, then restart Apache. See `backend/php.ini.example`.
The PHP process needs write permission on `backend/uploads`.

## PHP development server

Start MySQL, import the SQL, and configure `backend/config.php`. From the root:

```powershell
php -d upload_max_filesize=50M -d post_max_size=55M -S 127.0.0.1:8000 router.php
```

Open `http://127.0.0.1:8000`. Keep `router.php` in the command: it blocks direct
access to backend files and CVs. On Apache, `backend/.htaccess` provides this
restriction; Apache must allow `.htaccess` authorization rules.

## Existing database

Back it up first. If it contains the ORIGINAL Node.js `users` table, import
`backend/upgrade.sql` **once**, then `backend/database.sql`. This adds columns and
tables without deleting accounts. Do not run the upgrade on a fresh database or
run it twice. Existing bcrypt hashes and CV paths are supported. Review old admin
accounts: the previous form allowed users to select Admin themselves.

## Create your admin account

To create a **new admin without registering first**, open a terminal in the project
folder and run:

```powershell
php backend/create_admin.php
```

If PHP is not on PATH, use XAMPP's PHP executable:

```powershell
& "C:\xampp\php\php.exe" backend/create_admin.php
```

Enter the new admin's name, email and password, then confirm the password. The
terminal displays characters while typing. The script saves a password hash and
does not require a CV. Sign in through `signIn.php` to open the Admin Panel.
This setup script works only in the terminal, not through a public browser URL.

Already signed in as an admin? Open **Admin Panel → Add New Admin**. Fill in the
new account details and confirm using your own current password.

Alternatively, to promote an **existing registered user**, run this in phpMyAdmin
with that user's actual email (`your-email@example.com` is only a placeholder):

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-email@example.com';
```

Sign in again. Admins can approve/reject papers/projects, review CVs,
enable/disable non-admin accounts and handle password reset requests.
Public registration can create only Student or Teacher accounts.

## Working features

- Registration, hashed passwords, login sessions and POST logout.
- Dashboard counts and homepage research loaded from the database.
- PDF uploads, drafts, submission, admin review and paper downloads.
- Research search by title/author/keyword, category, department and year.
- Free Crossref integration for CSE papers, topic/keyword search, abstracts, PDF links
  and next/previous pages in Research Explorer.
- Save/unsave papers and view your own submissions.
- Create projects, join/leave recruiting projects and update owner status.
- Forum categories, new discussions and replies.
- Profile/bio editing, password changes and notifications with mark-all-read.
- Admin-assisted password reset with expiring, single-use links.

Reset flow: user requests a reset; admin verifies identity outside the portal;
admin creates a 30-minute link and privately shares it with the account owner;
user chooses a new password. **No automatic email service is configured.**

Uploads accept PDF only. Account email is read-only in Settings. UIU lists show up to
100 newest records. Local-list pagination, following, profile photos and SMTP
delivery are not implemented in this simple version.

## CSE papers from Crossref

Research Explorer opens **CSE Papers (Crossref)** by default. The **UIU Papers** option
contains your database papers and existing save/unsave controls. External Crossref
papers open at their source; they are not inserted into the database or bookmarks.

`backend/crossref.php` calls the [Crossref API](https://www.crossref.org/documentation/retrieve-metadata/rest-api/)
with PHP cURL and reads JSON metadata. No API key is needed. Enable PHP `curl`
and allow outbound HTTPS. Keep certificate verification
enabled; if PHP reports a certificate error, configure a valid `curl.cainfo` CA bundle.

Results are cached for 30 minutes in `backend/cache` (created automatically; must
be writable). Requests use one connection at a time, with at least 3 seconds between
calls to keep traffic low. The
site waits before retrying failed requests and shows cached results when possible.
The page labels Crossref as the source. Searches combine the selected CSE topic
with your keywords and rank journal articles by relevance; this is a keyword
search, not a strict subject classification. Abstracts and PDF links appear only
when provided by the publisher. Full-text access depends on the publisher.
No database re-import is needed for this integration.

## Where the code is

| File | Purpose |
| --- | --- |
| `backend/config.php` | Database settings |
| `backend/db.php` | Connect to MySQL |
| `backend/common.php` | Small session, form, escaping and upload helpers |
| `backend/sidebar.php` | Shared sidebar, active menu links and account/sign-out section |
| `backend/database.sql` | Tables and relationships |
| `register.php`, `signIn.php`, `logout.php` | Account access |
| `upload.php`, `research-exploer1.php`, `download.php` | Papers |
| `backend/crossref.php` | Free CSE paper search and file cache |
| `Project.php`, `project_details.php` | Projects |
| `Community_Forum.php`, category pages, `discussion.php` | Forum |
| `profile.php`, `setting.php`, `notification.php` | Account pages |
| `admin_index.php` | Review and account management |
| `forgot_password.php`, `reset_password.php` | Password recovery |
| `portal.css` | Forms/content within existing layouts |
| `tests/smoke.py` | HTTP checks for a disposable test database |

Each main page starts with PHP to read/write the database, followed by HTML.
`?` placeholders in `execute_query()` keep user input separate from SQL.
`e()` escapes values before putting them into HTML. Keep the hidden CSRF token
and permission checks even though this is a beginner project.

Optional local overrides go in `backend/config.local.php` (ignored by Git):

```php
<?php
$db_port = 3306;
$db_password = 'your-local-password';
```

Old Node entry files were removed. An existing `backend/node_modules` folder is
unused. PHP does not need it. Do not commit private config or new uploads.

## Checks

See `tests/README.md` for smoke tests against a disposable database.
Check PHP syntax with `php -l filename.php`.
