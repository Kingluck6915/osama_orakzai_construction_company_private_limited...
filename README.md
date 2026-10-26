# Osama Orakzai Construction Company

A responsive construction-company website with a private, password-protected admin area for projects and project enquiries.

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `fileinfo`, and `mbstring` enabled
- MySQL 8.0+ or MariaDB 10.5+
- Apache with `.htaccess` support (or equivalent server rules)
- HTTPS for production

## Local setup with XAMPP

1. Copy this folder into `C:\xampp\htdocs\osama-construction`.
2. Start Apache and MySQL in the XAMPP control panel.
3. Create a database named `osama_construction` in phpMyAdmin.
4. Import `database/schema.sql` into that database.
5. From PowerShell in the website folder, run `& "C:\xampp\php\php.exe" scripts\create-app-key.php`. It saves a private random key to `.app-key` (already denied by the supplied Apache rules). Keep this file private and backed up.
6. Open the website, then visit `/admin/setup.php`. Enter your preferred admin email and a unique password of at least 12 characters. The first account is created once; after that, the setup page redirects to login.
7. Open `/admin/login.php` directly to sign in; the public site does not link to the admin area.

The application detects its URL prefix automatically, so it can run from a folder under XAMPP `htdocs` or from a domain root.

For a database created before the services CMS was added, import `database/migrations/001_services.sql` instead of re-importing the full schema. This adds the services table without changing existing project, admin, or inquiry data.

## Admin

- The dashboard summarizes projects and new enquiries.
- Projects and services can be created, edited, published as drafts, reordered, and removed. JPG, PNG, and WebP project images are accepted up to 5 MB.
- Website project enquiries are stored in MySQL. Admins can review contact information and the submitted message, update its status, and save private notes.
- Login uses PHP password hashing, session ID rotation, idle expiry, CSRF tokens, and a per-IP-hash temporary login throttle. All admin pages enforce authorization on the server.
- The first admin account is created through the one-time `/admin/setup.php` page, after the private application key has been created. The setup page disables itself as soon as an account exists.
- Keep backups of both the MySQL database and `uploads/projects/`. Project image uploads are stored on disk and are not included in the SQL dump.

## Configuration and deployment

Set these environment variables on the server, or use equivalent private host configuration:

| Variable | Purpose |
| --- | --- |
| `DB_HOST` | MySQL host (default `127.0.0.1`) |
| `DB_NAME` | Database name (default `osama_construction`) |
| `DB_USER` | Database user (default `root` for local XAMPP) |
| `DB_PASSWORD` | Database password |
| `APP_KEY` | Unique, random secret used to HMAC login-throttle identifiers |
| `SITE_URL` | Canonical site URL if needed by your host |

If `APP_KEY` is not provided as an environment variable, the app reads it from the private `.app-key` file created by `scripts/create-app-key.php`. Do not publish or commit that file. On Apache, the supplied `.htaccess` denies direct access to it.

Use a dedicated database account with only the privileges this site needs. Do not use the XAMPP `root` account in production. Keep `config.php` and the database inaccessible to direct web requests. The supplied root `.htaccess` denies direct access to configuration/schema files, disables directory listings, and adds basic response headers; retain equivalent rules if your host uses Nginx or another server.

Ensure `uploads/projects/` is writable by PHP, but not executable. The included `.htaccess` blocks script files in the upload directory on Apache. Enforce HTTPS and set the PHP `session.cookie_secure` setting through your host when serving the production website over HTTPS.

The website uses remote architectural photography from Unsplash as visual backgrounds. The portfolio itself starts empty rather than presenting sample work as real company projects.

## Known scope

The implemented admin CMS covers projects, services, and project enquiries. It does not include a broader editor for team profiles, testimonials, job listings, or blog posts. No office address, client names, project history, certifications, or performance statistics have been invented.

## Quick verification checklist

- Create the database and admin account, then sign in and sign out.
- Confirm `/admin/index.php`, `/admin/projects.php`, `/admin/services.php`, and `/admin/inquiries.php` redirect to sign-in when logged out.
- Add a draft project and verify it is absent from the public site; publish it and verify its content appears.
- Add, edit, reorder, and unpublish a service; confirm only published services appear on the public site.
- Submit the public project form and check the new enquiry and private notes in admin.
- Try an unsupported or oversized image and confirm it is rejected.
- Check the navigation, forms, and admin tables at phone, tablet, and desktop widths.
- If the database is missing, the website displays a setup message instead of PHP internals; check the PHP error log for technical details.
