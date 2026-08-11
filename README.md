# UTM Tracking Taxonomy Manager

A multi-tenant web app that replaces the `UTM_Parameter.xlsx` workflow: each company
(tenant) gets its own login and manages its own landing pages, UTM/event taxonomy, and
auto-generated tracking snippets — no more hand-typed spreadsheet rows.

Built as plain PHP 8 + MySQL/PDO with **no framework and no Composer dependency**, so it
runs on ordinary cPanel-style shared hosting: upload the files, import one SQL file via
phpMyAdmin, edit one config file.

## What's in it

Every sheet from the original spreadsheet is now a tenant-scoped module, plus a few new
ones to round the workflow out:

| Module | Replaces | Notes |
|---|---|---|
| Verticals | `service_vertical` sheet | name + short code (e.g. Digital Transformation / DT) |
| Services | (implicit in `URL` sheet) | belongs to a Vertical |
| Page Types | (implicit `page_type`/`page_type_short`) | e.g. `assessment_landing_page` / `lp` |
| Form Types | `form_type` sheet | |
| Form Locations | `form_location` sheet | |
| Funnel Stages | `funnel_stage` sheet | ordered |
| Events | `events` sheet | GA4 event name registry |
| Lead Magnets | `lead_magnet_name` sheet | + asset URL, owning vertical/service |
| Traffic Types | `utm_cv` sheet | mar / abm |
| **Landing Pages** | new | name, URL, type, owner, status (draft/live/archived) |
| **Tracking Configurations** | `URL` sheet | links a page to the taxonomy, auto-builds a unique `form_id`, live-renders both tracking snippets |
| **Snippet Templates** | the sheet's hardcoded GA4/Zoho code blocks | per-tenant, editable, token-based (`{{form_id}}`, `{{service_vertical}}`, …) |
| **Campaign / UTM Link Builder** | new | utm_source/medium/campaign/term/content → a generated tracking URL |
| Users & Roles | new | Owner / Admin / Editor / Viewer per tenant |
| Company Settings | new | tenant profile |
| Super Admin | new | platform back office — list/suspend/reactivate tenant workspaces |

All master-data modules include `status` (active/inactive), a description field, full
audit trail (`created_by`/`updated_by`/timestamps), and CSV export. Landing Pages and
Tracking Configurations also support CSV export for bulk review.

## How multi-tenancy works

- **One shared MySQL database.** Every table carries a `tenant_id`; every query in
  `App\Models\BaseModel` is automatically scoped by it, so a bug in a controller can
  never leak another tenant's rows. This fits shared hosting, where you typically only
  get one (or very few) MySQL databases.
- **Tenant resolved by logged-in user**, not by subdomain — `users.tenant_id` decides
  which workspace you're in. No wildcard DNS or per-tenant vhost needed; the whole app
  runs on a single domain.
- **Self-service signup**: registering creates a new company (tenant) and its first user
  as Owner, in one transaction.
- **Snippet Templates are per-tenant and editable** because every company's GA4/CRM setup
  differs — new tenants are seeded with two defaults that reproduce the original sheet's
  GA4 dataLayer push and its CRM lead-object snippet.

## Deploying on shared hosting (cPanel-style, no SSH required)

1. **Create a MySQL database and user** in cPanel → MySQL Databases. Note the DB name,
   username, and password (cPanel usually prefixes them with your account name).
2. **Import the schema**: cPanel → phpMyAdmin → select your new database → Import →
   choose `database/schema.sql` → Go. This creates every table and seeds one demo tenant
   (login `demo@solidpro-es.com` / `Passw0rd!` — **change this password immediately**, or
   just delete that tenant's row from `tenants`/`users` once you've explored the app).
3. **Upload the files** (FTP or File Manager) to your hosting account.
4. **Copy the config**: rename/copy `config/config.sample.php` to `config/config.php` and
   fill in your DB credentials and `app.url` (your site's base URL, no trailing slash).
   Set `app.debug` to `false` once you've confirmed everything works.
5. **Point your document root at `/public`** if your host supports it (recommended —
   keeps `/app`, `/config`, `/database` outside the web-servable path). If it doesn't,
   leave the root-level `.htaccess` in place; it transparently forwards everything to
   `/public` from your account's root.
6. Visit your site — you should land on the login page. Log in with the demo account or
   register a new company from `/index.php?r=register`.

No `composer install`, no build step, no queue/cron/Redis required.

## Local development

```bash
# 1. Create + seed a local database
mysql -u root -e "CREATE DATABASE utm_taxonomy CHARACTER SET utf8mb4;"
mysql -u root utm_taxonomy < database/schema.sql

# 2. Configure
cp config/config.sample.php config/config.php
# edit config/config.php with your local DB credentials

# 3. Run
php -S localhost:8000 -t public
```

Then open `http://localhost:8000/index.php?r=login` and sign in with the seeded demo
account (`demo@solidpro-es.com` / `Passw0rd!`), or register a new workspace.

## Security notes

- All queries go through PDO prepared statements.
- All output is escaped via `htmlspecialchars()` (see `e()` helper).
- Every form carries a CSRF token, verified server-side.
- Passwords are hashed with `password_hash()` (bcrypt); sessions are regenerated on
  login/logout.
- Role checks (`viewer < editor < admin < owner`) gate every write/delete action, both
  server-side (`Auth::requireRole()`) and hidden in the UI.
- `config/config.php` is git-ignored — never commit real database credentials.

## Extending it

- New master-data module: copy the pattern in `app/Models/Vertical.php` +
  `app/Controllers/VerticalController.php` + `app/Views/verticals/` — `BaseModel` and
  `BaseController` handle tenant scoping, CSRF, roles, and CSV export for you.
- New snippet integration (e.g. a different CRM): tenants can just add a new Snippet
  Template from Settings → Snippet Templates, no code change needed.
