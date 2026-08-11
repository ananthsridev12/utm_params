-- Migration: org invite links
-- Only run this if your database was created BEFORE this file existed (i.e. you
-- imported an older schema.sql). A brand new install that imports the current
-- schema.sql already has this and should skip this file.
--
-- Adds a single reusable, regenerable invite link per tenant so people can
-- self-register into an EXISTING company/workspace (choosing their own
-- password) instead of always creating a new one via /register. Managed from
-- Users & Roles ("Invite Link" panel, admin+ only).

ALTER TABLE tenants
    ADD COLUMN invite_token   VARCHAR(64) NULL AFTER campaign_name_pattern,
    ADD COLUMN invite_role    ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer' AFTER invite_token,
    ADD COLUMN invite_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER invite_role,
    ADD UNIQUE KEY uq_tenants_invite_token (invite_token);
