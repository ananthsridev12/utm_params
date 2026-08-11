-- ============================================================================
-- Migration: Campaigns gain utm_cv (Traffic Type) as a real query parameter on
-- the generated URL, plus a data fix for generated URLs saved before the
-- ValueTrack-placeholder encoding bug ({keyword} was being percent-encoded to
-- %7Bkeyword%7D, which Google/Bing Ads' click-time substitution can't see).
--
-- Only run this if your database already has the three earlier migrations
-- applied (or was created before this change). A fresh install that imports
-- the current schema.sql already has all of this and should NOT run this file.
--
-- Run once via phpMyAdmin -> Import (or `mysql -u ... -p dbname < this file`).
-- ============================================================================

ALTER TABLE campaigns
    ADD COLUMN traffic_type_id INT UNSIGNED NULL AFTER channel_id,
    ADD CONSTRAINT fk_campaigns_traffic_type FOREIGN KEY (traffic_type_id) REFERENCES traffic_types(id) ON DELETE SET NULL;

-- Un-percent-encode { and } in any already-saved generated_url so ad platforms'
-- {keyword}/{device}/{matchtype}/{network}/... placeholders work retroactively.
-- Safe to re-run: a URL with no encoded braces is left untouched.
UPDATE campaigns
SET generated_url = REPLACE(REPLACE(REPLACE(REPLACE(generated_url, '%7B', '{'), '%7D', '}'), '%7b', '{'), '%7d', '}')
WHERE generated_url LIKE '%\%7B%' OR generated_url LIKE '%\%7D%' OR generated_url LIKE '%\%7b%' OR generated_url LIKE '%\%7d%';
