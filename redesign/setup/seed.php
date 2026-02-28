<?php
/**
 * Quick seed script - insert sample users
 */

$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=monastery_system;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute(['admin@monastery.lk']);
    if ($stmt->fetch()) {
        echo "Sample data already exists. Skipping.\n";
        exit;
    }
    
    // Admin
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, full_name, phone, status) VALUES (?, ?, 'admin', 'System Administrator', '0112345678', 'active')");
    $stmt->execute(['admin@monastery.lk', $hash]);
    echo "Admin created.\n";
    
    // Doctor
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, full_name, phone, status) VALUES (?, ?, 'doctor', 'Dr. Perera', '0771234567', 'active')");
    $stmt->execute(['doctor@monastery.lk', $hash]);
    $doctorUserId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO doctors (user_id, doctor_id, specialization, qualifications, experience_years, license_number, available_days, availability_start, availability_end, status) VALUES (?, 'DOC001', 'General Medicine', 'MBBS, MD - Colombo General Hospital', 10, 'SLMC-12345', 'monday,tuesday,wednesday,thursday,friday', '09:00', '17:00', 'active')");
    $stmt->execute([$doctorUserId]);
    echo "Doctor created.\n";
    
    // Monk
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, full_name, phone, status) VALUES (?, ?, 'monk', 'Ven. Ananda Thero', '0712345678', 'active')");
    $stmt->execute(['monk@monastery.lk', $hash]);
    $monkUserId = $pdo->lastInsertId();
    
    // Assign monk to room 1 if exists
    $roomId = null;
    $room = $pdo->query("SELECT id FROM rooms LIMIT 1")->fetch();
    if ($room) $roomId = $room['id'];
    
    $stmt = $pdo->prepare("INSERT INTO monks (user_id, monk_id, ordained_date, age, blood_group, emergency_contact, emergency_phone, room_id, status) VALUES (?, 'MONK001', '2015-05-01', 36, 'O+', 'Family Contact', '0776543210', ?, 'active')");
    $stmt->execute([$monkUserId, $roomId]);
    
    if ($roomId) {
        $pdo->exec("UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = $roomId");
    }
    echo "Monk created.\n";
    
    // Donator
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, full_name, phone, status) VALUES (?, ?, 'donator', 'Nimal Jayawardena', '0761234567', 'active')");
    $stmt->execute(['donor@monastery.lk', $hash]);
    $donorUserId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO donators (user_id, donator_id, address, city) VALUES (?, 'DON-001', '123 Temple Road, Colombo', 'Colombo')");
    $stmt->execute([$donorUserId]);
    echo "Donator created.\n";
    
    // Sample donations
    $catId = $pdo->query("SELECT id FROM donation_categories LIMIT 1")->fetch();
    if ($catId) {
        $donatorRow = $pdo->query("SELECT id FROM donators LIMIT 1")->fetch();
        if ($donatorRow) {
            $stmt = $pdo->prepare("INSERT INTO donations (donation_id, donator_id, category_id, amount, donation_method, donation_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(['DN-2024-0001', $donatorRow['id'], $catId['id'], 5000.00, 'cash', date('Y-m-d'), 'completed']);
            $stmt->execute(['DN-2024-0002', $donatorRow['id'], $catId['id'], 2500.00, 'bank_transfer', date('Y-m-d'), 'pending']);
            
            // Update category amount
            $pdo->exec("UPDATE donation_categories SET current_amount = current_amount + 5000 WHERE id = " . $catId['id']);
            echo "Sample donations created.\n";
            
            // Sample expense
            $stmt = $pdo->prepare("INSERT INTO expenses (expense_id, category_id, amount, description, expense_date, vendor_name, payment_method, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(['EXP-2024-001', $catId['id'], 1500.00, 'Monthly food supplies', date('Y-m-d'), 'Colombo Suppliers', 'cash', 'approved', 1]);
            echo "Sample expense created.\n";
        }
    }
    
    // Sample appointment
    $monkRow = $pdo->query("SELECT id FROM monks LIMIT 1")->fetch();
    $doctorRow = $pdo->query("SELECT id FROM doctors LIMIT 1")->fetch();
    if ($monkRow && $doctorRow) {
        $stmt = $pdo->prepare("INSERT INTO appointments (appointment_id, monk_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['APT-2024-0001', $monkRow['id'], $doctorRow['id'], date('Y-m-d', strtotime('+1 day')), '10:00:00', 'General checkup', 'scheduled']);
        echo "Sample appointment created.\n";
    }
    
    // Sample medical record
    if ($monkRow && $doctorRow) {
        $stmt = $pdo->prepare("INSERT INTO medical_records (record_id, monk_id, doctor_id, visit_date, diagnosis, treatment_notes, prescription, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['MR-2024-0001', $monkRow['id'], $doctorRow['id'], date('Y-m-d'), 'Common cold', 'Rest and hydration', 'Paracetamol 500mg', 'completed']);
        echo "Sample medical record created.\n";
    }
    
    echo "\n=== ALL SAMPLE DATA CREATED ===\n";
    echo "Login credentials (all password: admin123):\n";
    echo "  Admin:   admin@monastery.lk\n";
    echo "  Doctor:  doctor@monastery.lk\n";
    echo "  Monk:    monk@monastery.lk\n";
    echo "  Donator: donor@monastery.lk\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
