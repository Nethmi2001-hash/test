-- MONASTERY HEALTHCARE & DONATION MANAGEMENT SYSTEM
-- Complete Database Schema

SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS appointment_slots;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS donation_categories;
DROP TABLE IF EXISTS doctor_availability;
DROP TABLE IF EXISTS room_assignments;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS monks;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS donators;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS audit_logs;

-- Users table (Master table for all user types)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'monk', 'doctor', 'donator') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    profile_image VARCHAR(255)
);

-- Monks table
CREATE TABLE monks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    monk_id VARCHAR(20) NOT NULL UNIQUE,
    ordained_date DATE,
    age INT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    blood_group VARCHAR(5),
    medical_conditions TEXT,
    room_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Doctors table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_id VARCHAR(20) NOT NULL UNIQUE,
    specialization VARCHAR(100),
    qualifications TEXT,
    experience_years INT DEFAULT 0,
    license_number VARCHAR(50),
    available_days SET('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
    availability_start TIME DEFAULT '09:00:00',
    availability_end TIME DEFAULT '17:00:00',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Donators table
CREATE TABLE donators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    donator_id VARCHAR(20) NOT NULL UNIQUE,
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    country VARCHAR(50) DEFAULT 'Sri Lanka',
    total_donated DECIMAL(12,2) DEFAULT 0.00,
    first_donation_date TIMESTAMP NULL,
    last_donation_date TIMESTAMP NULL,
    donation_count INT DEFAULT 0,
    preferred_categories SET('food', 'medical', 'electricity', 'water', 'maintenance', 'education'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rooms table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type ENUM('single', 'shared', 'dormitory') NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    current_occupancy INT DEFAULT 0,
    floor INT,
    building VARCHAR(50),
    facilities TEXT,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Room assignments (history table)
CREATE TABLE room_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    monk_id INT NOT NULL,
    room_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'ended') DEFAULT 'active',
    notes TEXT,
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES monks(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id)
);

-- Donation categories
CREATE TABLE donation_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    target_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    current_amount DECIMAL(12,2) DEFAULT 0.00,
    monthly_target DECIMAL(12,2) DEFAULT 0.00,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Donations table
CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donation_id VARCHAR(20) NOT NULL UNIQUE,
    donator_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    donation_method ENUM('cash', 'bank_transfer', 'online', 'check', 'other') NOT NULL,
    transaction_reference VARCHAR(100),
    status ENUM('pending', 'verified', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    verification_notes TEXT,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    donation_date DATE NOT NULL,
    receipt_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (donator_id) REFERENCES donators(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES donation_categories(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id VARCHAR(20) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    vendor_name VARCHAR(100),
    bill_image VARCHAR(255),
    receipt_number VARCHAR(50),
    payment_method ENUM('cash', 'bank_transfer', 'check', 'card', 'other') NOT NULL,
    status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES donation_categories(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Medical records table
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(20) NOT NULL UNIQUE,
    monk_id INT NOT NULL,
    doctor_id INT NOT NULL,
    visit_date DATE NOT NULL,
    chief_complaint TEXT,
    diagnosis TEXT NOT NULL,
    prescription TEXT,
    treatment_notes TEXT,
    vital_signs JSON, -- Store BP, temperature, pulse, etc.
    lab_results TEXT,
    next_appointment_date DATE NULL,
    status ENUM('active', 'completed', 'follow_up_required') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES monks(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id)
);

-- Doctor availability
CREATE TABLE doctor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    available_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_appointments INT DEFAULT 8,
    current_appointments INT DEFAULT 0,
    status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_datetime (doctor_id, available_date, start_time)
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id VARCHAR(20) NOT NULL UNIQUE,
    monk_id INT NOT NULL,
    doctor_id INT NOT NULL,
    availability_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    reason TEXT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (monk_id) REFERENCES monks(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (availability_id) REFERENCES doctor_availability(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Appointment slots (30-min slots)
CREATE TABLE appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    availability_id INT NOT NULL,
    slot_time TIME NOT NULL,
    is_booked BOOLEAN DEFAULT FALSE,
    appointment_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (availability_id) REFERENCES doctor_availability(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    UNIQUE KEY unique_slot (availability_id, slot_time)
);

-- Audit logs
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user
INSERT INTO users (email, password, role, full_name, phone) VALUES
('admin@monastery.lk', '$2y$10$8K8HxQ9P9M9LvL8N9R9H9e8K8HxQ9P9M9LvL8N9R9H9e8K8HxQ9P9M', 'admin', 'System Administrator', '+94701234567');

-- Insert donation categories
INSERT INTO donation_categories (name, description, target_amount, monthly_target, priority) VALUES
('Food', 'Daily meals and food supplies for monastery', 500000.00, 100000.00, 'high'),
('Medical', 'Healthcare and medical supplies', 200000.00, 50000.00, 'high'),
('Electricity', 'Electricity bills and power supply', 150000.00, 25000.00, 'medium'),
('Water', 'Water supply and purification', 100000.00, 20000.00, 'medium'),
('Maintenance', 'Building and facility maintenance', 300000.00, 50000.00, 'medium'),
('Education', 'Books, teaching materials, and educational resources', 100000.00, 15000.00, 'low');

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type, capacity, floor, building, facilities) VALUES
('A101', 'single', 1, 1, 'Main Building', 'Bed, Desk, Storage, Window'),
('A102', 'single', 1, 1, 'Main Building', 'Bed, Desk, Storage, Window'),
('A103', 'shared', 2, 1, 'Main Building', '2 Beds, 2 Desks, Shared Storage'),
('B201', 'single', 1, 2, 'East Wing', 'Bed, Desk, Storage, Balcony'),
('B202', 'shared', 2, 2, 'East Wing', '2 Beds, 2 Desks, Shared Bathroom'),
('C301', 'dormitory', 4, 3, 'West Wing', '4 Beds, Study Area, Shared Facilities');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_donations_status ON donations(status);
CREATE INDEX idx_donations_date ON donations(donation_date);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_medical_records_monk ON medical_records(monk_id);
CREATE INDEX idx_expenses_category ON expenses(category_id);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_date ON audit_logs(created_at);

-- Create views for common queries
CREATE OR REPLACE VIEW active_monks AS
SELECT 
    u.id, u.full_name, u.email, u.phone,
    m.monk_id, m.age, m.blood_group, m.room_id,
    r.room_number, r.building
FROM users u
JOIN monks m ON u.id = m.user_id
LEFT JOIN rooms r ON m.room_id = r.id
WHERE u.status = 'active' AND m.status = 'active';

CREATE OR REPLACE VIEW active_doctors AS
SELECT 
    u.id, u.full_name, u.email, u.phone,
    d.doctor_id, d.specialization, d.experience_years,
    d.available_days, d.availability_start, d.availability_end
FROM users u
JOIN doctors d ON u.id = d.user_id
WHERE u.status = 'active' AND d.status = 'active';

CREATE OR REPLACE VIEW donation_summary AS
SELECT 
    dc.id, dc.name, dc.description,
    dc.target_amount, dc.current_amount,
    dc.monthly_target,
    ROUND((dc.current_amount / dc.target_amount) * 100, 2) as progress_percentage,
    COUNT(don.id) as total_donations,
    COALESCE(SUM(CASE WHEN don.donation_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN don.amount ELSE 0 END), 0) as monthly_collected
FROM donation_categories dc
LEFT JOIN donations don ON dc.id = don.category_id AND don.status = 'completed'
GROUP BY dc.id;

-- Update current amounts in donation categories (trigger would be better in production)
UPDATE donation_categories dc
SET current_amount = (
    SELECT COALESCE(SUM(d.amount), 0)
    FROM donations d
    WHERE d.category_id = dc.id AND d.status = 'completed'
);

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;