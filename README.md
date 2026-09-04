# Client Sales Presentation Portal

A Laravel 13 + MySQL sales/demo presentation platform designed for software companies that want to send **one private link on WhatsApp** instead of separate demo links, credentials and proposal files.

The application includes a compact Fastkart-inspired green admin interface and a custom dynamic one-page client presentation.

## Included modules

- Secure admin login
- Dashboard with hot prospects, live visitors and recent activity
- Clients
- Leads
- Follow-ups (calls, WhatsApp, email, meetings and other next actions)
- Product master
  - features
  - media/screenshots/videos
  - encrypted demo credentials
  - demo URLs
- Private client presentations
  - random public tokens
  - optional expiry
  - section enable/disable
  - section ordering
  - client-specific text
  - pricing/investment
- Detailed analytics
  - each page open
  - section views
  - active time by section
  - scroll depth
  - every tracked button click
  - demo/external URL opens
  - credential show/copy actions
  - video progress for uploaded HTML5 videos
  - device/browser/OS
  - IP address and source
  - approximate country/city when supplied by the hosting/CDN headers
  - repeat sessions / visitor ID
- Engagement scoring
- Sales team and role protection
- Company settings
- Reusable CSV import/export
- Global search
- Multiple filters on list pages
- Safe uploads under `public/upload`

## Technical stack

- Laravel 13
- PHP 8.3+
- MySQL / MariaDB
- Blade
- Bootstrap assets from the supplied Fastkart template
- Custom lightweight CSS/JavaScript
- No Node/Vite build is required in production
- No Redis, WebSockets or cron job is required for core operation

## Shared hosting deployment

### 1. Requirements

Enable/select PHP 8.3 or newer and make sure these common extensions are available: PDO MySQL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON and Fileinfo.

### 2. Upload project

Upload/extract the project outside the public web root where possible, for example:

`/home/ACCOUNT/client_webpage`

Set your subdomain/document root to:

`/home/ACCOUNT/client_webpage/public`

Do not point the public domain to the Laravel project root.

### 3. Create MySQL database

Create a database and database user in cPanel, grant the user all privileges for that database, then edit `.env`:

```env
APP_URL=https://demo.yourcompany.com
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_strong_password
```

Keep `APP_DEBUG=false` in production.

### 4A. Preferred database setup (Terminal/SSH)

From the project directory:

```bash
php artisan migrate --seed --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4B. phpMyAdmin fallback

If your hosting does not provide Terminal/SSH, import:

`database/sql/client_sales_portal.sql`

into the empty MySQL database using phpMyAdmin.

### 5. Permissions

Laravel must be able to write to:

- `storage/`
- `bootstrap/cache/`
- `public/upload/`

Typical shared-hosting permissions are 755 for folders, with the hosting account owner having write permission.

## Initial admin account

When using the seeder or SQL fallback:

- Email: `admin@example.com`
- Password: `Admin@12345`

**Change the email and password immediately after first login.**

## Important APP_KEY rule

Demo passwords are encrypted using Laravel's encrypted model cast. Configure and keep the same `APP_KEY` after you start saving demo credentials. Changing the application key later will make already-encrypted credentials unreadable.

## CSV import/export

Example import templates are in:

`database/import-templates/`

Use the Export CSV button from a module to get the exact field structure. Imports are streamed row-by-row to keep memory use reasonable on shared hosting. CSV exports also protect cells beginning with spreadsheet-formula characters.

## Upload security

All application-managed uploads are stored below:

`public/upload/`

The included `.htaccess` disables directory listing and denies execution/access for PHP/script-like extensions. Controllers also validate file type, size and MIME content before saving.

## Analytics behavior

The public presentation creates a new session UUID for each page visit and a browser visitor UUID for repeat-visit recognition. JavaScript records meaningful interactions through the same Laravel application.

Heartbeats update the live session but are **not stored as individual event rows**, reducing database growth. Section time is counted only while the page is visible and recently active, so leaving a tab open does not inflate engagement as heavily.

The system intentionally does not request precise GPS location. Country/city are populated only when available from hosting/CDN request headers; otherwise the admin sees the visitor IP address and device information.

## Roles

- `admin` — full access: users, settings, products, and every client/lead/presentation
- `salesperson` — own clients, leads, presentations and analytics only

Product master and User/Settings management are restricted to admins.

## Main folders

```text
app/Http/Controllers/Admin/
  Analytics/
  Auth/
  Client/
  Dashboard/
  Lead/
  FollowUp/
  Presentation/
  Product/
  Search/
  Setting/
  User/

app/Models/
  Analytics/
  Client/
  Lead/
  FollowUp/
  Presentation/
  Product/
  Setting/

app/Services/
  Analytics/
  Common/
  ImportExport/
  Presentation/

resources/views/admin/
  analytics/
  auth/
  clients/
  dashboard/
  leads/
  followups/
  layouts/
  partials/
  presentations/
  products/
  search/
  settings/
  users/

resources/views/frontend/presentation/

public/upload/
```

## Production notes

- Use HTTPS.
- Keep `APP_DEBUG=false`.
- Change the default administrator credentials.
- Do not expose the Laravel project root publicly; expose only `/public`.
- Use a strong database password.
- Back up MySQL and `public/upload` regularly.
- If using Cloudflare, the application can store the country header automatically.
- Large videos are better hosted on a streaming/object-storage service; uploaded MP4/WebM is supported for smaller media.
