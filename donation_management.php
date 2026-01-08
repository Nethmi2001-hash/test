<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monastery_healthcare";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $donor_name = trim($_POST['donor_name']);
        $donor_email = trim($_POST['donor_email']);
        $donor_phone = trim($_POST['donor_phone']);
        $amount = floatval($_POST['amount']);
        $category_id = intval($_POST['category_id']);
        $payment_method = $_POST['payment_method'];
        $reference_number = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'] ?? 'pending';

        if (empty($donor_name) || $amount <= 0 || empty($category_id)) {
            $error = "Donor name, valid amount, and category are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO donations (donor_name, donor_email, donor_phone, amount, category_id, payment_method, reference_number, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param("sssdissssi", $donor_name, $donor_email, $donor_phone, $amount, $category_id, $payment_method, $reference_number, $notes, $status, $created_by);
            
            if ($stmt->execute()) {
                $success = "Donation recorded successfully! Donation ID: " . $stmt->insert_id;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'update') {
        $donation_id = intval($_POST['donation_id']);
        $donor_name = trim($_POST['donor_name']);
        $donor_email = trim($_POST['donor_email']);
        $donor_phone = trim($_POST['donor_phone']);
        $amount = floatval($_POST['amount']);
        $category_id = intval($_POST['category_id']);
        $payment_method = $_POST['payment_method'];
        $reference_number = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE donations SET donor_name=?, donor_email=?, donor_phone=?, amount=?, category_id=?, payment_method=?, reference_number=?, notes=?, status=? WHERE donation_id=?");
        $stmt->bind_param("sssdissssi", $donor_name, $donor_email, $donor_phone, $amount, $category_id, $payment_method, $reference_number, $notes, $status, $donation_id);
        
        if ($stmt->execute()) {
            $success = "Donation updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $donation_id = intval($_POST['donation_id']);
        $stmt = $conn->prepare("DELETE FROM donations WHERE donation_id=?");
        $stmt->bind_param("i", $donation_id);
        
        if ($stmt->execute()) {
            $success = "Donation deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'verify') {
        $donation_id = intval($_POST['donation_id']);
        
        // Get donation details before verification
        $donation_query = $conn->prepare("
            SELECT d.*, c.name as category_name 
            FROM donations d 
            LEFT JOIN categories c ON d.category_id = c.category_id 
            WHERE d.donation_id = ?
        ");
        $donation_query->bind_param("i", $donation_id);
        $donation_query->execute();
        $donation_result = $donation_query->get_result();
        $donation_data = $donation_result->fetch_assoc();
        $donation_query->close();
        
        // Verify donation
        $stmt = $conn->prepare("UPDATE donations SET status='verified', verified_by=?, verified_at=NOW() WHERE donation_id=?");
        $verified_by = $_SESSION['user_id'];
        $stmt->bind_param("ii", $verified_by, $donation_id);
        
        if ($stmt->execute()) {
            $success = "Donation verified successfully!";
            
            // Send thank you email if email is provided
            if (!empty($donation_data['donor_email'])) {
                require_once __DIR__ . '/includes/email_helper.php';
                if (sendDonationThankYou($donation_data)) {
                    $success .= " Thank you email sent to donor!";
                }
            }
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get categories (only donation type)
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories WHERE type='donation' ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get donations with category info
$donations = [];
$result = $conn->query("
    SELECT d.*, c.name as category_name, u.name as created_by_name 
    FROM donations d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    LEFT JOIN users u ON d.created_by = u.user_id 
    ORDER BY d.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donations[] = $row;
    }
}

// Calculate statistics
$stats = [
    'total_donations' => 0,
    'pending_amount' => 0,
    'verified_amount' => 0,
    'this_month' => 0
];

$result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM donations");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_donations'] = $row['count'];
}

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status='pending'");
if ($result) $stats['pending_amount'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status='verified'");
if ($result) $stats['verified_amount'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($result) $stats['this_month'] = $result->fetch_assoc()['total'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation Management - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-light: #ffa726;
            --monastery-dark: #e65100;
            --monastery-pale: #fff3e0;
        }
        body {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        }
        .page-header {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(245, 124, 0, 0.3);
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--monastery-saffron);
            margin-bottom: 20px;
        }
        .stat-card h3 {
            color: var(--monastery-dark);
            font-size: 1.8rem;
            margin: 10px 0 5px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .donation-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            border-left: 4px solid var(--monastery-saffron);
            transition: all 0.2s;
        }
        .donation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(245, 124, 0, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .payment-method-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge.bg-warning {
            background: #ffc107 !important;
        }
        .badge.bg-success {
            background: #28a745 !important;
        }
        .badge.bg-danger {
            background: #dc3545 !important;
        }
        .payhere-section {
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px dashed var(--monastery-saffron);
        }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-cash-coin"></i> Donation Management</h2>
                <p class="mb-0 mt-1 opacity-75">Track and manage monastery donations</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Record Donation
                </button>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="mb-1">Total Donations</p>
                        <h3><?= $stats['total_donations'] ?></h3>
                    </div>
                    <i class="bi bi-gift" style="font-size: 2.5rem; color: var(--monastery-saffron); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="mb-1">Pending Verification</p>
                        <h3>Rs. <?= number_format($stats['pending_amount'], 2) ?></h3>
                    </div>
                    <i class="bi bi-clock-history" style="font-size: 2.5rem; color: #ffc107; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="mb-1">Verified Donations</p>
                        <h3>Rs. <?= number_format($stats['verified_amount'], 2) ?></h3>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #28a745; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="mb-1">This Month</p>
                        <h3>Rs. <?= number_format($stats['this_month'], 2) ?></h3>
                    </div>
                    <i class="bi bi-calendar-check" style="font-size: 2.5rem; color: var(--monastery-saffron); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- PayHere Online Payment Section -->
    <div class="payhere-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="mb-2"><i class="bi bi-credit-card"></i> Accept Online Donations</h5>
                <p class="mb-0 text-muted">
                    <small>Donors can pay using Credit/Debit Cards, Bank Transfer, or Mobile Wallets via PayHere Payment Gateway (Sandbox Mode)</small>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#payhereModal">
                    <i class="bi bi-wallet2"></i> Pay Online (PayHere)
                </button>
            </div>
        </div>
    </div>

    <!-- Donations List -->
    <div class="card shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Donations</h5>
        </div>
        <div class="card-body">
            <?php if (count($donations) > 0): ?>
                <?php foreach ($donations as $donation): ?>
                    <div class="donation-card">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong class="text-primary"><?= htmlspecialchars($donation['donor_name']) ?></strong><br>
                                <small class="text-muted">
                                    <?php if ($donation['donor_email']): ?>
                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($donation['donor_email']) ?><br>
                                    <?php endif; ?>
                                    <?php if ($donation['donor_phone']): ?>
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($donation['donor_phone']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-0" style="color: var(--monastery-dark);">Rs. <?= number_format($donation['amount'], 2) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($donation['category_name']) ?></small>
                            </div>
                            <div class="col-md-2">
                                <?php
                                $method_colors = [
                                    'cash' => 'success',
                                    'bank_transfer' => 'primary',
                                    'card' => 'info',
                                    'payhere' => 'warning'
                                ];
                                $color = $method_colors[$donation['payment_method']] ?? 'secondary';
                                ?>
                                <span class="payment-method-badge bg-<?= $color ?> text-white">
                                    <?= strtoupper(str_replace('_', ' ', $donation['payment_method'])) ?>
                                </span>
                                <?php if ($donation['reference_number']): ?>
                                    <br><small class="text-muted">Ref: <?= htmlspecialchars($donation['reference_number']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <?php
                                $status_colors = [
                                    'pending' => 'warning',
                                    'verified' => 'success',
                                    'rejected' => 'danger'
                                ];
                                $status_color = $status_colors[$donation['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $status_color ?>">
                                    <?= strtoupper($donation['status']) ?>
                                </span><br>
                                <small class="text-muted"><?= date('M d, Y', strtotime($donation['created_at'])) ?></small>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php if ($donation['status'] == 'verified'): ?>
                                    <a href="generate_receipt.php?id=<?= $donation['donation_id'] ?>" class="btn btn-sm btn-success" target="_blank">
                                        <i class="bi bi-file-pdf"></i> Receipt
                                    </a>
                                <?php endif; ?>
                                <?php if ($donation['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="form_name" value="verify">
                                        <input type="hidden" name="donation_id" value="<?= $donation['donation_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Verify this donation?')">
                                            <i class="bi bi-check-circle"></i> Verify
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" onclick="editDonation(<?= htmlspecialchars(json_encode($donation)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="form_name" value="delete">
                                    <input type="hidden" name="donation_id" value="<?= $donation['donation_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this donation?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php if ($donation['notes']): ?>
                            <div class="mt-2">
                                <small><i class="bi bi-sticky"></i> <strong>Notes:</strong> <?= htmlspecialchars($donation['notes']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.3;"></i>
                    <p class="mt-3">No donations recorded yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Donation Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Record New Donation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Name <span class="text-danger">*</span></label>
                            <input type="text" name="donor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Email</label>
                            <input type="email" name="donor_email" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Phone</label>
                            <input type="text" name="donor_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="payhere">PayHere (Online)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Bank ref, transaction ID, etc.">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending Verification</option>
                                <option value="verified">Verified</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Donation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Donation Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Donation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="update">
                    <input type="hidden" name="donation_id" id="edit_donation_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Name <span class="text-danger">*</span></label>
                            <input type="text" name="donor_name" id="edit_donor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Email</label>
                            <input type="email" name="donor_email" id="edit_donor_email" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donor Phone</label>
                            <input type="text" name="donor_phone" id="edit_donor_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="edit_payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="payhere">PayHere (Online)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_number" id="edit_reference_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="pending">Pending Verification</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Donation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- PayHere Payment Modal -->
<div class="modal fade" id="payhereModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #FFB800 0%, #FF8000 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-credit-card"></i> PayHere Online Payment (Sandbox)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <strong>Test Mode:</strong> This is PayHere SANDBOX for testing. No real money will be charged.
                </div>

                <form id="payhereForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" id="ph_donor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Email <span class="text-danger">*</span></label>
                            <input type="email" id="ph_donor_email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" id="ph_donor_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Donation Amount (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" id="ph_amount" class="form-control" step="0.01" min="100" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Donation Category <span class="text-danger">*</span></label>
                        <select id="ph_category" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message/Purpose (Optional)</label>
                        <textarea id="ph_notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Sandbox Test Cards:</strong><br>
                        <small>
                            • Visa: 4111 1111 1111 1111 (CVV: any 3 digits, Expiry: any future date)<br>
                            • MasterCard: 5555 5555 5555 4444<br>
                            • Any name can be used
                        </small>
                    </div>

                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="initiatePayHere()">
                        <i class="bi bi-credit-card"></i> Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editDonation(donation) {
    document.getElementById('edit_donation_id').value = donation.donation_id;
    document.getElementById('edit_donor_name').value = donation.donor_name;
    document.getElementById('edit_donor_email').value = donation.donor_email || '';
    document.getElementById('edit_donor_phone').value = donation.donor_phone || '';
    document.getElementById('edit_amount').value = donation.amount;
    document.getElementById('edit_category_id').value = donation.category_id;
    document.getElementById('edit_payment_method').value = donation.payment_method;
    document.getElementById('edit_reference_number').value = donation.reference_number || '';
    document.getElementById('edit_status').value = donation.status;
    document.getElementById('edit_notes').value = donation.notes || '';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function initiatePayHere() {
    // Get form values
    const donor_name = document.getElementById('ph_donor_name').value;
    const donor_email = document.getElementById('ph_donor_email').value;
    const donor_phone = document.getElementById('ph_donor_phone').value;
    const amount = document.getElementById('ph_amount').value;
    const category_id = document.getElementById('ph_category').value;
    const notes = document.getElementById('ph_notes').value;

    // Validate
    if (!donor_name || !donor_email || !donor_phone || !amount || !category_id) {
        alert('Please fill all required fields');
        return;
    }

    if (amount < 100) {
        alert('Minimum donation amount is Rs. 100');
        return;
    }

    // TODO: In production, implement PayHere SDK integration
    // For now, show placeholder message
    alert('PayHere Integration:\n\n' +
          'This will redirect to PayHere payment gateway.\n\n' +
          'Donor: ' + donor_name + '\n' +
          'Amount: Rs. ' + amount + '\n' +
          'Email: ' + donor_email + '\n\n' +
          'SANDBOX MODE - No real payment will be processed.\n\n' +
          'To complete integration:\n' +
          '1. Get PayHere Merchant ID (sandbox)\n' +
          '2. Include PayHere JS SDK\n' +
          '3. Create payment object\n' +
          '4. Handle success/error callbacks\n' +
          '5. Save to database on success');

    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('payhereModal')).hide();
}
</script>

</body>
</html>
<?php $conn->close(); ?>
