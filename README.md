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
| **Campaign / UTM Link Builder** | new | pick a Landing Page or type any URL, choose an Ad Channel to auto-fill source/medium and reveal channel-specific extra parameters (e.g. Google Ads `{keyword}`/`{device}`/`{matchtype}`), live-previews the generated URL |
| **Ad Channels** | new | per-tenant channel presets (Google Ads Search, Meta Ads, LinkedIn Ads, Email, …) driving the Campaign builder's defaults |
| **Custom Variables** | new | tenant-defined data-layer keys beyond the built-in taxonomy (add/remove freely, fixed-list or free-text), scoped to show on Tracking Configs, Campaigns, or both |
| Users & Roles | new | Owner / Admin / Editor / Viewer per tenant |
| Company Settings | new | tenant profile + **Naming Conventions** (below) |
| Super Admin | new | platform back office — list/suspend/reactivate tenant workspaces |

All master-data modules include `status` (active/inactive), a description field, full
audit trail (`created_by`/`updated_by`/timestamps), and CSV export. Landing Pages and
Tracking Configurations also support CSV export for bulk review.

## Universal by design: Custom Variables + Naming Conventions

The taxonomy above (Verticals, Form Types, …) is just a starting point, not a hardcoded
schema. Two mechanisms make the app fit any company's dataLayer and naming scheme without
a code change:

- **Custom Variables** (sidebar → Content → Custom Variables): add a variable for
  anything your dataLayer needs that isn't already covered — an A/B test variant, an ad
  format, a partner code — as a fixed list you manage or free text typed in each time.
  Delete what you don't need. Each one becomes a `{{key_name}}` token usable in Snippet
  Templates and Naming Conventions, and shows up as a field on Tracking Configurations
  and/or Campaigns (your choice, per variable).
- **Naming Conventions** (Company Settings): per-tenant `{{token}}` patterns control how
  `form_id` and the Campaign name are built — same token syntax as Snippet Templates,
  covering both the built-in taxonomy and your own Custom Variables. For example, given
  Custom Variables `format`, `objective`, `date`, and `version`, the pattern
  `PA{{seq}}-{{service_vertical}}-{{service_name}}-{{channel}}-{{format}}-{{objective}}-{{date}}-{{version}}`
  produces `PA1-DT-CPQ-GA-RSA-Traffic-Aug2026-V1` — a real spreadsheet naming convention,
  reproduced without a spreadsheet formula.

## Roadmap

- **GTM / GA4 integration.** Snippet Templates and Custom Variables already model an
  arbitrary dataLayer shape token-by-token, which is what a future integration would
  read from — e.g. pushing a Snippet Template as a GTM variable/tag definition via the
  Tag Manager API, or pulling GA4's configured event names to seed Events automatically.
  Not built yet; today the app only generates the JS snippets for you to paste into GTM
  by hand.

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
6. **mod_rewrite must be on** — every link in the app (`/login`, `/verticals/edit/3`, ...)
   is a clean path, not `index.php?r=...`; the `.htaccess` file at whichever level is your
   document root is what turns that back into a real request behind the scenes. This is
   the default on essentially all cPanel/Apache shared hosting, so normally there's
   nothing to do — but if pages 404 after deploying, check cPanel → MultiPHP/Apache
   settings (or ask your host) that `.htaccess` overrides and `mod_rewrite` are enabled
   for your account.
7. Visit your site — you should land on the login page. Log in with the demo account or
   register a new company from `/index.php?r=register`.

No `composer install`, no build step, no queue/cron/Redis required.

## Deploying via cPanel Git Version Control (utm.easi7.in)

`.cpanel.yml` at the repo root drives cPanel's native **Git™ Version Control** deploy
feature — no FTP, no GitHub Actions, no credentials leaving the server. cPanel clones
this repo on the server itself; clicking **Deploy HEAD Commit** in its UI runs the
tasks in `.cpanel.yml`, which copy `app/`, `public/`, `database/` and `.htaccess` from
whatever commit is checked out into the live app directory (`$HOME/utm.easi7.in/`). It
never touches `config/config.php` or the database.

Read the numbered setup comments at the top of `.cpanel.yml` once before the first
deploy. Short version:

1. cPanel → **Git™ Version Control** → Create → clone this repo (branch `main`) into a
   working path like `repositories/utm_params` (NOT your live site folder).
2. cPanel → **Domains** → set `utm.easi7.in`'s document root to `utm.easi7.in/public`.
3. Upload `config/config.php` to `$HOME/utm.easi7.in/config/config.php` by hand once
   (copied from `config/config.sample.php` with your real DB credentials) — it's
   git-ignored on purpose and this deploy never overwrites it.
4. Import `database/schema.sql` via phpMyAdmin once.
5. To deploy: **Manage** → **Pull or Deploy** tab → **Update from Remote**, then
   **Deploy HEAD Commit**. Repeat those two clicks after every push you want live.

## Database migrations

`database/schema.sql` is only imported once, on a brand new database. Any schema change
made after that (like the Ad Channels module) ships as a numbered file under
`database/migrations/` — import each one you haven't run yet, in order, the same way you
imported `schema.sql` (phpMyAdmin → Import, or `mysql -u ... -p dbname < database/migrations/xxx.sql`).
A fresh install that imports the current `schema.sql` already has everything and should
skip files in `migrations/` entirely — each migration file says so at the top.

## Local development

```bash
# 1. Create + seed a local database
mysql -u root -e "CREATE DATABASE utm_taxonomy CHARACTER SET utf8mb4;"
mysql -u root utm_taxonomy < database/schema.sql

# 2. Configure
cp config/config.sample.php config/config.php
# edit config/config.php with your local DB credentials

# 3. Run -- dev-router.php mimics the .htaccess rewrite so clean URLs (/login, etc.)
#    work the same locally as they do on real Apache/cPanel hosting. PHP's built-in
#    server doesn't read .htaccess on its own, hence the router script.
php -S localhost:8000 -t public dev-router.php
```

Then open `http://localhost:8000/login` and sign in with the seeded demo account
(`demo@solidpro-es.com` / `Passw0rd!`), or register a new workspace.

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
