-- ============================================================================
-- Migration: Ad Channels module + Campaign builder can pick a Landing Page
-- and gets channel-specific extra parameters (e.g. Google Ads keyword/device).
--
-- Only run this if your database was created BEFORE this change -- i.e. you
-- already imported database/schema.sql once. A fresh install that imports
-- the current schema.sql already has all of this and should NOT run this
-- file (the CREATE TABLE / ADD COLUMN below will error "already exists").
--
-- Run once via phpMyAdmin -> Import (or `mysql -u ... -p dbname < this file`).
-- ============================================================================

CREATE TABLE IF NOT EXISTS channels (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           INT UNSIGNED NOT NULL,
    name                VARCHAR(150) NOT NULL,
    default_utm_source  VARCHAR(100) NULL,
    default_utm_medium  VARCHAR(100) NULL,
    term_label          VARCHAR(60) NULL,
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

ALTER TABLE campaigns
    ADD COLUMN landing_page_id INT UNSIGNED NULL AFTER tenant_id,
    ADD COLUMN channel_id INT UNSIGNED NULL AFTER landing_page_id,
    ADD COLUMN extra_params TEXT NULL AFTER utm_content,
    MODIFY COLUMN generated_url VARCHAR(700) NOT NULL;

ALTER TABLE campaigns
    ADD CONSTRAINT fk_campaigns_landing_page FOREIGN KEY (landing_page_id) REFERENCES landing_pages(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_campaigns_channel FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE SET NULL;

-- Seed the same starter channels as a fresh install, for every tenant that
-- already exists. Safe to re-run: uq_channels_tenant_name silently skips
-- tenants that already have a channel with that name (INSERT IGNORE).
INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'Google Ads - Search', 'google', 'cpc', 'Keyword', 'network,device,matchtype',
       'RSAs and other keyword-targeted Search campaigns. Use {keyword}, {network}, {device}, {matchtype} ValueTrack parameters as the values.'
FROM tenants;

INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'Google Ads - Display/PMax', 'google', 'display', NULL, 'placement,device',
       'Display Network and Performance Max, which have no keyword targeting.'
FROM tenants;

INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'Meta Ads', 'facebook', 'paid-social', 'Audience', 'placement,adset_name',
       'Facebook/Instagram paid campaigns.'
FROM tenants;

INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'LinkedIn Ads', 'linkedin', 'paid-social', NULL, 'campaign_id,creative_id',
       'LinkedIn Campaign Manager.'
FROM tenants;

INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'Email', 'newsletter', 'email', NULL, NULL,
       'Outbound email/newsletter sends.'
FROM tenants;

INSERT IGNORE INTO channels (tenant_id, name, default_utm_source, default_utm_medium, term_label, extra_param_labels, description)
SELECT id, 'Organic Social', NULL, 'social', NULL, NULL,
       'Unpaid posts -- set utm_source per platform (linkedin, twitter, ...).'
FROM tenants;
