-- ============================================
-- MONASTERY HEALTHCARE SYSTEM - REDESIGNED
-- Modern Database Schema v2.0
-- Optimized for Performance & Scalability
-- ============================================

DROP DATABASE IF EXISTS monastery_healthcare_v2;
CREATE DATABASE monastery_healthcare_v2 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE monastery_healthcare_v2;

-- ============================================
-- CORE SYSTEM TABLES
-- ============================================

-- System Roles
CREATE TABLE sys_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    permissions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- System Users (Unified user management)
CREATE TABLE sys_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(255),
    role_id INT NOT NULL,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    two_factor_secret VARCHAR(255),
    recovery_codes JSON,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES sys_roles(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_role (role_id),
    INDEX idx_status (status),
    INDEX idx_uuid (uuid)
) ENGINE=InnoDB;

-- Session Management
CREATE TABLE sys_sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT,
    last_activity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB;

-- Activity Logs
CREATE TABLE sys_activity_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================
-- HEALTHCARE MODULE
-- ============================================

-- Monks Profile
CREATE TABLE healthcare_monks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    monk_id VARCHAR(20) NOT NULL UNIQUE,
    title_prefix VARCHAR(20),
    ordination_date DATE,
    temple_name VARCHAR(200),
    date_of_birth DATE,
    place_of_birth VARCHAR(200),
    blood_group ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    dietary_restrictions TEXT,
    language_preferences JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_monk_id (monk_id),
    INDEX idx_active (is_active),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Medical Conditions & Allergies
CREATE TABLE healthcare_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    monk_id INT NOT NULL,
    type ENUM('allergy', 'chronic', 'acute', 'family_history') NOT NULL,
    condition_name VARCHAR(200) NOT NULL,
    severity ENUM('mild', 'moderate', 'severe') DEFAULT 'mild',
    onset_date DATE,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES healthcare_monks(id) ON DELETE CASCADE,
    INDEX idx_monk_id (monk_id),
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Healthcare Providers
CREATE TABLE healthcare_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    specialization JSON, -- Array of specializations
    qualification VARCHAR(500),
    years_experience INT,
    consultation_fee DECIMAL(10,2),
    bio TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_license (license_number),
    INDEX idx_active (is_active),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Provider Availability
CREATE TABLE healthcare_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_duration INT DEFAULT 30 COMMENT 'Duration in minutes',
    max_patients INT DEFAULT 10,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE CASCADE,
    INDEX idx_provider_id (provider_id),
    INDEX idx_day (day_of_week),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- Facilities & Rooms
CREATE TABLE healthcare_facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    type ENUM('consultation', 'treatment', 'examination', 'procedure', 'laboratory') NOT NULL,
    location VARCHAR(200),
    capacity INT DEFAULT 1,
    equipment JSON,
    status ENUM('available', 'occupied', 'maintenance', 'closed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Appointments
CREATE TABLE healthcare_appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_number VARCHAR(20) NOT NULL UNIQUE,
    monk_id INT NOT NULL,
    provider_id INT NOT NULL,
    facility_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    type ENUM('consultation', 'follow_up', 'emergency', 'routine_checkup') DEFAULT 'consultation',
    status ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    chief_complaint TEXT,
    notes TEXT,
    cancellation_reason TEXT,
    created_by INT,
    confirmed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES healthcare_monks(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE RESTRICT,
    FOREIGN KEY (facility_id) REFERENCES healthcare_facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_monk_id (monk_id),
    INDEX idx_provider_id (provider_id),
    INDEX idx_appointment_number (appointment_number)
) ENGINE=InnoDB;

-- Medical Records
CREATE TABLE healthcare_medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_number VARCHAR(20) NOT NULL UNIQUE,
    monk_id INT NOT NULL,
    provider_id INT NOT NULL,
    appointment_id INT,
    visit_date DATE NOT NULL,
    visit_type ENUM('consultation', 'follow_up', 'emergency', 'routine') NOT NULL,
    vital_signs JSON COMMENT 'BP, pulse, temp, weight, etc.',
    chief_complaint TEXT,
    history_present_illness TEXT,
    physical_examination TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    medications_prescribed JSON,
    laboratory_tests JSON,
    imaging_studies JSON,
    recommendations TEXT,
    follow_up_instructions TEXT,
    next_visit_date DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES healthcare_monks(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES healthcare_providers(id) ON DELETE RESTRICT,
    FOREIGN KEY (appointment_id) REFERENCES healthcare_appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_monk_id (monk_id),
    INDEX idx_visit_date (visit_date),
    INDEX idx_record_number (record_number),
    INDEX idx_provider_id (provider_id)
) ENGINE=InnoDB;

-- ============================================
-- DONATIONS MODULE
-- ============================================

-- Donation Categories
CREATE TABLE donations_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(7) DEFAULT '#007bff',
    target_amount DECIMAL(15,2),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB;

-- Donation Campaigns
CREATE TABLE donations_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL UNIQUE,
    description TEXT,
    goal_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0,
    category_id INT,
    start_date DATE NOT NULL,
    end_date DATE,
    featured_image VARCHAR(500),
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES donations_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB;

-- Donations
CREATE TABLE donations_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(30) NOT NULL UNIQUE,
    donor_user_id INT,
    donor_name VARCHAR(200),
    donor_email VARCHAR(200),
    donor_phone VARCHAR(20),
    donor_address TEXT,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(5) DEFAULT 'LKR',
    category_id INT,
    campaign_id INT,
    payment_method ENUM('bank_transfer', 'cash', 'online', 'cheque', 'mobile_payment') NOT NULL,
    
    -- Payment gateway details
    gateway_name VARCHAR(100),
    gateway_transaction_id VARCHAR(200),
    gateway_order_id VARCHAR(200),
    gateway_response JSON,
    
    -- Bank transfer details
    bank_reference VARCHAR(200),
    bank_slip_image VARCHAR(500),
    
    -- Status tracking
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    
    -- Receipt details
    receipt_number VARCHAR(50),
    receipt_issued_at TIMESTAMP NULL,
    tax_receipt_required BOOLEAN DEFAULT FALSE,
    
    -- Administrative
    notes TEXT,
    internal_notes TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    processed_by INT,
    processed_at TIMESTAMP NULL,
    
    -- Recurring donation details
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('weekly', 'monthly', 'quarterly', 'yearly'),
    recurring_until DATE,
    parent_donation_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donor_user_id) REFERENCES sys_users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES donations_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES donations_campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES sys_users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_donation_id) REFERENCES donations_transactions(id) ON DELETE SET NULL,
    
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_status (status),
    INDEX idx_verification (verification_status),
    INDEX idx_donor (donor_user_id),
    INDEX idx_amount (amount),
    INDEX idx_created_at (created_at),
    INDEX idx_recurring (is_recurring, recurring_frequency)
) ENGINE=InnoDB;