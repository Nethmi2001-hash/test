-- ============================================
-- EXPENSES & FINANCIAL MANAGEMENT
-- ============================================

-- Expense Categories
CREATE TABLE expenses_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    budget_allocation DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Expense Transactions
CREATE TABLE expenses_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_number VARCHAR(30) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(5) DEFAULT 'LKR',
    transaction_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'card') NOT NULL,
    reference_number VARCHAR(200),
    receipt_image VARCHAR(500),
    vendor_name VARCHAR(300),
    vendor_contact VARCHAR(100),
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expenses_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES sys_users(id) ON DELETE RESTRICT,
    INDEX idx_expense_number (expense_number),
    INDEX idx_category (category_id),
    INDEX idx_status (status),
    INDEX idx_date (transaction_date)
) ENGINE=InnoDB;

-- ============================================
-- NOTIFICATIONS & MESSAGING
-- ============================================

-- Notification Templates
CREATE TABLE sys_notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL UNIQUE,
    type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    subject VARCHAR(500),
    body TEXT NOT NULL,
    variables JSON COMMENT 'Available template variables',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type)
) ENGINE=InnoDB;

-- Notifications Queue
CREATE TABLE sys_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    title VARCHAR(500),
    message TEXT NOT NULL,
    data JSON,
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    failure_reason TEXT,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_read_at (read_at),
    INDEX idx_sent_at (sent_at),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================
-- REPORTS & ANALYTICS
-- ============================================

-- Report Templates
CREATE TABLE reports_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(300) NOT NULL,
    slug VARCHAR(300) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    query_template TEXT NOT NULL,
    parameters JSON,
    chart_config JSON,
    access_roles JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Generated Reports
CREATE TABLE reports_generated (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    parameters JSON,
    file_path VARCHAR(1000),
    format ENUM('pdf', 'excel', 'csv', 'json') NOT NULL,
    status ENUM('generating', 'completed', 'failed') DEFAULT 'generating',
    generated_by INT NOT NULL,
    generated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    file_size_bytes BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES reports_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES sys_users(id) ON DELETE CASCADE,
    INDEX idx_template_id (template_id),
    INDEX idx_status (status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

-- ============================================
-- SYSTEM CONFIGURATION
-- ============================================

-- System Settings
CREATE TABLE sys_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(200) NOT NULL UNIQUE,
    key_value TEXT,
    data_type ENUM('string', 'integer', 'boolean', 'json', 'text') DEFAULT 'string',
    category VARCHAR(100) DEFAULT 'general',
    description TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_public (is_public)
) ENGINE=InnoDB;

-- File Uploads
CREATE TABLE sys_file_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    original_name VARCHAR(500) NOT NULL,
    file_name VARCHAR(500) NOT NULL,
    file_path VARCHAR(1000) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(200),
    file_extension VARCHAR(20),
    entity_type VARCHAR(100),
    entity_id INT,
    uploaded_by INT,
    is_public BOOLEAN DEFAULT FALSE,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_uuid (uuid),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_uploaded_by (uploaded_by)
) ENGINE=InnoDB;

-- ============================================
-- INITIAL DATA SETUP
-- ============================================

-- Insert system roles
INSERT INTO sys_roles (name, slug, permissions) VALUES
('Administrator', 'admin', '["*"]'),
('Healthcare Provider', 'doctor', '["healthcare.*", "reports.healthcare.*"]'),
('Monk', 'monk', '["healthcare.appointments.own", "healthcare.records.own"]'),
('Donor', 'donor', '["donations.*", "reports.donations.own"]'),
('Helper/Assistant', 'helper', '["healthcare.appointments.*", "donations.verify"]);

-- Insert default categories
INSERT INTO donations_categories (name, slug, description, icon, color) VALUES
('Medical Supplies', 'medical-supplies', 'Medicines, medical equipment, and healthcare supplies', 'medical-bag', '#dc3545'),
('Food & Nutrition', 'food-nutrition', 'Daily meals, nutrition supplements, and food supplies', 'utensils', '#28a745'),
('Utilities', 'utilities', 'Electricity, water, internet, and other utility bills', 'plug', '#ffc107'),
('Education', 'education', 'Books, educational materials, and learning resources', 'graduation-cap', '#007bff'),
('Infrastructure', 'infrastructure', 'Building maintenance, repairs, and improvements', 'tools', '#6c757d'),
('General Fund', 'general-fund', 'General purpose donations for monastery operations', 'hand-holding-heart', '#17a2b8');

INSERT INTO expenses_categories (name, slug, description, budget_allocation) VALUES
('Medical Expenses', 'medical-expenses', 'Healthcare costs, medicines, and medical supplies', 50000.00),
('Food & Kitchen', 'food-kitchen', 'Daily meals, kitchen supplies, and nutrition', 30000.00),
('Utilities', 'utilities', 'Electricity, water, gas, internet, and phone bills', 15000.00),
('Maintenance', 'maintenance', 'Building repairs, equipment maintenance, and upkeep', 20000.00),
('Transportation', 'transportation', 'Vehicle maintenance, fuel, and travel expenses', 10000.00),
('Administration', 'administration', 'Office supplies, documentation, and admin costs', 8000.00);

-- Insert default system settings
INSERT INTO sys_settings (key_name, key_value, data_type, category, description) VALUES
('app_name', 'Monastery Healthcare System', 'string', 'general', 'Application name'),
('app_version', '2.0.0', 'string', 'general', 'Application version'),
('default_currency', 'LKR', 'string', 'financial', 'Default currency for transactions'),
('appointment_duration_default', '30', 'integer', 'healthcare', 'Default appointment duration in minutes'),
('max_file_upload_size', '10485760', 'integer', 'system', 'Maximum file upload size in bytes (10MB)'),
('enable_email_notifications', 'true', 'boolean', 'notifications', 'Enable email notifications'),
('enable_sms_notifications', 'false', 'boolean', 'notifications', 'Enable SMS notifications'),
('backup_retention_days', '30', 'integer', 'system', 'Number of days to retain database backups');

-- Create default admin user
INSERT INTO sys_users (uuid, email, username, password_hash, first_name, last_name, phone, role_id, status, email_verified_at) VALUES
(UUID(), 'admin@monastery.lk', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '+94771234567', 1, 'active', NOW());

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- Monk profile with user details
CREATE OR REPLACE VIEW view_monks_profile AS
SELECT 
    m.id,
    m.monk_id,
    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
    m.title_prefix,
    u.email,
    u.phone,
    m.date_of_birth,
    TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE()) AS age,
    m.blood_group,
    m.emergency_contact_name,
    m.emergency_contact_phone,
    m.temple_name,
    m.ordination_date,
    m.is_active,
    u.status AS user_status
FROM healthcare_monks m
JOIN sys_users u ON m.user_id = u.id;

-- Healthcare providers with user details
CREATE OR REPLACE VIEW view_healthcare_providers AS
SELECT 
    p.id,
    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
    p.license_number,
    p.specialization,
    p.qualification,
    p.years_experience,
    p.consultation_fee,
    u.email,
    u.phone,
    p.is_active,
    u.status AS user_status
FROM healthcare_providers p
JOIN sys_users u ON p.user_id = u.id;

-- Appointment details view
CREATE OR REPLACE VIEW view_appointments_detailed AS
SELECT 
    a.id,
    a.appointment_number,
    a.appointment_date,
    a.appointment_time,
    a.status,
    a.type,
    a.priority,
    a.chief_complaint,
    mp.full_name AS monk_name,
    mp.monk_id,
    hp.full_name AS provider_name,
    hp.specialization AS provider_specialization,
    f.name AS facility_name,
    f.type AS facility_type,
    CONCAT(cu.first_name, ' ', cu.last_name) AS created_by_name
FROM healthcare_appointments a
JOIN view_monks_profile mp ON a.monk_id = mp.id
JOIN view_healthcare_providers hp ON a.provider_id = hp.id
LEFT JOIN healthcare_facilities f ON a.facility_id = f.id
LEFT JOIN sys_users cu ON a.created_by = cu.id;

-- Donation summary view
CREATE OR REPLACE VIEW view_donations_summary AS
SELECT 
    d.id,
    d.transaction_number,
    d.amount,
    d.currency,
    d.status,
    d.verification_status,
    d.donor_name,
    d.donor_email,
    dc.name AS category_name,
    dc.slug AS category_slug,
    dcam.title AS campaign_title,
    d.payment_method,
    d.created_at
FROM donations_transactions d
LEFT JOIN donations_categories dc ON d.category_id = dc.id
LEFT JOIN donations_campaigns dcam ON d.campaign_id = dcam.id;

COMMIT;