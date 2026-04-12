-- Migration Setup Script for PostgreSQL (Supabase)

-- 1. Session Table
CREATE TABLE IF NOT EXISTS rtw_app_sessions (
    id VARCHAR(255) PRIMARY KEY,
    data TEXT NOT NULL,
    timestamp INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_timestamp ON rtw_app_sessions(timestamp);

-- 2. Users Table (Bcrypt passwords)
CREATE TABLE IF NOT EXISTS rtw_app_users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Master List (Simplified for demo)
CREATE TABLE IF NOT EXISTS rtw_master_list (
    "EmployeeID" VARCHAR(50) PRIMARY KEY,
    "FirstName" VARCHAR(100),
    "LastName" VARCHAR(100),
    "Department" VARCHAR(255),
    "PositionTitle" VARCHAR(255),
    "BiometricsID" VARCHAR(50),
    "IsActive" BOOLEAN DEFAULT TRUE
);

-- 4. User Permissions
CREATE TABLE IF NOT EXISTS rtw_user_permissions (
    id SERIAL PRIMARY KEY,
    employee_id VARCHAR(50),
    permission_type VARCHAR(100),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 5. Supervisors
CREATE TABLE IF NOT EXISTS rtw_supervisors (
    id SERIAL PRIMARY KEY,
    employee_id VARCHAR(50),
    department VARCHAR(255),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. Return to Work Applications
CREATE TABLE IF NOT EXISTS rtw_return_to_work (
    id SERIAL PRIMARY KEY,
    date TIMESTAMPTZ DEFAULT NOW(),
    employee_number VARCHAR(50),
    employee_id VARCHAR(50),
    employee_name VARCHAR(255),
    department VARCHAR(255),
    prodn_type VARCHAR(255),
    days_absence INT,
    first_date_absence DATE,
    date_returned DATE,
    reason TEXT,
    notified_superior VARCHAR(10),
    superior_name_position VARCHAR(255),
    filing_date DATE DEFAULT CURRENT_DATE,
    medical_certificate_path TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    -- Nurse Portal Fields
    nurse_declaration VARCHAR(100),
    nurse_reason TEXT,
    nurse_declaration_date TIMESTAMPTZ,
    -- Clinical Assessment Fields
    clinic_med_cert_clarification TEXT,
    clinic_date_declared TIMESTAMPTZ,
    clinic_doctor_assessment TEXT,
    -- Approval Fields
    approved_by VARCHAR(255),
    approved_at TIMESTAMPTZ
);

-- MOCK DATA
-- Password is 'password' hashed with bcrypt
-- Note: Replace with actual hash for production
INSERT INTO rtw_app_users (username, password, full_name, role)
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'),
('nurse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Nurse', 'company nurse')
ON CONFLICT (username) DO NOTHING;

INSERT INTO rtw_master_list ("EmployeeID", "FirstName", "LastName", "Department", "PositionTitle", "BiometricsID", "IsActive")
VALUES 
('EMP001', 'John', 'Doe', 'Information Technology Department', 'Software Engineer', 'admin', TRUE),
('EMP002', 'Jane', 'Smith', 'Human Resources', 'HR Manager', '101', TRUE),
('EMP003', 'Alice', 'Nurse', 'Clinic', 'Company Nurse', 'nurse', TRUE),
('EMP004', 'Robert', 'Taylor', 'Production Department', 'Production Manager', '104', TRUE),
('EMP005', 'Maria', 'Garcia', 'Quality Assurance', 'QA Supervisor', '105', TRUE),
('EMP006', 'James', 'Wilson', 'Finance Department', 'Finance Head', '106', TRUE),
('EMP007', 'Sarah', 'Miller', 'Production Department', 'Line Lead', '107', TRUE),
('EMP008', 'Michael', 'Brown', 'Logistics', 'Logistics Supervisor', '108', TRUE),
('EMP009', 'Elena', 'Lopez', 'Human Resources', 'HR Specialist', '109', TRUE),
('EMP010', 'David', 'Clark', 'Production Department', 'Machine Operator', '110', TRUE),
('EMP011', 'Lim', 'Tae-oh', 'Management', 'Executive Director', '111', TRUE),
('EMP012', 'Chen', 'Wei', 'Operations', 'Operations Manager', '112', TRUE),
('EMP013', 'Yuki', 'Tanaka', 'Production Department', 'Shift Supervisor', '113', TRUE)
ON CONFLICT ("EmployeeID") DO NOTHING;

INSERT INTO rtw_user_permissions (employee_id, permission_type)
VALUES 
('EMP001', 'APPROVER'),
('EMP001', 'SHE_IMPERSONATOR'),
('EMP002', 'APPROVER'),
('EMP004', 'APPROVER'),
('EMP005', 'APPROVER'),
('EMP006', 'APPROVER'),
('EMP008', 'APPROVER'),
('EMP011', 'APPROVER'),
('EMP012', 'APPROVER'),
('EMP013', 'APPROVER')
ON CONFLICT DO NOTHING;

-- MOCK Return-to-Work Applications
INSERT INTO rtw_return_to_work (
    "employee_number", "employee_id", "employee_name", "department", "prodn_type", 
    "days_absence", "first_date_absence", "date_returned", "reason", 
    "notified_superior", "superior_name_position", "filing_date", "status",
    "nurse_declaration", "nurse_reason", "nurse_declaration_date", "approved_by", "approved_at"
)
VALUES 
-- PENDING APPROVAL (Needs Admin action)
('109', 'EMP009', 'Elena Lopez', 'Human Resources', 'Non-Production', 3, CURRENT_DATE - 4, CURRENT_DATE - 1, 'Flu recovery', 'Yes', 'Jane Smith', CURRENT_DATE - 1, 'Pending', 'Fit', 'Fully recovered, no symptoms.', CURRENT_TIMESTAMP - INTERVAL '2 hours', NULL, NULL),
('110', 'EMP010', 'David Clark', 'Production Department', 'Production', 5, CURRENT_DATE - 6, CURRENT_DATE, 'Personal reasons', 'Yes', 'Robert Taylor', CURRENT_DATE, 'Pending', 'Fit', 'No health issues reported.', CURRENT_TIMESTAMP - INTERVAL '1 hour', NULL, NULL),

-- PENDING NURSE (Needs Nurse action)
('107', 'EMP007', 'Sarah Miller', 'Production Department', 'Production', 2, CURRENT_DATE - 3, CURRENT_DATE, 'Mild headache', 'Yes', 'Robert Taylor', CURRENT_DATE, 'Pending Nurse', NULL, NULL, NULL, NULL, NULL),
('113', 'EMP013', 'Yuki Tanaka', 'Production Department', 'Production', 4, CURRENT_DATE - 5, CURRENT_DATE - 1, 'Muscle pain', 'Yes', 'Yuki Tanaka', CURRENT_DATE - 1, 'Pending Nurse', NULL, NULL, NULL, NULL, NULL),

-- APPROVED (History)
('admin', 'EMP001', 'John Doe', 'Information Technology Department', 'Non-Production', 1, CURRENT_DATE - 10, CURRENT_DATE - 9, 'Fever', 'Yes', 'Lim Tae-oh', CURRENT_DATE - 9, 'Approved', 'Fit', 'Cleared after rest.', CURRENT_DATE - 9, 'Lim Tae-oh', CURRENT_DATE - 9),
('104', 'EMP004', 'Robert Taylor', 'Production Department', 'Production', 7, CURRENT_DATE - 15, CURRENT_DATE - 8, 'COVID-19 Follow-up', 'Yes', 'Lim Tae-oh', CURRENT_DATE - 8, 'Approved', 'Fit', 'Negative test verified.', CURRENT_DATE - 8, 'Lim Tae-oh', CURRENT_DATE - 8),
('105', 'EMP005', 'Maria Garcia', 'Quality Assurance', 'Non-Production', 3, CURRENT_DATE - 12, CURRENT_DATE - 9, 'Migraine', 'Yes', 'Jane Smith', CURRENT_DATE - 9, 'Approved', 'Fit', 'Symptom free.', CURRENT_DATE - 9, 'Jane Smith', CURRENT_DATE - 9),

-- DECLINED (History)
('108', 'EMP008', 'Michael Brown', 'Logistics', 'Non-Production', 2, CURRENT_DATE - 4, CURRENT_DATE - 2, 'Cough and Colds', 'Yes', 'Jane Smith', CURRENT_DATE - 2, 'Declined', 'Unfit', 'Sore throat and cough still present.', CURRENT_DATE - 2, 'Jane Smith', CURRENT_DATE - 2)
ON CONFLICT DO NOTHING;
