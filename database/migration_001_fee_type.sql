-- ==========================================================
-- Migration: Add fee_type / duration handling / billing month
-- Safe to run once against the EXISTING institution.sqlite.
-- Run via: sqlite3 database/institution.sqlite < migration_001_fee_type.sql
-- ==========================================================

PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- ----------------------------------------------------------
-- COURSES: add fee_type, duration_type, duration_months
-- ----------------------------------------------------------
ALTER TABLE courses ADD COLUMN fee_type TEXT NOT NULL DEFAULT 'fixed'
    CHECK (fee_type IN ('fixed', 'monthly'));

ALTER TABLE courses ADD COLUMN duration_type TEXT NOT NULL DEFAULT 'fixed'
    CHECK (duration_type IN ('fixed', 'ongoing'));

ALTER TABLE courses ADD COLUMN duration_months INTEGER;

-- Backfill duration_months for your existing fixed courses,
-- parsed from the old free-text "duration" column.
UPDATE courses SET duration_months = 3 WHERE duration = '3 Months';
UPDATE courses SET duration_months = 2 WHERE duration = '2 Months';
UPDATE courses SET duration_months = 6 WHERE duration = '6 Months';

-- All existing rows are fixed-fee courses; fee_type/duration_type
-- defaults above already cover this, so no further UPDATE needed.

-- ----------------------------------------------------------
-- PAYMENTS: add billing_month / billing_year
-- NULL for fixed-course payments (unused).
-- Populated for monthly-course payments (e.g. 9, 2026).
-- ----------------------------------------------------------
ALTER TABLE payments ADD COLUMN billing_month INTEGER
    CHECK (billing_month IS NULL OR (billing_month BETWEEN 1 AND 12));

ALTER TABLE payments ADD COLUMN billing_year INTEGER;

-- Index to quickly find "has this student paid for month X of year Y"
CREATE INDEX IF NOT EXISTS idx_payments_billing
    ON payments(student_id, billing_year, billing_month);

-- ----------------------------------------------------------
-- New Typewriting courses (fee_type = monthly, duration_type = ongoing)
-- Only inserted if not already present (safe to re-run).
-- ----------------------------------------------------------
INSERT INTO courses (name, fee, duration, fee_type, duration_type, duration_months)
SELECT 'English Junior Typewriting', 500, 'Ongoing', 'monthly', 'ongoing', NULL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE name = 'English Junior Typewriting');

INSERT INTO courses (name, fee, duration, fee_type, duration_type, duration_months)
SELECT 'English Senior Typewriting', 500, 'Ongoing', 'monthly', 'ongoing', NULL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE name = 'English Senior Typewriting');

INSERT INTO courses (name, fee, duration, fee_type, duration_type, duration_months)
SELECT 'Tamil Junior Typewriting', 500, 'Ongoing', 'monthly', 'ongoing', NULL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE name = 'Tamil Junior Typewriting');

INSERT INTO courses (name, fee, duration, fee_type, duration_type, duration_months)
SELECT 'Tamil Senior Typewriting', 500, 'Ongoing', 'monthly', 'ongoing', NULL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE name = 'Tamil Senior Typewriting');

COMMIT;

-- ----------------------------------------------------------
-- Verify after running:
--   .schema courses
--   .schema payments
--   SELECT id, name, fee, fee_type, duration_type, duration_months FROM courses;
-- ----------------------------------------------------------