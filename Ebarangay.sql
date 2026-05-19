-- ============================================================
-- E-Barangay Online Access System - Database Setup
-- Import this file via phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS ebarangay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ebarangay;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    place_of_birth VARCHAR(150),
    civil_status ENUM('Single','Married','Widowed','Separated','Annulled') DEFAULT 'Single',
    citizenship VARCHAR(100) DEFAULT 'Filipino',
    cedula_number VARCHAR(50),
    gender ENUM('Male','Female','Other') DEFAULT 'Male',
    role ENUM('admin','resident') DEFAULT 'resident',
    status ENUM('active','inactive') DEFAULT 'active',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: document_requests
-- ============================================================
CREATE TABLE IF NOT EXISTS document_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('barangay_clearance','certificate_of_indigency','business_clearance','barangay_blotter') NOT NULL,
    status ENUM('Pending','Processing','Approved','Released','Rejected') DEFAULT 'Pending',
    purpose TEXT,
    admin_remarks TEXT DEFAULT NULL,
    -- Barangay Clearance & Residency
    full_name VARCHAR(150),
    complete_address TEXT,
    date_of_birth DATE,
    place_of_birth VARCHAR(150),
    civil_status VARCHAR(50),
    citizenship VARCHAR(100),
    period_of_residency VARCHAR(100),
    cedula_number VARCHAR(50),
    -- Certificate of Indigency extras
    monthly_income DECIMAL(12,2),
    annual_income DECIMAL(12,2),
    target_institution VARCHAR(150),
    specific_benefit VARCHAR(150),
    -- Business Clearance extras
    business_name VARCHAR(150),
    business_address TEXT,
    type_of_ownership ENUM('Sole Proprietorship','Partnership','Corporation'),
    nature_of_business VARCHAR(200),
    capital_investment DECIMAL(15,2),
    -- Blotter extras
    complainant_name VARCHAR(150),
    complainant_address TEXT,
    complainant_contact VARCHAR(50),
    respondent_name VARCHAR(150),
    respondent_address TEXT,
    respondent_contact VARCHAR(50),
    case_type VARCHAR(100),
    date_of_occurrence DATETIME,
    place_of_incident TEXT,
    narrative_of_events LONGTEXT,
    witnesses TEXT,
    evidence_description TEXT,
    desired_action TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: announcements
-- ============================================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    is_pinned TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('request','announcement','system','status_update') DEFAULT 'system',
    reference_id INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
-- Password: Admin@1234 (bcrypt hashed)
-- ============================================================
INSERT INTO users (full_name, email, password, phone, address, role, status, gender, civil_status) VALUES
('Barangay Administrator', 'admin@ebarangay.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09000000000', 'Barangay Hall, Sample City', 'admin', 'active', 'Male', 'Single');

-- ============================================================
-- SAMPLE RESIDENT ACCOUNT
-- Password: Resident@1234 (bcrypt hashed)
-- ============================================================
INSERT INTO users (full_name, email, password, phone, address, date_of_birth, place_of_birth, civil_status, citizenship, cedula_number, role, status, gender) VALUES
('Juan dela Cruz', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '09123456789', '123 Rizal St., Barangay Sample', '1990-05-15', 'Sample City', 'Single', 'Filipino', 'CED-2024-001', 'resident', 'active', 'Male');

-- ============================================================
-- SAMPLE ANNOUNCEMENTS
-- ============================================================
INSERT INTO announcements (admin_id, title, content, category, is_pinned) VALUES
(1, 'Welcome to E-Barangay Online Access System', 'We are pleased to announce the launch of our new online barangay services portal. Residents can now request documents, view announcements, and track their requests online. For assistance, please visit the barangay hall during office hours.', 'General', 1),
(1, 'Senior Citizens Cash Assistance Program', 'All registered senior citizens aged 60 and above are encouraged to claim their quarterly cash assistance. Please bring your Senior Citizen ID, barangay clearance, and 2 valid IDs to the barangay hall.', 'Social Services', 0),
(1, 'Road Repair Notice: Rizal Street', 'Please be advised that road repair works will commence along Rizal Street starting next Monday. Expect minor traffic disruptions from 8:00 AM to 5:00 PM. We apologize for the inconvenience.', 'Infrastructure', 0);

-- ============================================================
-- SAMPLE DOCUMENT REQUESTS
-- ============================================================
INSERT INTO document_requests (user_id, document_type, status, purpose, full_name, complete_address, date_of_birth, place_of_birth, civil_status, citizenship, period_of_residency, cedula_number) VALUES
(2, 'barangay_clearance', 'Approved', 'Employment', 'Juan dela Cruz', '123 Rizal St., Barangay Sample', '1990-05-15', 'Sample City', 'Single', 'Filipino', '5 years', 'CED-2024-001');

-- ============================================================
-- SAMPLE NOTIFICATIONS
-- ============================================================
INSERT INTO notifications (user_id, title, message, type, reference_id, is_read) VALUES
(2, 'Document Request Submitted', 'Your Barangay Clearance request has been submitted and is now pending review.', 'request', 1, 0),
(2, 'Request Status Updated', 'Your Barangay Clearance request has been Approved. You may now claim your document at the barangay hall.', 'status_update', 1, 0),
(1, 'New Document Request', 'Juan dela Cruz submitted a Barangay Clearance request.', 'request', 1, 1);