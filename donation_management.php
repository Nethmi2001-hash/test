<?php
session_start();

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>

<?php include 'navbar.php'; ?>

    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="page-title"><i class="bi bi-cash-coin"></i> Donation Management</h1>
            <p class="page-subtitle">Track and manage monastery donations</p>
        </div>
        <div class="page-header-actions">
            <button class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Record Donation
            </button>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert-modern alert-danger-modern">
            <i class="bi bi-exclamation-triangle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-modern alert-success-modern">
            <i class="bi bi-check-circle"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon emerald">
                    <i class="bi bi-gift"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Total Donations</div>
                    <div class="stat-value"><?= $stats['total_donations'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon amber">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Pending Verification</div>
                    <div class="stat-value">Rs. <?= number_format($stats['pending_amount'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Verified Donations</div>
                    <div class="stat-value">Rs. <?= number_format($stats['verified_amount'], 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">This Month</div>
                    <div class="stat-value">Rs. <?= number_format($stats['this_month'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Donations Table -->
    <div class="modern-table-wrapper">
        <div class="modern-table-header">
            <h5><i class="bi bi-list-ul"></i> Recent Donations</h5>
        </div>

        <!-- Advanced Search Section -->
        <div id="advanced-search" data-type="donations" class="px-3 pt-3"></div>

        <div class="table-responsive-modern">
            <div id="donations-list">
            <?php if (count($donations) > 0): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Donor</th>
                            <th>Amount</th>
                            <th>Receipt</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($donation['donor_name']) ?></strong>
                                <?php if ($donation['donor_email']): ?>
                                    <br><small class="text-muted"><i class="bi bi-envelope"></i> <?= htmlspecialchars($donation['donor_email']) ?></small>
                                <?php endif; ?>
                                <?php if ($donation['donor_phone']): ?>
                                    <br><small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($donation['donor_phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>Rs. <?= number_format($donation['amount'], 2) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($donation['category_name']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($donation['slip_path'])): ?>
                                    <a href="#" class="btn-modern btn-primary-modern btn-sm-modern" onclick="viewSlip('<?= htmlspecialchars($donation['slip_path']) ?>', '<?= htmlspecialchars($donation['donor_name']) ?>')" title="View Uploaded Bank Slip">
                                        <i class="bi bi-image"></i> View Slip
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-dash-circle"></i> No Slip</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'badge-warning',
                                    'verified' => 'badge-success',
                                    'rejected' => 'badge-danger'
                                ];
                                $s_badge = $status_badges[$donation['status']] ?? 'badge-neutral';
                                ?>
                                <span class="badge-modern badge-dot <?= $s_badge ?>">
                                    <?= ucfirst($donation['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($donation['created_at'])) ?>
                                <?php if ($donation['notes']): ?>
                                    <br><small class="text-muted"><i class="bi bi-sticky"></i> <?= htmlspecialchars($donation['notes']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ($donation['status'] == 'verified'): ?>
                                        <a href="generate_receipt.php?id=<?= $donation['donation_id'] ?>" class="btn-icon" target="_blank" title="Receipt">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($donation['status'] == 'pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="form_name" value="verify">
                                            <input type="hidden" name="donation_id" value="<?= $donation['donation_id'] ?>">
                                            <button type="submit" class="btn-icon" onclick="return confirm('Verify this donation?')" title="Verify">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn-icon" disabled style="opacity:0.4;cursor:not-allowed;" title="Already Verified">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p class="mt-3">No donations recorded yet</p>
                </div>
            <?php endif; ?>
            </div><!-- END donations-list -->
        </div>
    </div>

<!-- Add Donation Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Record New Donation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Name <span class="required">*</span></label>
                                <input type="text" name="donor_name" class="form-control-modern" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Email</label>
                                <input type="email" name="donor_email" class="form-control-modern">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Phone</label>
                                <input type="text" name="donor_phone" class="form-control-modern">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Amount (Rs.) <span class="required">*</span></label>
                                <input type="number" name="amount" class="form-control-modern" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Category <span class="required">*</span></label>
                                <select name="category_id" class="form-select-modern" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Payment Method <span class="required">*</span></label>
                                <select name="payment_method" class="form-select-modern" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="card">Card</option>
                                    <option value="payhere">PayHere (Online)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control-modern" placeholder="Bank ref, transaction ID, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Status</label>
                                <select name="status" class="form-select-modern">
                                    <option value="pending">Pending Verification</option>
                                    <option value="verified">Verified</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Notes</label>
                        <textarea name="notes" class="form-control-modern" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-modern btn-primary-modern">
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Donation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="update">
                    <input type="hidden" name="donation_id" id="edit_donation_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Name <span class="required">*</span></label>
                                <input type="text" name="donor_name" id="edit_donor_name" class="form-control-modern" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Email</label>
                                <input type="email" name="donor_email" id="edit_donor_email" class="form-control-modern">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donor Phone</label>
                                <input type="text" name="donor_phone" id="edit_donor_phone" class="form-control-modern">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Amount (Rs.) <span class="required">*</span></label>
                                <input type="number" name="amount" id="edit_amount" class="form-control-modern" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Category <span class="required">*</span></label>
                                <select name="category_id" id="edit_category_id" class="form-select-modern" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Payment Method <span class="required">*</span></label>
                                <select name="payment_method" id="edit_payment_method" class="form-select-modern" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="card">Card</option>
                                    <option value="payhere">PayHere (Online)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Reference Number</label>
                                <input type="text" name="reference_number" id="edit_reference_number" class="form-control-modern">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Status</label>
                                <select name="status" id="edit_status" class="form-select-modern">
                                    <option value="pending">Pending Verification</option>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control-modern" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-modern btn-primary-modern">
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
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-credit-card"></i> PayHere Online Payment (Sandbox)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert-modern alert-warning-modern mb-3">
                    <i class="bi bi-info-circle"></i>
                    <span><strong>Test Mode:</strong> This is PayHere SANDBOX for testing. No real money will be charged.</span>
                </div>

                <form id="payhereForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Your Name <span class="required">*</span></label>
                                <input type="text" id="ph_donor_name" class="form-control-modern" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Your Email <span class="required">*</span></label>
                                <input type="email" id="ph_donor_email" class="form-control-modern" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Phone Number <span class="required">*</span></label>
                                <input type="text" id="ph_donor_phone" class="form-control-modern" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Donation Amount (Rs.) <span class="required">*</span></label>
                                <input type="number" id="ph_amount" class="form-control-modern" step="0.01" min="100" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Donation Category <span class="required">*</span></label>
                        <select id="ph_category" class="form-select-modern" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Message/Purpose (Optional)</label>
                        <textarea id="ph_notes" class="form-control-modern" rows="2"></textarea>
                    </div>

                    <div class="alert-modern alert-warning-modern mb-3">
                        <i class="bi bi-credit-card"></i>
                        <div>
                            <strong>Sandbox Test Cards:</strong><br>
                            <small>
                                &bull; Visa: 4111 1111 1111 1111 (CVV: any 3 digits, Expiry: any future date)<br>
                                &bull; MasterCard: 5555 5555 5555 4444<br>
                                &bull; Any name can be used
                            </small>
                        </div>
                    </div>

                    <button type="button" class="btn-modern btn-primary-modern btn-lg-modern w-100" onclick="initiatePayHere()">
                        <i class="bi bi-credit-card"></i> Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Bank Slip Viewer Modal -->
<div class="modal fade" id="slipViewerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#c2410c,#f97316);color:#fff;border:none;">
                <h5 class="modal-title"><i class="bi bi-image"></i> <span id="slipModalTitle">Bank Slip</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4" style="background:#f8fafc;">
                <img id="slipImage" src="" alt="Bank Slip" style="max-width:100%;max-height:70vh;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);cursor:zoom-in;" onclick="window.open(this.src,'_blank')">
                <p class="text-muted mt-3 mb-0"><small><i class="bi bi-zoom-in"></i> Click image to open full size in new tab</small></p>
            </div>
            <div class="modal-footer" style="border:none;justify-content:center;">
                <a id="slipDownloadLink" href="#" download class="btn-modern btn-primary-modern btn-sm-modern">
                    <i class="bi bi-download"></i> Download Slip
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

// Initialize Advanced Search for Donations
window.addEventListener('load', function() {
    new AdvancedSearch('donations');
});

function viewSlip(slipPath, donorName) {
    document.getElementById('slipImage').src = slipPath;
    document.getElementById('slipDownloadLink').href = slipPath;
    document.getElementById('slipModalTitle').textContent = 'Bank Slip - ' + donorName;
    new bootstrap.Modal(document.getElementById('slipViewerModal')).show();
}
</script>

<!-- Advanced Search System -->
<script src="assets/js/advanced-search.js"></script>

</body>
</html>
<?php $conn->close(); ?>
