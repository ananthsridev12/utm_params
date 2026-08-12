-- Migration: campaign sequence numbers
-- Only run this if your database was created BEFORE this file existed (i.e. you
-- imported an older schema.sql). A brand new install that imports the current
-- schema.sql already has this and should skip this file.
--
-- Fixes a bug where the {{seq}} token in a tenant's campaign_name_pattern was
-- a LIVE COUNT(*) of campaigns (App\Models\Campaign::nextSeq()) -- deleting a
-- campaign made the count drop, so a later campaign could be suggested (and
-- saved with) a seq number already baked into an existing campaign's name.
-- Replaces it with a persisted, tenant-scoped counter that only increments at
-- creation time and is never reused, even after deletes.

ALTER TABLE tenants
    ADD COLUMN next_campaign_seq INT UNSIGNED NOT NULL DEFAULT 1 AFTER campaign_name_pattern;

ALTER TABLE campaigns
    ADD COLUMN seq_number INT UNSIGNED NULL AFTER traffic_type_id;

-- Backfill: assign each tenant's existing campaigns a seq_number in
-- created_at (then id, as a tiebreaker for same-second rows) order, starting
-- at 1.
UPDATE campaigns c
JOIN (
    SELECT x.id,
           @seq := IF(@prev_tenant = x.tenant_id, @seq + 1, 1) AS rn,
           @prev_tenant := x.tenant_id AS _tenant_marker
    FROM campaigns x
    CROSS JOIN (SELECT @seq := 0, @prev_tenant := 0) init_vars
    ORDER BY x.tenant_id, x.created_at, x.id
) ranked ON ranked.id = c.id
SET c.seq_number = ranked.rn;

-- Set each tenant's next_campaign_seq to one past its highest existing
-- seq_number. Tenants with no campaigns keep the column's DEFAULT 1.
UPDATE tenants t
JOIN (
    SELECT tenant_id, MAX(seq_number) AS max_seq
    FROM campaigns
    GROUP BY tenant_id
) m ON m.tenant_id = t.id
SET t.next_campaign_seq = m.max_seq + 1;
