-- Migration: full campaign details (settings, bidding, targeting, keywords) + Excel export
-- Only run this if your database was created BEFORE this file existed. A brand new
-- install that imports the current schema.sql already has this and should skip it.
--
-- Turns a Campaign record from just a UTM tracking link into a full campaign brief
-- with the same kind of settings you'd fill in on Google Ads / Meta Ads / LinkedIn
-- Ads: objective, budget, bidding, schedule, plus platform-specific targeting
-- (keywords for Search, audience criteria for Meta/LinkedIn). Each channel is
-- classified with a platform_type so the Campaign form knows which fixed form to
-- show. No approval/workflow status is added -- these fields are just extra data
-- on the existing Draft/Active campaign record.

ALTER TABLE channels
    ADD COLUMN platform_type ENUM('google_ads','meta_ads','linkedin_ads','other') NOT NULL DEFAULT 'other' AFTER extra_param_labels;

-- Best-effort classification of any channels already in your database, matched by
-- name (case-insensitive substring). Re-check under Ad Channels afterwards --
-- anything not matched here stays 'other' and can be reclassified manually.
UPDATE channels SET platform_type = 'google_ads' WHERE LOWER(name) LIKE '%google%' OR LOWER(name) LIKE '%bing%' OR LOWER(name) LIKE '%microsoft%';
UPDATE channels SET platform_type = 'meta_ads' WHERE LOWER(name) LIKE '%meta%' OR LOWER(name) LIKE '%facebook%' OR LOWER(name) LIKE '%instagram%';
UPDATE channels SET platform_type = 'linkedin_ads' WHERE LOWER(name) LIKE '%linkedin%';

ALTER TABLE campaigns
    ADD COLUMN objective            VARCHAR(100) NULL AFTER status,
    ADD COLUMN budget_type          ENUM('daily','lifetime') NULL AFTER objective,
    ADD COLUMN budget_amount        DECIMAL(12,2) NULL AFTER budget_type,
    ADD COLUMN currency             VARCHAR(10) NULL DEFAULT 'USD' AFTER budget_amount,
    ADD COLUMN bidding_strategy     VARCHAR(100) NULL AFTER currency,
    ADD COLUMN bid_amount           DECIMAL(12,2) NULL AFTER bidding_strategy,
    ADD COLUMN start_date           DATE NULL AFTER bid_amount,
    ADD COLUMN end_date             DATE NULL AFTER start_date,
    ADD COLUMN google_campaign_type VARCHAR(50) NULL AFTER end_date,
    ADD COLUMN google_networks      VARCHAR(255) NULL AFTER google_campaign_type,
    ADD COLUMN google_languages     VARCHAR(255) NULL AFTER google_networks,
    ADD COLUMN google_devices       VARCHAR(100) NULL AFTER google_languages,
    ADD COLUMN meta_buying_type     VARCHAR(50) NULL AFTER google_devices,
    ADD COLUMN meta_placements      VARCHAR(255) NULL AFTER meta_buying_type,
    ADD COLUMN meta_ad_format       VARCHAR(50) NULL AFTER meta_placements,
    ADD COLUMN linkedin_ad_format   VARCHAR(50) NULL AFTER meta_ad_format,
    ADD COLUMN linkedin_bid_type    VARCHAR(50) NULL AFTER linkedin_ad_format;

CREATE TABLE IF NOT EXISTS campaign_keywords (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id   INT UNSIGNED NOT NULL,
    keyword       VARCHAR(190) NOT NULL,
    match_type    ENUM('broad','phrase','exact') NOT NULL DEFAULT 'broad',
    is_negative   TINYINT(1) NOT NULL DEFAULT 0,
    sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_campaign_keywords_campaign (campaign_id),
    CONSTRAINT fk_campaign_keywords_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS campaign_targeting (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT UNSIGNED NOT NULL,
    criterion_type  VARCHAR(60) NOT NULL,
    criterion_value VARCHAR(255) NOT NULL,
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_campaign_targeting_campaign (campaign_id),
    CONSTRAINT fk_campaign_targeting_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
