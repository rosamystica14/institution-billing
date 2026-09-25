-- Default settings row (id must be 1)
INSERT OR IGNORE INTO settings (id, institution_name, address, phone, email, whatsapp_api_version)
VALUES (1, 'My Institution', '123 Main Street, Your City', '9999999999', 'info@myinstitution.com', 'v19.0');

-- ==========================
-- Fixed-fee courses (unchanged behavior)
-- ==========================
INSERT INTO courses (name, fee, fee_type, duration, duration_type, duration_months) VALUES
('Basic Computer Course', 5000, 'fixed', '3 Months', 'fixed', 3),
('Tally with GST',        8000, 'fixed', '2 Months', 'fixed', 2),
('Web Development',      15000, 'fixed', '6 Months', 'fixed', 6),
('Spoken English',        4000, 'fixed', '2 Months', 'fixed', 2),
('Core Java',              6000, 'fixed', '3 Months', 'fixed', 3);

-- ==========================
-- Monthly-fee courses (new: Typewriting)
-- fee = amount PER MONTH, duration_type = ongoing (no end date)
-- ==========================
INSERT INTO courses (name, fee, fee_type, duration, duration_type, duration_months) VALUES
('English Junior Typewriting', 500, 'monthly', 'Ongoing', 'ongoing', NULL),
('English Senior Typewriting', 500, 'monthly', 'Ongoing', 'ongoing', NULL),
('Tamil Junior Typewriting',   500, 'monthly', 'Ongoing', 'ongoing', NULL),
('Tamil Senior Typewriting',   500, 'monthly', 'Ongoing', 'ongoing', NULL);

-- ==========================
-- Sample students (course_id refers to insertion order above:
-- 1 Basic Computer, 2 Tally, 3 Web Dev, 4 Spoken English, 5 Core Java,
-- 6 Eng Junior Type, 7 Eng Senior Type, 8 Tamil Junior Type, 9 Tamil Senior Type)
-- ==========================
INSERT INTO students (name, father_name, mobile, whatsapp_number, course_id, admission_date, address) VALUES
('Arun Kumar',   'Ravi Kumar',    '9876543210', '9876543210', 1, date('now', '-20 days'), 'Chennai'),
('Priya Sharma', 'Suresh Sharma', '9876543211', '9876543211', 3, date('now', '-45 days'), 'Bangalore'),
('Mohammed Ali', 'Ibrahim Ali',   '9876543212', '9876543212', 2, date('now', '-10 days'), 'Hyderabad'),
('Ravi',         'Selvam',        '9876543213', '9876543213', 6, date('now', '-40 days'), 'Arantangi');

-- ==========================
-- Sample payments
-- Fixed-course payments: billing_month/billing_year left NULL.
-- Monthly-course payment: billing_month/billing_year set.
-- ==========================
INSERT INTO payments (student_id, receipt_no, amount, payment_date, payment_mode, remarks, billing_month, billing_year) VALUES
(1, 'RCPT-2026-00001', 2000, date('now', '-20 days'), 'Cash', 'Admission fee',    NULL, NULL),
(1, 'RCPT-2026-00002', 1500, date('now', '-5 days'),  'UPI',  'Partial payment',  NULL, NULL),
(2, 'RCPT-2026-00003', 7000, date('now', '-45 days'), 'Cash', 'First installment',NULL, NULL),
(3, 'RCPT-2026-00004', 8000, date('now', '-10 days'), 'UPI',  'Full payment',     NULL, NULL),
(4, 'RCPT-2026-00005', 500,  date('now', '-10 days'), 'Cash', 'Typewriting fee',  8,    2026);