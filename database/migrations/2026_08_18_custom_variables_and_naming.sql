-- ============================================================================
-- Migration: Custom Variables (tenant-defined data-layer keys) + Naming
-- Conventions (tenant-configurable form_id / Campaign name patterns).
--
-- Only run this if your database was created BEFORE this change -- i.e. you
-- already imported database/schema.sql (optionally already ran the
-- 2026_08_11_campaigns_channels.sql migration too). A fresh install that
-- imports the current schema.sql already has all of this and should NOT run
-- this file.
--
-- Run once via phpMyAdmin -> Import (or `mysql -u ... -p dbname < this file`).
-- ============================================================================

ALTER TABLE tenants
    ADD COLUMN tracking_form_id_pattern VARCHAR(255) NOT NULL
        DEFAULT '{{page_type_short}}-{{service_vertical}}-{{service}}-{{form_type}}-{{form_location}}'
        AFTER status,
    ADD COLUMN campaign_name_pattern VARCHAR(255) NULL AFTER tracking_form_id_pattern;

ALTER TABLE channels
    ADD COLUMN short_code VARCHAR(20) NULL AFTER name;

CREATE TABLE IF NOT EXISTS custom_variables (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id    INT UNSIGNED NOT NULL,
    key_name     VARCHAR(100) NOT NULL,
    label        VARCHAR(150) NOT NULL,
    source_type  ENUM('static_list','free_text') NOT NULL DEFAULT 'free_text',
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

-- Give existing seeded channels a short code so naming patterns/snippets can use it.
UPDATE channels SET short_code = 'GA'  WHERE name = 'Google Ads - Search' AND short_code IS NULL;
UPDATE channels SET short_code = 'GDN' WHERE name = 'Google Ads - Display/PMax' AND short_code IS NULL;
UPDATE channels SET short_code = 'FB'  WHERE name = 'Meta Ads' AND short_code IS NULL;
UPDATE channels SET short_code = 'LI'  WHERE name = 'LinkedIn Ads' AND short_code IS NULL;
UPDATE channels SET short_code = 'EM'  WHERE name = 'Email' AND short_code IS NULL;
UPDATE channels SET short_code = 'ORG' WHERE name = 'Organic Social' AND short_code IS NULL;

-- Example Custom Variables for every existing tenant (safe to re-run: INSERT IGNORE
-- skips tenants that already have a variable with that key_name). Purely illustrative
-- -- edit or delete them freely under Custom Variables.
INSERT IGNORE INTO custom_variables (tenant_id, key_name, label, source_type, applies_to_tracking_config, applies_to_campaign, description, sort_order)
SELECT id, 'format', 'Ad Format', 'static_list', 0, 1, 'e.g. RSA, DSA, Display, Video -- independent of Channel.', 1 FROM tenants;
INSERT IGNORE INTO custom_variables (tenant_id, key_name, label, source_type, applies_to_tracking_config, applies_to_campaign, description, sort_order)
SELECT id, 'objective', 'Objective', 'static_list', 0, 1, 'What the campaign is optimizing for.', 2 FROM tenants;
INSERT IGNORE INTO custom_variables (tenant_id, key_name, label, source_type, applies_to_tracking_config, applies_to_campaign, description, sort_order)
SELECT id, 'date', 'Campaign Date', 'free_text', 0, 1, 'e.g. Aug2026.', 3 FROM tenants;
INSERT IGNORE INTO custom_variables (tenant_id, key_name, label, source_type, applies_to_tracking_config, applies_to_campaign, description, sort_order)
SELECT id, 'version', 'Version', 'free_text', 0, 1, 'e.g. V1, V2.', 4 FROM tenants;

INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'RSA', 'Responsive Search Ad', 1 FROM custom_variables WHERE key_name = 'format';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'DSA', 'Dynamic Search Ad', 2 FROM custom_variables WHERE key_name = 'format';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Display', 'Display', 3 FROM custom_variables WHERE key_name = 'format';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Video', 'Video', 4 FROM custom_variables WHERE key_name = 'format';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'PMax', 'Performance Max', 5 FROM custom_variables WHERE key_name = 'format';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Traffic', 'Traffic', 1 FROM custom_variables WHERE key_name = 'objective';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Leads', 'Leads', 2 FROM custom_variables WHERE key_name = 'objective';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Conversions', 'Conversions', 3 FROM custom_variables WHERE key_name = 'objective';
INSERT INTO custom_variable_options (custom_variable_id, value, label, sort_order)
SELECT id, 'Awareness', 'Awareness', 4 FROM custom_variables WHERE key_name = 'objective';
