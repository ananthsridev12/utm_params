-- ============================================================================
-- Migration: GA4-recommended source/medium suggestions + conditional keyword
-- requirement on Channels, and a per-Snippet-Template "applies to Tracking
-- Configurations" toggle so not every created snippet renders everywhere.
--
-- Only run this if your database already has the 2026_08_18 migration applied
-- (or was created before this change). A fresh install that imports the
-- current schema.sql already has all of this and should NOT run this file.
--
-- Run once via phpMyAdmin -> Import (or `mysql -u ... -p dbname < this file`).
-- ============================================================================

ALTER TABLE channels
    ADD COLUMN recommended_sources VARCHAR(255) NULL AFTER default_utm_medium,
    ADD COLUMN recommended_mediums VARCHAR(255) NULL AFTER recommended_sources,
    ADD COLUMN requires_term TINYINT(1) NOT NULL DEFAULT 0 AFTER term_label;

ALTER TABLE snippet_templates
    ADD COLUMN applies_to_tracking_config TINYINT(1) NOT NULL DEFAULT 1 AFTER is_default;

-- Backfill recommended sources/mediums + requires_term for any of this app's
-- own seeded channel names that already exist in your database (safe no-op
-- for channels you've since renamed or added yourself -- edit those by hand
-- under Ad Channels afterwards).
UPDATE channels SET recommended_sources = 'google', recommended_mediums = 'cpc,ppc,paidsearch', requires_term = 1 WHERE name = 'Google Ads - Search';
UPDATE channels SET recommended_sources = 'google', recommended_mediums = 'display,cpm,banner' WHERE name IN ('Google Ads - Display', 'Google Ads - Display/PMax');
UPDATE channels SET recommended_sources = 'facebook,instagram', recommended_mediums = 'paid-social,cpc' WHERE name = 'Meta Ads';
UPDATE channels SET recommended_sources = 'linkedin', recommended_mediums = 'paid-social,cpc' WHERE name = 'LinkedIn Ads';
UPDATE channels SET recommended_mediums = 'email' WHERE name = 'Email';
UPDATE channels SET recommended_sources = 'linkedin,twitter,facebook,instagram', recommended_mediums = 'social,organic-social' WHERE name = 'Organic Social';

-- Add the newer channels (Bing, TikTok, X, Pinterest, Reddit, YouTube, Shopping/PMax,
-- Organic Search, Affiliate, Referral, SMS, generic Display) to tenants that already
-- ran the 2026_08_11 migration and so only have the original 6.
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Google Ads - Video/YouTube', 'YT', 'google', 'video', 'google,youtube', 'paid-video,video,cpv', NULL, 0, 'placement,device', 'YouTube/video campaigns.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Google Ads - Shopping/PMax', 'GSHOP', 'google', 'cpc', 'google', 'cpc,paidshopping', NULL, 0, 'device', 'Shopping and Performance Max -- no single keyword to attribute.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Microsoft/Bing Ads', 'BING', 'bing', 'cpc', 'bing', 'cpc,ppc,paidsearch', 'Keyword', 1, 'device,matchtype', 'Keyword-targeted Bing/Microsoft Search campaigns.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'TikTok Ads', 'TT', 'tiktok', 'paid-social', 'tiktok', 'paid-social,cpc', NULL, 0, 'placement', 'TikTok Ads Manager.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Twitter/X Ads', 'X', 'twitter', 'paid-social', 'twitter,x', 'paid-social,cpc', NULL, 0, NULL, 'X (Twitter) Ads.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Pinterest Ads', 'PIN', 'pinterest', 'paid-social', 'pinterest', 'paid-social,cpc', NULL, 0, NULL, 'Pinterest Ads Manager.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Reddit Ads', 'RDT', 'reddit', 'paid-social', 'reddit', 'paid-social,cpc', NULL, 0, NULL, 'Reddit Ads.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Organic Search', 'ORGS', NULL, 'organic', 'google,bing,yahoo', 'organic', NULL, 0, NULL, 'Rarely tagged manually -- GA4 detects this automatically. Only use for special tracking cases.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Affiliate', 'AFF', NULL, 'affiliate', NULL, 'affiliate', NULL, 0, NULL, 'Affiliate/partner-driven traffic.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Referral / Partner', 'REF', NULL, 'referral', NULL, 'referral', NULL, 0, NULL, 'Co-marketing, partner links, other sites linking to you.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'SMS', 'SMS', 'sms', 'sms', NULL, 'sms', NULL, 0, NULL, 'Text message campaigns.' FROM tenants;
INSERT IGNORE INTO channels (tenant_id, name, short_code, default_utm_source, default_utm_medium, recommended_sources, recommended_mediums, term_label, requires_term, extra_param_labels, description)
SELECT id, 'Display / Programmatic (other)', 'DISP', NULL, 'display', NULL, 'display,cpm,programmatic', NULL, 0, 'placement,device', 'Any programmatic/display network not covered above.' FROM tenants;
