-- ============================================================
-- database.sql — Full Schema for Scholarship Management System
-- ============================================================
-- HOW TO IMPORT:
--   Option A (phpMyAdmin): Open phpMyAdmin → Import tab → choose this file.
--   Option B (Terminal):   mysql -u root -p < database.sql
-- ============================================================

-- ------------------------------------
-- Create & select the database
-- ------------------------------------


USE scholarship_system;

-- ============================================================
-- TABLE 1: users
-- Stores both students and admins.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id       INT           AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)  NOT NULL,
    email         VARCHAR(120)  UNIQUE NOT NULL,
    phone         VARCHAR(20),
    password      VARCHAR(255)  NOT NULL,              -- Store hashed passwords (password_hash)
    role          ENUM('student','admin')  DEFAULT 'student',
    profile_image VARCHAR(255),
    status        ENUM('active','inactive') DEFAULT 'active',
    last_login    TIMESTAMP     NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: scholarships
-- Scholarships posted by admins.
-- ============================================================
CREATE TABLE IF NOT EXISTS scholarships (
    scholarship_id     INT            AUTO_INCREMENT PRIMARY KEY,
    title              VARCHAR(255)   NOT NULL,
    provider           VARCHAR(150),                   -- e.g. HEC, Punjab Govt
    category           VARCHAR(100),                   -- e.g. STEM, Arts
    level              ENUM('Matric','Intermediate','Undergraduate','Postgraduate'),
    type               ENUM('Merit','Need-Based','Talent-Based'),
    description        TEXT,

    -- Eligibility
    eligibility_criteria TEXT,
    min_gpa            DECIMAL(3,2),                   -- e.g. 3.00
    max_age            INT,
    gender_requirement ENUM('Male','Female','Any') DEFAULT 'Any',

    -- Financial
    amount             DECIMAL(10,2),                  -- Scholarship value in PKR
    total_seats        INT,

    -- Dates
    start_date         DATE,
    deadline           DATE,

    status             ENUM('active','closed') DEFAULT 'active',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 3: applications
-- A student's application for a specific scholarship.
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    application_id     INT          AUTO_INCREMENT PRIMARY KEY,
    user_id            INT          NOT NULL,
    scholarship_id     INT          NOT NULL,

    -- Personal Info (filled at apply time)
    father_name        VARCHAR(120),
    cnic               VARCHAR(25),
    date_of_birth      DATE,
    gender             VARCHAR(10),
    address            TEXT,

    -- Academic Info
    matric_marks       VARCHAR(20),
    intermediate_marks VARCHAR(20),
    university         VARCHAR(150),

    -- Workflow status
    status ENUM(
        'pending',       -- just submitted
        'under_review',  -- admin is reviewing
        'approved',      -- accepted
        'rejected',      -- not accepted
        'disbursed'      -- money sent
    ) DEFAULT 'pending',

    applied_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Prevent a student from applying to the same scholarship twice
    UNIQUE KEY uq_user_scholarship (user_id, scholarship_id),

    FOREIGN KEY (user_id)         REFERENCES users(user_id)         ON DELETE CASCADE,
    FOREIGN KEY (scholarship_id)  REFERENCES scholarships(scholarship_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 4: application_documents
-- Files uploaded with an application (CNIC, transcript, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS application_documents (
    document_id    INT          AUTO_INCREMENT PRIMARY KEY,
    application_id INT          NOT NULL,
    document_name  VARCHAR(150),           -- e.g. "CNIC Front", "Matric Certificate"
    file_path      VARCHAR(255),           -- relative path on server: uploads/docs/file.pdf
    file_type      VARCHAR(50),            -- e.g. application/pdf, image/jpeg
    uploaded_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
token VARCHAR(128) NOT NULL,
expires_at DATETIME NOT NULL,
used BOOLEAN DEFAULT FALSE,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ============================================================
-- TABLE 5: payments
-- Tracks disbursement of scholarship money.
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    payment_id     INT           AUTO_INCREMENT PRIMARY KEY,
    application_id INT           NOT NULL,
    amount         DECIMAL(10,2),
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    payment_date   TIMESTAMP     NULL,
    remarks        TEXT,                   -- e.g. bank transfer reference

    FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 6: notifications
-- In-app alerts sent to students or admins.
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT  AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    message         TEXT,
    type            ENUM('info','warning','success','application','payment'),
    is_read         BOOLEAN   DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 7: contact_messages
-- Messages submitted via the public Contact Us form.
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT          AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(150),
    message    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 8: activity_logs
-- Audit trail — records every important action by any user.
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id      INT           AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,                        -- NULL if action is by a guest
    action      VARCHAR(100),               -- e.g. 'LOGIN', 'APPLY', 'STATUS_CHANGE'
    table_name  VARCHAR(100),               -- which table was affected
    record_id   INT,                        -- PK of the affected row
    description TEXT,                       -- human-readable details
    ip_address  VARCHAR(50),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SAMPLE ADMIN ACCOUNT
-- Password hash below = password_hash('Admin@1234', PASSWORD_BCRYPT)
-- Change the password immediately after first login!
-- ============================================================
INSERT INTO users (full_name, email, phone, password, role, status)
VALUES (
    'System Administrator',
    'admin@scholarship.com',
    '03000000000',
    '$2y$10$W971JuKpf6AjYYDUAAc50OlOI.uBipCxUv7syCHekdcy7KCcRLFVC', -- "Akram@123"
    'admin',
    'active'
);

-- ============================================================
-- End of schema
-- ============================================================
