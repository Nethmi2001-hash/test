<?php
/**
 * Process Public Donation - Bank Transfer / Slip Upload
 * Handles form submission from public_donate.php
 */
require_once __DIR__ . '/includes/db_config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: public_donate.php');
    exit;
}

$conn = getDBConnection();

// Collect and sanitize form data
$donor_name = trim($_POST['donor_name'] ?? '');
$donor_email = trim($_POST['donor_email'] ?? '');
$donor_phone = trim($_POST['donor_phone'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$bank_reference = trim($_POST['bank_reference'] ?? '');

// Validate required fields
$errors = [];
if (empty($donor_name)) $errors[] = 'Donor name is required';
if (empty($donor_email) || !filter_var($donor_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($donor_phone)) $errors[] = 'Phone number is required';
if ($amount < 100) $errors[] = 'Minimum donation is Rs. 100';
if ($category_id <= 0) $errors[] = 'Please select a donation category';

// Validate bank slip upload
$slip_path = null;
if (empty($_FILES['bank_slip']['name']) || $_FILES['bank_slip']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Bank slip upload is required';
} else {
    $file = $_FILES['bank_slip'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = 'Invalid file type. Please upload JPG, PNG, GIF, WebP, or PDF';
    }
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds 5MB limit';
    }
}

// If validation errors, redirect back
if (!empty($errors)) {
    $error_msg = implode('. ', $errors);
    header('Location: public_donate.php?error=' . urlencode($error_msg) . '#donate');
    exit;
}

// Create uploads directory if not exists
$upload_dir = __DIR__ . '/uploads/bank_slips/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$file_ext = strtolower(pathinfo($_FILES['bank_slip']['name'], PATHINFO_EXTENSION));
$unique_name = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
$destination = $upload_dir . $unique_name;

// Move uploaded file
if (!move_uploaded_file($_FILES['bank_slip']['tmp_name'], $destination)) {
    header('Location: public_donate.php?error=' . urlencode('Failed to upload file. Please try again.') . '#donate');
    exit;
}

$slip_path = 'uploads/bank_slips/' . $unique_name;

// Check if donor is a registered user (by email)
$donor_user_id = null;
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->bind_param("s", $donor_email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $donor_user_id = $row['user_id'];
}
$stmt->close();

// Insert donation record
$method = 'bank';
$status = 'pending';
$stmt = $conn->prepare("INSERT INTO donations (
    donor_user_id, donor_name, donor_email, donor_phone, 
    amount, currency, category_id, method, 
    bank_reference, slip_path, 
    status, notes, created_at
) VALUES (?, ?, ?, ?, ?, 'LKR', ?, ?, ?, ?, ?, ?, NOW())");

$stmt->bind_param(
    "isssdisssss",
    $donor_user_id, $donor_name, $donor_email, $donor_phone,
    $amount, $category_id, $method,
    $bank_reference, $slip_path,
    $status, $notes
);

if ($stmt->execute()) {
    $donation_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    
    // Redirect with success
    header('Location: public_donate.php?success=1&ref=' . $donation_id . '#donate');
    exit;
} else {
    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    
    // Clean up uploaded file on failure
    if (file_exists($destination)) {
        unlink($destination);
    }
    
    header('Location: public_donate.php?error=' . urlencode('Failed to save donation. Please try again.') . '#donate');
    exit;
}
