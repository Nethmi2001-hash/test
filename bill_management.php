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
        $description = trim($_POST['description']);
        $amount = floatval($_POST['amount']);
        $category_id = intval($_POST['category_id']);
        $bill_date = $_POST['bill_date'];
        $vendor = trim($_POST['vendor'] ?? '');
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'] ?? 'pending';

        if (empty($description) || $amount <= 0 || empty($category_id)) {
            $error = "Description, valid amount, and category are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO bills (description, amount, category_id, bill_date, vendor, invoice_number, notes, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param("sdisssssi", $description, $amount, $category_id, $bill_date, $vendor, $invoice_number, $notes, $status, $created_by);
            
            if ($stmt->execute()) {
                $success = "Bill/Expense recorded successfully! Bill ID: " . $stmt->insert_id;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'update') {
        $bill_id = intval($_POST['bill_id']);
        $description = trim($_POST['description']);
        $amount = floatval($_POST['amount']);
        $category_id = intval($_POST['category_id']);
        $bill_date = $_POST['bill_date'];
        $vendor = trim($_POST['vendor'] ?? '');
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE bills SET description=?, amount=?, category_id=?, bill_date=?, vendor=?, invoice_number=?, notes=?, status=? WHERE bill_id=?");
        $stmt->bind_param("sdissssi", $description, $amount, $category_id, $bill_date, $vendor, $invoice_number, $notes, $status, $bill_id);
        
        if ($stmt->execute()) {
            $success = "Bill/Expense updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $bill_id = intval($_POST['bill_id']);
        $stmt = $conn->prepare("DELETE FROM bills WHERE bill_id=?");
        $stmt->bind_param("i", $bill_id);
        
        if ($stmt->execute()) {
            $success = "Bill/Expense deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'approve') {
        $bill_id = intval($_POST['bill_id']);
        $stmt = $conn->prepare("UPDATE bills SET status='approved', approved_by=?, approved_at=NOW() WHERE bill_id=?");
        $approved_by = $_SESSION['user_id'];
        $stmt->bind_param("ii", $approved_by, $bill_id);
        
        if ($stmt->execute()) {
            $success = "Bill/Expense approved successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get categories (only bill type)
$categories = [];
$cat_result = $conn->query("SELECT * FROM categories WHERE type='bill' ORDER BY name ASC");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get bills with category info
$bills = [];
$result = $conn->query("
    SELECT b.*, c.name as category_name, u.name as created_by_name 
    FROM bills b 
    LEFT JOIN categories c ON b.category_id = c.category_id 
    LEFT JOIN users u ON b.created_by = u.user_id 
    ORDER BY b.bill_date DESC, b.created_at DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
}

// Calculate statistics
$stats = [
    'total_bills' => 0,
    'pending_amount' => 0,
    'approved_amount' => 0,
    'this_month' => 0
];

$result = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM bills");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_bills'] = $row['count'];
}

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM bills WHERE status='pending'");
if ($result) $stats['pending_amount'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM bills WHERE status='approved'");
if ($result) $stats['approved_amount'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM bills WHERE MONTH(bill_date) = MONTH(CURRENT_DATE()) AND YEAR(bill_date) = YEAR(CURRENT_DATE())");
if ($result) $stats['this_month'] = $result->fetch_assoc()['total'];

// Get category-wise expenses for this month
$category_expenses = [];
$result = $conn->query("
    SELECT c.name, COALESCE(SUM(b.amount), 0) as total 
    FROM categories c 
    LEFT JOIN bills b ON c.category_id = b.category_id AND MONTH(b.bill_date) = MONTH(CURRENT_DATE()) AND YEAR(b.bill_date) = YEAR(CURRENT_DATE())
    WHERE c.type = 'bill'
    GROUP BY c.category_id, c.name
    ORDER BY total DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $category_expenses[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bills & Expenses - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .bill-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            border-left: 4px solid #dc3545;
            transition: all 0.2s;
        }
        .bill-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container mt-4 mb-5">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-receipt"></i> Bills & Expenses Management</h2>
                <p class="mb-0 mt-1 opacity-75">Track monastery expenses and bills</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Expense
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
                        <p class="mb-1">Total Bills</p>
                        <h3><?= $stats['total_bills'] ?></h3>
                    </div>
                    <i class="bi bi-receipt-cutoff" style="font-size: 2.5rem; color: #dc3545; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="mb-1">Pending Approval</p>
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
                        <p class="mb-1">Approved Expenses</p>
                        <h3>Rs. <?= number_format($stats['approved_amount'], 2) ?></h3>
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
                    <i class="bi bi-calendar-month" style="font-size: 2.5rem; color: var(--monastery-saffron); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Category-wise Expenses Chart -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="chart-container">
                <h5 style="color: var(--monastery-dark); margin-bottom: 20px;">
                    <i class="bi bi-pie-chart"></i> Category-wise Expenses (This Month)
                </h5>
                <canvas id="categoryChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Bills List -->
    <div class="card shadow-sm">
        <div class="card-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Bills & Expenses</h5>
        </div>
        <div class="card-body">
            <?php if (count($bills) > 0): ?>
                <?php foreach ($bills as $bill): ?>
                    <div class="bill-card">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <strong class="text-danger"><?= htmlspecialchars($bill['description']) ?></strong><br>
                                <small class="text-muted">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($bill['category_name']) ?>
                                    <?php if ($bill['vendor']): ?>
                                        <br><i class="bi bi-shop"></i> <?= htmlspecialchars($bill['vendor']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-md-2">
                                <h5 class="mb-0 text-danger">Rs. <?= number_format($bill['amount'], 2) ?></h5>
                                <small class="text-muted"><?= date('M d, Y', strtotime($bill['bill_date'])) ?></small>
                            </div>
                            <div class="col-md-2">
                                <?php if ($bill['invoice_number']): ?>
                                    <small class="text-muted">Invoice: <?= htmlspecialchars($bill['invoice_number']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <?php
                                $status_colors = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger'
                                ];
                                $status_color = $status_colors[$bill['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $status_color ?>">
                                    <?= strtoupper($bill['status']) ?>
                                </span>
                            </div>
                            <div class="col-md-2 text-end">
                                <?php if ($bill['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="form_name" value="approve">
                                        <input type="hidden" name="bill_id" value="<?= $bill['bill_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this expense?')">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-primary" onclick="editBill(<?= htmlspecialchars(json_encode($bill)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="form_name" value="delete">
                                    <input type="hidden" name="bill_id" value="<?= $bill['bill_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this expense?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php if ($bill['notes']): ?>
                            <div class="mt-2">
                                <small><i class="bi bi-sticky"></i> <strong>Notes:</strong> <?= htmlspecialchars($bill['notes']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; opacity: 0.3;"></i>
                    <p class="mt-3">No bills/expenses recorded yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Bill Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Bill/Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control" placeholder="e.g., Electricity Bill, Medicine Purchase" required>
                        </div>
                        <div class="col-md-4 mb-3">
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
                            <label class="form-label">Bill Date <span class="text-danger">*</span></label>
                            <input type="date" name="bill_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor/Supplier</label>
                            <input type="text" name="vendor" class="form-control" placeholder="Company/Shop name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control" placeholder="Invoice/Receipt #">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved</option>
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
                        <i class="bi bi-save"></i> Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Bill Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Bill/Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="update">
                    <input type="hidden" name="bill_id" id="edit_bill_id">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" name="description" id="edit_description" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
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
                            <label class="form-label">Bill Date <span class="text-danger">*</span></label>
                            <input type="date" name="bill_date" id="edit_bill_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor/Supplier</label>
                            <input type="text" name="vendor" id="edit_vendor" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" name="invoice_number" id="edit_invoice_number" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="pending">Pending Approval</option>
                                <option value="approved">Approved</option>
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
                        <i class="bi bi-save"></i> Update Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBill(bill) {
    document.getElementById('edit_bill_id').value = bill.bill_id;
    document.getElementById('edit_description').value = bill.description;
    document.getElementById('edit_amount').value = bill.amount;
    document.getElementById('edit_category_id').value = bill.category_id;
    document.getElementById('edit_bill_date').value = bill.bill_date;
    document.getElementById('edit_vendor').value = bill.vendor || '';
    document.getElementById('edit_invoice_number').value = bill.invoice_number || '';
    document.getElementById('edit_status').value = bill.status;
    document.getElementById('edit_notes').value = bill.notes || '';
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Category-wise Expenses Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($category_expenses, 'name')) ?>,
        datasets: [{
            label: 'Expenses (Rs.)',
            data: <?= json_encode(array_column($category_expenses, 'total')) ?>,
            backgroundColor: [
                '#f57c00',
                '#ff9800',
                '#ffa726',
                '#fb8c00',
                '#f57f17'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>
