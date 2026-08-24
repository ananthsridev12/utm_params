# UTM Tracking Taxonomy Manager — project context for Claude

This file is auto-loaded by Claude Code at the start of every local session in
this repo. It captures the product intent, architecture, conventions, and
history that aren't obvious from the code alone, so a fresh session doesn't
have to rediscover them.

## What this app is

A multi-tenant PHP/MySQL web app that replaces a spreadsheet-based UTM/tracking
taxonomy workflow (`UTM_Parameter.xlsx`). Each company (tenant) gets its own
login and manages its own landing pages, UTM/event taxonomy, campaign UTM
links, and auto-generated GA4/CRM tracking snippets.

**Stack**: Plain PHP 8 + MySQL/PDO. **No framework, no Composer dependency.**
This is deliberate — the target deployment is ordinary shared/cPanel hosting
(`utm.easi7.in`) with no SSH, no build step, no Composer.

**Deploy target**: cPanel's native **Git™ Version Control** feature (see
`.cpanel.yml`), not FTP, not GitHub Actions. cPanel clones the repo itself;
clicking "Deploy HEAD Commit" runs the copy tasks in `.cpanel.yml`. Read the
numbered comments at the top of `.cpanel.yml` and the "Deploying via cPanel
Git Version Control" section of `README.md` before touching deploy config.

## Architecture

- **MVC-lite**: `App\Core\*` (Router, Database, Auth, Request, Response/View,
  Csrf, Config, Str, Url, TenantContext, Flash, helpers.php) →
  `App\Models\*` (BaseModel + per-module models) →
  `App\Controllers\*` (BaseController + per-module controllers) →
  `App\Views\*` (plain PHP templates, no template engine).
- **Multi-tenancy**: one shared MySQL database. Every table carries
  `tenant_id`. Tenant is resolved from the **logged-in user**
  (`users.tenant_id`), not subdomain — `TenantContext::id()`/`requireTenant()`.
  `App\Models\BaseModel` enforces `tenant_id = ?` on every query centrally, so
  a bug in a controller can never leak another tenant's rows.
- **RBAC**: `viewer < editor < admin < owner` (`Auth::hasAtLeast()`,
  `Auth::requireRole()`), plus a separate cross-tenant `is_super_admin` flag
  for the platform back office (`SuperAdminController`).
- **Master-data CRUD pattern**: `BaseModel` (all/allActive/find/create/
  update/delete/findByColumn/count) + `BaseController` (CSRF verify, role
  gate, CSV export via `streamCsv()`/`resolveExportRows()`, `afterSave()`
  hook) give tenant-scoped CRUD almost for free. Copy `Vertical.php` +
  `VerticalController.php` + `app/Views/verticals/*` as the canonical
  example when adding a new master-data module.
- **Clean URLs**: every link is a real path (`/login`, `/campaigns/edit/3`),
  not `index.php?r=...`. `App\Core\Url::to()` builds these; Apache
  `.htaccess` (`mod_rewrite`) turns them back into requests on real hosting.
  **Locally**, PHP's built-in server doesn't read `.htaccess`, so run it via
  `dev-router.php` (see "Local development" below) — don't use plain
  `php -S localhost:8000 -t public` or clean URLs will 404.
- **CSRF field name is `_csrf`**, not `csrf_token` — `Csrf::field()` /
  `Csrf::verifyRequest()`. Easy to get wrong when curl-testing by hand.

## Modules (tenant-scoped, all under sidebar nav)

| Module | Notes |
|---|---|
| Verticals, Services, Page Types, Form Types, Form Locations, Funnel Stages, Events, Lead Magnets, Traffic Types | The 9 original fixed taxonomy tables from the spreadsheet. Standard master-data CRUD. |
| Landing Pages | name/URL/type/owner/status; **CSV import** with downloadable template (`landing-pages/import`, `import-template`) — references other modules by human-readable name/email via `BaseModel::findByColumn()`, unmatched refs warn without failing the row. |
| Tracking Configurations | Links a Landing Page to the taxonomy, auto-builds a unique `form_id` (via the tenant's Naming Convention pattern), live-renders GA4/CRM snippets. Two CSV export modes: basic+snippets and full+snippets. |
| Snippet Templates | Per-tenant, editable, `{{token}}`-based. Two seeded defaults (GA4 dataLayer push, CRM lead object). Per-template toggle for whether it applies to Tracking Configs. |
| Campaign / UTM Link Builder | Pick a Landing Page or type a URL, choose an Ad Channel to auto-fill source/medium (GA4-recommended, shown per channel) and reveal channel-specific extra params (e.g. Google Ads `{keyword}`/`{device}`/`{matchtype}`). Keyword only required for search-type channels. **`utm_cv` (Traffic Type) is a required field here** — it's part of the generated URL query string. Live preview. Also holds a **full campaign brief**: shared budget/bidding/schedule fields plus a fixed settings form per ad platform (Google Ads: campaign type/objective/bidding/networks/languages/devices + a repeatable Keywords table; Meta Ads: objective/buying type/bidding/placements/ad format + a repeatable Targeting table; LinkedIn Ads: objective/ad format/bid type/bidding + Targeting table). No approval/draft workflow — full-detail fields are just extra data on the existing record. Exports one or many campaigns' full details to a real multi-sheet `.xlsx` via `App\Core\XlsxWriter`. |
| Ad Channels | Per-tenant channel presets (short_code, default/recommended source+medium, term label, `requires_term`, extra param labels, **`platform_type`**) driving the Campaign builder — `platform_type` (`google_ads`/`meta_ads`/`linkedin_ads`/`other`) decides which fixed full-details form a campaign under that channel shows. |
| Custom Variables | Tenant-defined data-layer keys beyond the built-in taxonomy — `static_list` (with options) or `free_text`, each scoped to show on Tracking Configs and/or Campaigns. Becomes a `{{key_name}}` token usable in Snippet Templates and Naming Conventions. Polymorphic values table (`custom_variable_values`, `entity_type`/`entity_id`) shared between Tracking Configs and Campaigns. |
| Users & Roles | Owner/Admin/Editor/Viewer per tenant. Admin creates users directly (temp password, no outbound email) **or** shares an **Invite Link** so people self-register into the same tenant with their own password. |
| Company Settings | Tenant profile + **Naming Conventions**: `{{token}}` patterns (same syntax as Snippet Templates) controlling how `form_id` and Campaign names are built. |
| Super Admin | Platform back office — list/suspend/reactivate tenant workspaces. Cross-tenant, gated by `is_super_admin`, not `tenant_id`. |

## Universal by design: Custom Variables + Naming Conventions

The 9-module taxonomy above is a starting point, not a hardcoded schema —
this was an explicit design goal ("universal app for data layer snippet
creation... based on user requirements they can add/remove variables").

**Rejected approach**: collapsing the 9 existing modules into a full
Entity-Attribute-Value system. More "pure," but means rewriting/migrating 9
already-working modules for no functional gain the user asked for.

**Chosen approach**: additive. Custom Variables sit alongside the fixed
taxonomy, reusing existing patterns:
- `SnippetTemplate::render()`'s generic `{{token}}` substitution is reused
  verbatim for Naming Conventions.
- The dynamic extra-field JS pattern from Campaign channel extra-params is
  reused for Custom Variable inputs on Tracking Configs/Campaigns.

Example naming convention actually reproduced from a real customer sheet:
pattern `PA{{seq}}-{{service_vertical}}-{{service_name}}-{{channel}}-{{format}}-{{objective}}-{{date}}-{{version}}`
→ output `PA1-DT-CPQ-GA-RSA-Traffic-Aug2026-V1`.

**Roadmap (explicitly deferred, not built)**: GTM/GA4 API integration —
pushing Snippet Templates as GTM variables/tags, pulling GA4 event names to
seed Events. The current data model (generic tokens + Custom Variables) is
designed to be what a future integration would read from, but no GTM/GA4
code exists yet.

## Known gotchas / decisions worth remembering

- **ValueTrack placeholders must stay literal.** `http_build_query()`
  percent-encodes `{`/`}` like any character, but Google/Bing Ads only
  recognize `{keyword}`, `{device}`, `{matchtype}` etc. **unencoded** at
  click time. Fixed via `CampaignController::unencodeValueTrackBraces()`
  (PHP) and a mirrored JS function in `campaign-builder.js`, applied after
  `http_build_query()`. If you touch campaign URL generation, preserve this.
- **`utm_cv` (Traffic Type) belongs on the Campaign URL Builder**, not just
  Tracking Config snippets. It was originally wired only into Tracking
  Config snippet tokens; that was wrong — landing-page scripts read
  `utm_cv` via `getUrlParam()` off the *actual clicked campaign link*, so it
  has to be a real query parameter on the generated URL. `campaigns.
  traffic_type_id` is a required field on the Campaign form.
- **CSV exports must resolve FKs to names**, never raw DB ids —
  `BaseController::resolveExportRows()` strips `tenant_id` and resolves
  `created_by`/`updated_by`; per-module `exportCsv()` overrides build rows
  from `*WithRelations()` methods.
- **`{{service}}` vs `{{service_name}}`**: `{{service}}` is the URL-safe
  slug (`cpq`), `{{service_name}}` is the display name (`CPQ`). Naming
  convention patterns that need the display form must use `{{service_name}}`
  (mirrors the pre-existing `{{vertical_name}}`).
- **Campaign full-details fields are server-resolved by platform, never trusted from the client.** Google/Meta/LinkedIn each render their own fixed fieldset with their own `<select>` options, but a hidden fieldset's inputs still get submitted on form POST — so `objective`/`bidding_strategy`/`bid_amount` and the Meta/LinkedIn Targeting rows use platform-prefixed field names (`google_objective`, `meta_objective`, `meta_targeting_type[]`, `linkedin_targeting_type[]`, …) and `CampaignController` picks which prefix to actually persist by looking up the *selected channel's real `platform_type` in the database* (`Channel::find($channelId)['platform_type']`), not a hidden form field. Also clears the other platforms' columns on save so switching a campaign's channel doesn't leave stale cross-platform data sitting in the row. `App\Core\XlsxWriter` is a from-scratch OOXML writer (via PHP's built-in `ZipArchive`) — no PhpSpreadsheet/Composer — supporting multiple sheets, a bold header row, and string/number cells only (no formulas/formatting).
- **Invite links are a single reusable, regenerable token per tenant**
  (`tenants.invite_token`/`invite_role`/`invite_enabled`), not a
  per-invitee/single-use table — simplest design that satisfies "share a
  link so people can join." Regenerating invalidates the old link;
  disabling doesn't delete the token, just flips `invite_enabled`. Lookup
  (`Tenant::findByInviteToken()`) is scoped to `invite_enabled = 1 AND
  status = 'active'`, so suspending a tenant silently kills its invite link.

## Database migrations

`database/schema.sql` is only imported once, on a brand-new database — it
already contains every table/column below. Any schema change made *after*
initial build ships as a numbered file under `database/migrations/`, each
documented at the top as "only run on a pre-existing DB." Run them in order:

1. `2026_08_11_campaigns_channels.sql` — Ad Channels + campaign channel fields
2. `2026_08_18_custom_variables_and_naming.sql` — Custom Variables, options,
   `tracking_config_values`, `custom_variable_values`, tenant naming pattern
   columns
3. `2026_08_25_channel_mediums_and_snippet_scope.sql` — channel recommended
   mediums, `snippet_templates.applies_to_tracking_config`
4. `2026_09_01_campaign_traffic_type_and_valuetrack_fix.sql` —
   `campaigns.traffic_type_id` + FK, retroactive data fix un-encoding
   `%7B`/`%7D` in already-saved `generated_url` values
5. `2026_09_08_org_invite_links.sql` — `tenants.invite_token`/`invite_role`/
   `invite_enabled`
6. `2026_09_15_campaign_full_details.sql` — `channels.platform_type`, full campaign
   brief columns on `campaigns` (objective/budget/bidding/schedule + Google/Meta/
   LinkedIn-specific), `campaign_keywords` and `campaign_targeting` tables

## Local development

```bash
mysql -u root -e "CREATE DATABASE utm_taxonomy CHARACTER SET utf8mb4;"
mysql -u root utm_taxonomy < database/schema.sql
cp config/config.sample.php config/config.php   # edit with local DB creds

# dev-router.php mimics the .htaccess rewrite -- required for clean URLs
# to work locally, since php -S doesn't read .htaccess on its own.
php -S localhost:8000 -t public dev-router.php
```

Open `http://localhost:8000/login` — demo account `demo@solidpro-es.com` /
`Passw0rd!` (tenant "Solidpro (Demo)", id 1), or register a new workspace.

**Verification pattern used throughout this project's history** (repeat this
before committing any change): `php -l` across all touched PHP files,
`node --check` on touched JS, apply any new migration to the local dev DB,
then either curl-based server-side checks (log in via `_csrf` + cookie jar,
POST the flow, assert against MySQL directly) or a Playwright headless
browser pass. Don't trust "the code looks right" alone — this app has
several times had subtle bugs (CSRF field name, ValueTrack encoding, wrong
token for display-vs-slug) that only curl/DB verification caught.

## Git branch

Development happens on `claude/multi-tenant-app-design-t755ck`. Commit
messages in this repo's history follow a "what changed + why + how it was
verified" structure — match that style for consistency.

## Deploying

See `README.md` → "Deploying via cPanel Git Version Control" for the full
cPanel setup, and "Database migrations" for which migration files a live
`utm.easi7.in` deploy still needs to run by hand via phpMyAdmin (deploys
never touch `config/config.php` or the database automatically).
