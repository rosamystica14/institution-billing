-- Institution Billing & Fee Management System
-- SQLite Schema
-- v2: fixed-fee + monthly-fee (Typewriting) billing support

PRAGMA foreign_keys = ON;

-- ==========================
-- COURSES
-- ==========================
CREATE TABLE IF NOT EXISTS courses (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT NOT NULL,
    fee                 REAL NOT NULL DEFAULT 0,     -- fixed total fee, or monthly amount if fee_type='monthly'
    fee_type            TEXT NOT NULL DEFAULT 'fixed'
                            CHECK (fee_type IN ('fixed', 'monthly')),
    duration            TEXT,                        -- display text, e.g. "3 Months" / "Ongoing"
    duration_type       TEXT NOT NULL DEFAULT 'fixed'
                            CHECK (duration_type IN ('fixed', 'ongoing')),
    duration_months     INTEGER,                     -- NULL when duration_type = 'ongoing'
    created_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- ==========================
-- STUDENTS
-- ==========================
CREATE TABLE IF NOT EXISTS students (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT NOT NULL,
    father_name         TEXT,
    mobile              TEXT NOT NULL,
    whatsapp_number     TEXT,
    parent_contact      TEXT,
    gender              TEXT,
    dob                 TEXT,
    course_id           INTEGER NOT NULL,
    admission_date      TEXT NOT NULL,
    address             TEXT,
    photo               TEXT,
    timing              TEXT,
    reference_source    TEXT,
    qualification       TEXT,
    school_college      TEXT,
    qualification_year  TEXT,
    -- Fee override, applies only to fixed-fee courses.
    -- See includes/functions.php -> computeFinalFee()
    -- fee_mode: 'default' (follows course fee), 'discount' (% off course fee), 'custom' (flat manual amount)
    fee_mode            TEXT NOT NULL DEFAULT 'default',
    discount_percent    REAL,
    final_fee           REAL,
    status              TEXT NOT NULL DEFAULT 'active',
    created_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at          TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
);

-- ==========================
-- PAYMENTS
-- ==========================
CREATE TABLE IF NOT EXISTS payments (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id      INTEGER NOT NULL,
    receipt_no      TEXT NOT NULL UNIQUE,
    amount          REAL NOT NULL,
    payment_date    TEXT NOT NULL,
    payment_mode    TEXT NOT NULL DEFAULT 'Cash',
    remarks         TEXT,
    -- Only used when the student's course is fee_type = 'monthly'.
    -- Identifies which calendar month this payment covers.
    billing_month   INTEGER CHECK (billing_month IS NULL OR (billing_month BETWEEN 1 AND 12)),
    billing_year    INTEGER,
    created_at      TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ==========================
-- WHATSAPP LOGS
-- ==========================
CREATE TABLE IF NOT EXISTS whatsapp_logs (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id      INTEGER,
    payment_id      INTEGER,
    phone           TEXT NOT NULL,
    message         TEXT NOT NULL,
    status          TEXT NOT NULL DEFAULT 'pending',
    response        TEXT,
    created_at      TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
);

-- ==========================
-- SETTINGS (single row table)
-- ==========================
CREATE TABLE IF NOT EXISTS settings (
    id                      INTEGER PRIMARY KEY CHECK (id = 1),
    institution_name        TEXT NOT NULL DEFAULT 'My Institution',
    logo                     TEXT,
    address                  TEXT,
    phone                    TEXT,
    email                    TEXT,
    whatsapp_token           TEXT,
    whatsapp_phone_number_id TEXT,
    whatsapp_api_version     TEXT DEFAULT 'v19.0',
    updated_at               TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- Indexes for faster search
CREATE INDEX IF NOT EXISTS idx_students_name ON students(name);
CREATE INDEX IF NOT EXISTS idx_students_mobile ON students(mobile);
CREATE INDEX IF NOT EXISTS idx_payments_student ON payments(student_id);
CREATE INDEX IF NOT EXISTS idx_payments_date ON payments(payment_date);
CREATE INDEX IF NOT EXISTS idx_payments_receipt ON payments(receipt_no);
CREATE INDEX IF NOT EXISTS idx_payments_billing ON payments(student_id, billing_year, billing_month);