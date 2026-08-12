-- ============================================================================
-- UTM Tracking Taxonomy Manager -- MySQL/MariaDB schema
-- Target: shared hosting (cPanel-style), MySQL 5.7+ / MariaDB 10.2+, InnoDB.
-- Import this whole file once via phpMyAdmin (or `mysql -u ... -p db < schema.sql`).
-- It creates all tables AND seeds one demo tenant with the taxonomy from the
-- original UTM_Parameter.xlsx so the app is usable immediately after install.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Platform-level tables
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tenants (
    id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                      VARCHAR(150) NOT NULL,
    slug                      VARCHAR(150) NOT NULL,
    primary_domain            VARCHAR(190) NULL,
    status                    ENUM('active','suspended') NOT NULL DEFAULT 'active',
    -- Naming Conventions (Company Settings): token patterns using the same {{token}}
    -- syntax as Snippet Templates (see App\Models\SnippetTemplate::render()).
    tracking_form_id_pattern  VARCHAR(255) NOT NULL DEFAULT '{{page_type_short}}-{{service_vertical}}-{{service}}-{{form_type}}-{{form_location}}',
    campaign_name_pattern     VARCHAR(255) NULL,
    -- Persisted, tenant-scoped counter for the {{seq}} campaign-naming token.
    -- Only incremented at actual campaign-creation time (never on delete),
    -- so a number is never reused once assigned -- see campaigns.seq_number.
    next_campaign_seq         INT UNSIGNED NOT NULL DEFAULT 1,
    -- Org invite link (Users & Roles -> "Invite Link"): a single reusable
    -- token per tenant that lets people self-register into THIS tenant
    -- (choosing their own password) instead of always creating a new
    -- company via /register. Regenerating replaces the token, invalidating
    -- any previously shared link.
    invite_token              VARCHAR(64) NULL,
    invite_role               ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    invite_enabled            TINYINT(1) NOT NULL DEFAULT 0,
    created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenants_slug (slug),
    UNIQUE KEY uq_tenants_invite_token (invite_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NULL,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('owner','admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    is_super_admin  TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login_at   DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_tenant (tenant_id),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(20) NOT NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id   INT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_tenant_created (tenant_id, created_at),
    CONSTRAINT fk_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Master-data (taxonomy) tables -- one per original sheet, tenant-scoped
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS verticals (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    short_code   VARCHAR(20) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_verticals_tenant_name (tenant_id, name),
    UNIQUE KEY uq_verticals_tenant_code (tenant_id, short_code),
    CONSTRAINT fk_verticals_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    vertical_id  INT UNSIGNED NULL,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_services_tenant_slug (tenant_id, slug),
    KEY idx_services_vertical (vertical_id),
    CONSTRAINT fk_services_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_services_vertical FOREIGN KEY (vertical_id) REFERENCES verticals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_types (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    short_code   VARCHAR(20) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_page_types_tenant_name (tenant_id, name),
    CONSTRAINT fk_page_types_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_types (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_types_tenant_name (tenant_id, name),
    CONSTRAINT fk_form_types_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_locations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_form_locations_tenant_name (tenant_id, name),
    CONSTRAINT fk_form_locations_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS funnel_stages (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_funnel_stages_tenant_name (tenant_id, name),
    CONSTRAINT fk_funnel_stages_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_events_tenant_name (tenant_id, name),
    CONSTRAINT fk_events_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_magnets (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    vertical_id  INT UNSIGNED NULL,
    service_id   INT UNSIGNED NULL,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(150) NOT NULL,
    asset_url    VARCHAR(255) NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_lead_magnets_tenant_slug (tenant_id, slug),
    KEY idx_lead_magnets_vertical (vertical_id),
    KEY idx_lead_magnets_service (service_id),
    CONSTRAINT fk_lead_magnets_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_lead_magnets_vertical FOREIGN KEY (vertical_id) REFERENCES verticals(id) ON DELETE SET NULL,
    CONSTRAINT fk_lead_magnets_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS traffic_types (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    code         VARCHAR(20) NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_traffic_types_tenant_code (tenant_id, code),
    CONSTRAINT fk_traffic_types_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS channels (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           INT UNSIGNED NOT NULL,
    name                VARCHAR(150) NOT NULL,
    -- Short code for naming patterns/snippets, e.g. "GA" for Google Ads, "FB" for Meta.
    short_code          VARCHAR(20) NULL,
    default_utm_source  VARCHAR(100) NULL,
    default_utm_medium  VARCHAR(100) NULL,
    -- Comma-separated GA4-recognized utm_source / utm_medium values commonly used with
    -- this channel, e.g. "cpc,ppc,paidsearch" for Google Ads Search. Shown as clickable
    -- suggestions on the Campaign form so users don't have to already know GA4's default
    -- channel-grouping conventions.
    recommended_sources VARCHAR(255) NULL,
    recommended_mediums VARCHAR(255) NULL,
    -- Relabels the Campaign form's utm_term field for this channel, e.g. "Keyword" for
    -- Google Ads Search, "Audience" for Meta Ads. Null keeps the generic "utm_term" label.
    term_label          VARCHAR(60) NULL,
    -- Only keyword-targeted Search channels actually need a keyword value -- everywhere
    -- else utm_term is optional. Drives both client-side and server-side validation.
    requires_term       TINYINT(1) NOT NULL DEFAULT 0,
    -- Comma-separated extra query-param names specific to this channel, e.g.
    -- "network,device,matchtype" for Google Ads Search, "placement,adset_name" for Meta Ads.
    -- Rendered as extra key/value inputs on the Campaign form and appended to generated_url.
    extra_param_labels  VARCHAR(255) NULL,
    description         VARCHAR(255) NULL,
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by          INT UNSIGNED NULL,
    updated_by          INT UNSIGNED NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_channels_tenant_name (tenant_id, name),
    CONSTRAINT fk_channels_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Custom Variables -- tenant-defined data-layer keys beyond the built-in taxonomy
-- above. Makes the app usable for any company's dataLayer shape, not just this one.
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS custom_variables (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    -- The {{token}} name used in Snippet Templates and Naming Convention patterns.
    key_name     VARCHAR(100) NOT NULL,
    label        VARCHAR(150) NOT NULL,
    source_type  ENUM('static_list','free_text') NOT NULL DEFAULT 'free_text',
    -- Which form(s) this variable shows up on -- keeps Tracking Configs and
    -- Campaigns from both being cluttered with fields only relevant to one.
    applies_to_tracking_config TINYINT(1) NOT NULL DEFAULT 1,
    applies_to_campaign        TINYINT(1) NOT NULL DEFAULT 1,
    description  VARCHAR(255) NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by   INT UNSIGNED NULL,
    updated_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_custom_variables_tenant_key (tenant_id, key_name),
    CONSTRAINT fk_custom_variables_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS custom_variable_options (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_variable_id INT UNSIGNED NOT NULL,
    value              VARCHAR(150) NOT NULL,
    label              VARCHAR(150) NOT NULL,
    sort_order         INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_custom_variable_options_variable FOREIGN KEY (custom_variable_id) REFERENCES custom_variables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per (entity, custom_variable) actually filled in on that entity. Polymorphic
-- across entity types (tracking_config, campaign, ...) since MySQL can't FK a column to
-- "whichever table entity_type says" -- the app layer (App\Models\CustomVariableValue)
-- deletes orphaned rows itself when a tracking_config/campaign is deleted.
CREATE TABLE IF NOT EXISTS custom_variable_values (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type         ENUM('tracking_config','campaign') NOT NULL,
    entity_id           INT UNSIGNED NOT NULL,
    custom_variable_id  INT UNSIGNED NOT NULL,
    value               VARCHAR(255) NULL,
    UNIQUE KEY uq_cv_values_entity_variable (entity_type, entity_id, custom_variable_id),
    KEY idx_cv_values_entity (entity_type, entity_id),
    CONSTRAINT fk_cv_values_variable FOREIGN KEY (custom_variable_id) REFERENCES custom_variables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Landing Pages
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS landing_pages (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id      INT UNSIGNED NOT NULL,
    name           VARCHAR(190) NOT NULL,
    url            VARCHAR(255) NOT NULL,
    page_type_id   INT UNSIGNED NULL,
    vertical_id    INT UNSIGNED NULL,
    service_id     INT UNSIGNED NULL,
    lead_magnet_id INT UNSIGNED NULL,
    owner_user_id  INT UNSIGNED NULL,
    status         ENUM('draft','live','archived') NOT NULL DEFAULT 'draft',
    template       VARCHAR(150) NULL,
    thumbnail_url  VARCHAR(255) NULL,
    notes          TEXT NULL,
    created_by     INT UNSIGNED NULL,
    updated_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_landing_pages_tenant_url (tenant_id, url),
    KEY idx_landing_pages_status (tenant_id, status),
    CONSTRAINT fk_lp_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_lp_page_type FOREIGN KEY (page_type_id) REFERENCES page_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_lp_vertical FOREIGN KEY (vertical_id) REFERENCES verticals(id) ON DELETE SET NULL,
    CONSTRAINT fk_lp_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_lp_lead_magnet FOREIGN KEY (lead_magnet_id) REFERENCES lead_magnets(id) ON DELETE SET NULL,
    CONSTRAINT fk_lp_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Snippet Templates (tenant-editable, token-based)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS snippet_templates (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    key_name     VARCHAR(60) NOT NULL,
    name         VARCHAR(150) NOT NULL,
    template     MEDIUMTEXT NOT NULL,
    is_default   TINYINT(1) NOT NULL DEFAULT 0,
    -- Whether this template renders automatically in the Tracking Configuration preview
    -- and CSV exports. Off lets a tenant keep a draft/legacy/one-off template around
    -- without it showing up on every single tracking config.
    applies_to_tracking_config TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_snippet_templates_tenant_key (tenant_id, key_name),
    CONSTRAINT fk_snippet_templates_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Tracking Configurations (the "URL" sheet)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tracking_configs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id        INT UNSIGNED NOT NULL,
    landing_page_id  INT UNSIGNED NULL,
    page_url         VARCHAR(255) NOT NULL,
    page_type_id     INT UNSIGNED NULL,
    vertical_id      INT UNSIGNED NULL,
    service_id       INT UNSIGNED NULL,
    lead_magnet_id   INT UNSIGNED NULL,
    form_type_id     INT UNSIGNED NULL,
    form_location_id INT UNSIGNED NULL,
    funnel_stage_id  INT UNSIGNED NULL,
    event_id         INT UNSIGNED NULL,
    traffic_type_id  INT UNSIGNED NULL,
    form_id          VARCHAR(191) NOT NULL,
    status           ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes            TEXT NULL,
    created_by       INT UNSIGNED NULL,
    updated_by       INT UNSIGNED NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tracking_configs_tenant_formid (tenant_id, form_id),
    KEY idx_tc_landing_page (landing_page_id),
    CONSTRAINT fk_tc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tc_landing_page FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_page_type FOREIGN KEY (page_type_id) REFERENCES page_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_vertical FOREIGN KEY (vertical_id) REFERENCES verticals(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_lead_magnet FOREIGN KEY (lead_magnet_id) REFERENCES lead_magnets(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_form_type FOREIGN KEY (form_type_id) REFERENCES form_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_form_location FOREIGN KEY (form_location_id) REFERENCES form_locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_funnel_stage FOREIGN KEY (funnel_stage_id) REFERENCES funnel_stages(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    CONSTRAINT fk_tc_traffic_type FOREIGN KEY (traffic_type_id) REFERENCES traffic_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Campaigns / UTM Link Builder
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS campaigns (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NOT NULL,
    landing_page_id INT UNSIGNED NULL,
    channel_id      INT UNSIGNED NULL,
    -- utm_cv: which Traffic Type (mar/abm/...) this campaign link is tagged for. Lives on
    -- the campaign, not just the Tracking Configuration -- landing-page scripts read it
    -- straight off the clicked URL via getUrlParam('utm_cv'), so it has to actually be a
    -- query param on the link the Campaign builder generates.
    traffic_type_id INT UNSIGNED NULL,
    -- The number permanently assigned to this campaign at creation time from
    -- tenants.next_campaign_seq; immutable afterward (mirrors form_id's
    -- "must stay stable" rule -- see tenant_settings/edit.php).
    seq_number      INT UNSIGNED NULL,
    name            VARCHAR(190) NOT NULL,
    target_url      VARCHAR(255) NOT NULL,
    utm_source      VARCHAR(100) NOT NULL,
    utm_medium      VARCHAR(100) NOT NULL,
    utm_campaign    VARCHAR(150) NOT NULL,
    utm_term        VARCHAR(150) NULL,
    utm_content     VARCHAR(150) NULL,
    -- JSON object of channel-specific extra query params, e.g. {"network":"g","device":"m"}.
    -- Stored as TEXT (not the JSON column type) for compatibility with older MySQL/MariaDB
    -- on shared hosting; the app reads/writes it with json_encode/json_decode.
    extra_params    TEXT NULL,
    generated_url   VARCHAR(700) NOT NULL,
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by      INT UNSIGNED NULL,
    updated_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_campaigns_tenant (tenant_id),
    CONSTRAINT fk_campaigns_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_campaigns_landing_page FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    CONSTRAINT fk_campaigns_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE SET NULL,
    CONSTRAINT fk_campaigns_traffic_type FOREIGN KEY (traffic_type_id) REFERENCES traffic_types(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Seed data: one demo tenant, populated from the original UTM_Parameter.xlsx
-- Login: demo@solidpro-es.com / Passw0rd!
-- ============================================================================

INSERT INTO tenants (id, name, slug, primary_domain, status) VALUES
    (1, 'Solidpro (Demo)', 'solidpro-demo', 'solidpro-es.com', 'active');

-- Password hash below is bcrypt for "Passw0rd!" -- change immediately after first login.
INSERT INTO users (id, tenant_id, name, email, password_hash, role, is_super_admin, status) VALUES
    (1, 1, 'Demo Owner', 'demo@solidpro-es.com', '$2y$12$YF72Gs6Avr60tjH8GVxSK.mnmG3YhyKlZzSSYIxdLYqZAM/49V1Me', 'owner', 0, 'active'),
    (2, NULL, 'Platform Admin', 'super@example.com', '$2y$12$YF72Gs6Avr60tjH8GVxSK.mnmG3YhyKlZzSSYIxdLYqZAM/49V1Me', 'owner', 1, 'active');

INSERT INTO verticals (tenant_id, name, short_code, description, created_by) VALUES
    (1, 'BFSI', 'BF', 'Banking, Financial Services & Insurance', 1),
    (1, 'Digital Transformation', 'DT', NULL, 1),
    (1, 'Energy & Utilities', 'EU', NULL, 1),
    (1, 'Industrial Goods & Consumer Products', 'IGCP', NULL, 1),
    (1, 'Healthcare and Lifesciences', 'HELIX', NULL, 1),
    (1, 'Process Engineering', 'PE', NULL, 1),
    (1, 'Structural Engineering', 'SE', NULL, 1),
    (1, 'Innovation and SPM', 'SPM', NULL, 1),
    (1, 'Sustainability', 'SUS', NULL, 1),
    (1, 'Technical Publications', 'TP', NULL, 1),
    (1, 'Global (cross-vertical)', 'GL', 'Used for pages that are not vertical-specific, e.g. /contact', 1);

INSERT INTO services (tenant_id, vertical_id, name, slug, created_by) VALUES
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), 'Delivery Pods', 'delivery-pods', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), 'Enterprise Systems Integration', 'esi', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), 'CPQ', 'cpq', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), 'Digital Consulting', 'digital-consulting', 1);

INSERT INTO page_types (tenant_id, name, short_code, created_by) VALUES
    (1, 'contact_page', 'cp', 1),
    (1, 'assessment_landing_page', 'lp', 1),
    (1, 'entrydoor_landing_page', 'elp', 1),
    (1, 'leadmagnet_landing_page', 'lp', 1),
    (1, 'assessment_resource_page', 'lp', 1);

INSERT INTO form_types (tenant_id, name, created_by) VALUES
    (1, 'lead_magnet', 1),
    (1, 'assessment', 1),
    (1, 'discovery_call', 1),
    (1, 'contact_form', 1),
    (1, 'calendly_booking', 1),
    (1, 'webinar', 1);

INSERT INTO form_locations (tenant_id, name, created_by) VALUES
    (1, 'header', 1),
    (1, 'hero', 1),
    (1, 'inline', 1),
    (1, 'footer', 1),
    (1, 'popup', 1),
    (1, 'sidebar', 1),
    (1, 'sticky_bar', 1),
    (1, 'homepage', 1);

INSERT INTO funnel_stages (tenant_id, name, sort_order, created_by) VALUES
    (1, 'Awareness', 1, 1),
    (1, 'Interest', 2, 1),
    (1, 'Capture', 3, 1),
    (1, 'MQL', 4, 1),
    (1, 'SQL', 5, 1),
    (1, 'Convert', 6, 1);

INSERT INTO events (tenant_id, name, created_by) VALUES
    (1, 'lead_submitted', 1),
    (1, 'discovery_call_requested', 1),
    (1, 'contact_form_submitted', 1),
    (1, 'lead_magnet_submitted', 1),
    (1, 'lead_tool_submitted', 1);

INSERT INTO lead_magnets (tenant_id, vertical_id, service_id, name, slug, created_by) VALUES
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='cpq'), 'CPQ Readiness Assessment', 'cpq_readiness_assessment', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='cpq'), 'Quote Velocity Diagnostic', 'quote-velocity-diagnostic', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='esi'), 'Integration X-Ray', 'integration-x-ray', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='delivery-pods'), 'Cost of Unfilled Role Calculator', 'cost-of-unfilled-role-calculator', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='digital-consulting'), 'DM5 Factory Builder', 'dm5-factory-builder', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='esi'), 'Post-ERP Integration Checklist', 'post-erp-integration-checklist', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='digital-consulting'), 'Export Digital Readiness Guide', 'export-digital-readiness-guide', 1),
    (1, (SELECT id FROM verticals WHERE tenant_id=1 AND short_code='DT'), (SELECT id FROM services WHERE tenant_id=1 AND slug='delivery-pods'), 'RaaS vs Hire Decision Guide', 'raas-vs-hire-decision-guide', 1);

INSERT INTO traffic_types (tenant_id, code, name, description, created_by) VALUES
    (1, 'mar', 'Marketing', 'Standard inbound/outbound marketing traffic', 1),
    (1, 'abm', 'Account-Based Marketing', 'Traffic sourced from ABM campaigns', 1);

-- Short codes, source/medium recommendations, and requires_term follow GA4's default
-- channel grouping conventions (Direct/Organic Search/Paid Search/Organic Social/Paid
-- Social/Email/Affiliates/Referral/Display/Paid Other/Audio/SMS/Mobile Push/Video/Shopping)
-- so campaigns built here land in the channel group you'd expect inside GA4 reports.
INSERT INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description, created_by) VALUES
    (1, 'Google Ads - Search', 'GA', 'google', 'cpc', 'google', 'cpc,ppc,paidsearch', 'Keyword', 1, 'network,device,matchtype', 'RSAs and other keyword-targeted Search campaigns. Use {keyword}, {network}, {device}, {matchtype} ValueTrack parameters as the values.', 1),
    (1, 'Google Ads - Display', 'GDN', 'google', 'display', 'google', 'display,cpm,banner', NULL, 0, 'placement,device', 'Display Network placements -- no keyword targeting.', 1),
    (1, 'Google Ads - Video/YouTube', 'YT', 'google', 'video', 'google,youtube', 'paid-video,video,cpv', NULL, 0, 'placement,device', 'YouTube/video campaigns.', 1),
    (1, 'Google Ads - Shopping/PMax', 'GSHOP', 'google', 'cpc', 'google', 'cpc,paidshopping', NULL, 0, 'device', 'Shopping and Performance Max -- no single keyword to attribute.', 1),
    (1, 'Microsoft/Bing Ads', 'BING', 'bing', 'cpc', 'bing', 'cpc,ppc,paidsearch', 'Keyword', 1, 'device,matchtype', 'Keyword-targeted Bing/Microsoft Search campaigns.', 1),
    (1, 'Meta Ads', 'FB', 'facebook', 'paid-social', 'facebook,instagram', 'paid-social,cpc', 'Audience', 0, 'placement,adset_name', 'Facebook/Instagram paid campaigns.', 1),
    (1, 'LinkedIn Ads', 'LI', 'linkedin', 'paid-social', 'linkedin', 'paid-social,cpc', NULL, 0, 'campaign_id,creative_id', 'LinkedIn Campaign Manager.', 1),
    (1, 'TikTok Ads', 'TT', 'tiktok', 'paid-social', 'tiktok', 'paid-social,cpc', NULL, 0, 'placement', 'TikTok Ads Manager.', 1),
    (1, 'Twitter/X Ads', 'X', 'twitter', 'paid-social', 'twitter,x', 'paid-social,cpc', NULL, 0, NULL, 'X (Twitter) Ads.', 1),
    (1, 'Pinterest Ads', 'PIN', 'pinterest', 'paid-social', 'pinterest', 'paid-social,cpc', NULL, 0, NULL, 'Pinterest Ads Manager.', 1),
    (1, 'Reddit Ads', 'RDT', 'reddit', 'paid-social', 'reddit', 'paid-social,cpc', NULL, 0, NULL, 'Reddit Ads.', 1),
    (1, 'Organic Social', 'ORG', NULL, 'social', 'linkedin,twitter,facebook,instagram', 'social,organic-social', NULL, 0, NULL, 'Unpaid posts -- set utm_source per platform.', 1),
    (1, 'Organic Search', 'ORGS', NULL, 'organic', 'google,bing,yahoo', 'organic', NULL, 0, NULL, 'Rarely tagged manually -- GA4 detects this automatically. Only use for special tracking cases.', 1),
    (1, 'Email', 'EM', 'newsletter', 'email', NULL, 'email', NULL, 0, NULL, 'Outbound email/newsletter sends.', 1),
    (1, 'Affiliate', 'AFF', NULL, 'affiliate', NULL, 'affiliate', NULL, 0, NULL, 'Affiliate/partner-driven traffic.', 1),
    (1, 'Referral / Partner', 'REF', NULL, 'referral', NULL, 'referral', NULL, 0, NULL, 'Co-marketing, partner links, other sites linking to you.', 1),
    (1, 'SMS', 'SMS', 'sms', 'sms', NULL, 'sms', NULL, 0, NULL, 'Text message campaigns.', 1),
    (1, 'Display / Programmatic (other)', 'DISP', NULL, 'display', NULL, 'display,cpm,programmatic', NULL, 0, 'placement,device', 'Any programmatic/display network not covered above.', 1);

-- Example Custom Variables, demonstrating a real campaign-naming convention:
-- "PA1-DT-CPQ-GA-RSA-Traffic-Aug2026-V1" via a {{format}}/{{objective}}/{{date}}/{{version}}
-- pattern (see the tenant's campaign_name_pattern below) alongside a
-- tracking-config-only example (A/B test variant).
INSERT INTO custom_variables (tenant_id, key_name, label, source_type, applies_to_tracking_config, applies_to_campaign, description, sort_order, created_by) VALUES
    (1, 'format', 'Ad Format', 'static_list', 0, 1, 'e.g. RSA, DSA, Display, Video -- independent of Channel.', 1, 1),
    (1, 'objective', 'Objective', 'static_list', 0, 1, 'What the campaign is optimizing for.', 2, 1),
    (1, 'date', 'Campaign Date', 'free_text', 0, 1, 'e.g. Aug2026 -- free text so it can be a month, quarter, or launch date.', 3, 1),
    (1, 'version', 'Version', 'free_text', 0, 1, 'e.g. V1, V2 -- bump when you relaunch the same campaign concept.', 4, 1),
    (1, 'ab_test_variant', 'A/B Test Variant', 'static_list', 1, 0, 'Which variant this tracking config belongs to, if the page is split-tested.', 1, 1);

INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order) VALUES
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='format'), 'RSA', 'Responsive Search Ad', 1),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='format'), 'DSA', 'Dynamic Search Ad', 2),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='format'), 'Display', 'Display', 3),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='format'), 'Video', 'Video', 4),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='format'), 'PMax', 'Performance Max', 5),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='objective'), 'Traffic', 'Traffic', 1),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='objective'), 'Leads', 'Leads', 2),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='objective'), 'Conversions', 'Conversions', 3),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='objective'), 'Awareness', 'Awareness', 4),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='ab_test_variant'), 'A', 'Variant A', 1),
    ((SELECT id FROM custom_variables WHERE tenant_id=1 AND key_name='ab_test_variant'), 'B', 'Variant B', 2);

UPDATE tenants SET campaign_name_pattern = 'PA{{seq}}-{{service_vertical}}-{{service_name}}-{{channel}}-{{format}}-{{objective}}-{{date}}-{{version}}' WHERE id = 1;

INSERT INTO snippet_templates (tenant_id, key_name, name, template, is_default) VALUES
(1, 'ga4', 'GA4 dataLayer push', 'window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
  \'event\': \'{{event_name}}\',
  \'page_url\': window.location.href,
  \'service_vertical\': \'{{service_vertical}}\',
  \'form_type\': \'{{form_type}}\',
  \'form_location\': \'{{form_location}}\',
  \'lead_magnet_name\': {{lead_magnet_name_js}},
  \'funnel_stage\': \'{{funnel_stage}}\',
  \'utm_source\': getTrafficSource(),
  \'utm_campaign\': getUrlParam(\'utm_campaign\'),
  \'utm_medium\': getUrlParam(\'utm_medium\')
});', 1),
(1, 'crm', 'CRM lead object', 'const leadData = {
  form_id:      \'{{form_id}}\',
  page_url:     window.location.href,
  vertical:     \'{{service_vertical}}\',
  service:      {{service_js}},
  utm_source:   getUrlParam(\'utm_source\'),
  utm_medium:   getUrlParam(\'utm_medium\'),
  utm_campaign: getUrlParam(\'utm_campaign\'),
  utm_cv:       getUrlParam(\'utm_cv\'),
  utm_content:  getUrlParam(\'utm_content\'),
  utm_term:     getUrlParam(\'utm_term\')
};', 1);
