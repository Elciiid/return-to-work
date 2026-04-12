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
('EMP003', 'Alice', 'Nurse', 'Clinic', 'Company Nurse', 'nurse', TRUE)
ON CONFLICT ("EmployeeID") DO NOTHING;

INSERT INTO rtw_user_permissions (employee_id, permission_type)
VALUES ('EMP001', 'APPROVER'), ('EMP001', 'SHE_IMPERSONATOR')
ON CONFLICT DO NOTHING;

INSERT INTO rtw_supervisors (employee_id, department)
VALUES ('EMP002', 'Human Resources')
ON CONFLICT DO NOTHING;
