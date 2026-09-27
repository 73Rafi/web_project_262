# Backend smoke tests

Use a **disposable local database**, never a database containing real accounts.
The test creates users, files, papers, projects, replies and notifications. It also
changes the generated test user's password and account status.

1. Import `backend/database.sql` into the disposable MySQL/MariaDB instance.
2. Configure `backend/config.local.php` to connect to it.
3. Register a test admin through the portal and promote that account using the
   SQL command in the root README.
4. Start PHP from the project root: `php -S 127.0.0.1:8000 router.php`.
5. Set `PORTAL_TEST_ADMIN_EMAIL` and `PORTAL_TEST_ADMIN_PASSWORD` in the terminal.
6. Run `python tests/smoke.py http://127.0.0.1:8000` (Python standard library only).

The checks cover authentication, CSRF, role restrictions, private CVs, invalid
uploads, paper drafts/review/search/bookmarks, project ownership/membership,
discussion replies, notifications, profile editing, password resets, session
revocation, account disabling and login attempt limits.

The test leaves its generated records in the disposable database. Uploaded files
are in `backend/uploads`. Do not delete existing real uploads when cleaning tests.
Browser layout and email delivery are not tested. Password reset is admin-assisted
and does not send email.
