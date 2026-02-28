<?php
require_once __DIR__ . '/../includes/db_config.php';
$conn = getDBConnection();

// Get donor's donation history
$donor_email = $_SESSION['email'];

// Get donor statistics
$stats = [
    'total_donations' => 0,
    'total_amount' => 0,
    'this_year_amount' => 0,
    'last_donation' => 'Never'
];

// Get donor statistics
$result = $conn->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM donations WHERE donor_email = ? AND status IN ('verified', 'paid')");
$result->bind_param("s", $donor_email);
$result->execute();
$donor_stats = $result->get_result()->fetch_assoc();
$stats['total_donations'] = $donor_stats['count'] ?? 0;
$stats['total_amount'] = $donor_stats['total'] ?? 0;

// This year donations
$result = $conn->prepare("SELECT SUM(amount) as total FROM donations WHERE donor_email = ? AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status IN ('verified', 'paid')");
$result->bind_param("s", $donor_email);
$result->execute();
$stats['this_year_amount'] = $result->get_result()->fetch_assoc()['total'] ?? 0;

// Last donation date
$result = $conn->prepare("SELECT MAX(created_at) as last_date FROM donations WHERE donor_email = ? AND status IN ('verified', 'paid')");
$result->bind_param("s", $donor_email);
$result->execute();
$last_donation = $result->get_result()->fetch_assoc()['last_date'];
if ($last_donation) {
    $stats['last_donation'] = date('M d, Y', strtotime($last_donation));
}

// Get recent donations
$recent_donations = [];
$result = $conn->prepare("
    SELECT d.*, c.name as category_name 
    FROM donations d
    JOIN categories c ON d.category_id = c.category_id
    WHERE d.donor_email = ?
    ORDER BY d.created_at DESC
    LIMIT 10
");
$result->bind_param("s", $donor_email);
$result->execute();
$recent_donations = $result->get_result()->fetch_all(MYSQLI_ASSOC);

// Get donation categories with targets
$categories = $conn->query("
    SELECT c.*, 
           COALESCE(SUM(d.amount), 0) as collected,
           (c.target_amount - COALESCE(SUM(d.amount), 0)) as remaining
    FROM categories c
    LEFT JOIN donations d ON c.category_id = d.category_id AND d.status IN ('verified', 'paid')
    GROUP BY c.category_id
    ORDER BY c.name
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/monastery-theme.css">
    <style>
        .donor-header {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .donation-card {
            border-left: 4px solid var(--bs-success);
            transition: transform 0.3s ease;
        }
        .donation-card:hover {
            transform: translateY(-2px);
        }
        .category-progress {
            height: 8px;
            border-radius: 10px;
        }
        .impact-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Donor Header -->
    <div class="donor-header text-center">
        <h1><i class="bi bi-heart-fill"></i> My Donations Dashboard</h1>
        <p class="lead mb-0"><?= htmlspecialchars($_SESSION['username']) ?></p>
        <p class="mb-0">Thank you for your generous support</p>
    </div>

    <!-- Donation Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-gift text-success fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_donations'] ?></h3>
                    <p class="text-muted mb-0">Total Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-currency-dollar text-primary fs-1"></i>
                    <h3 class="mt-2">Rs. <?= number_format($stats['total_amount']) ?></h3>
                    <p class="text-muted mb-0">Total Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-year text-info fs-1"></i>
                    <h3 class="mt-2">Rs. <?= number_format($stats['this_year_amount']) ?></h3>
                    <p class="text-muted mb-0">This Year</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history text-warning fs-1"></i>
                    <h3 class="mt-2 fs-6"><?= $stats['last_donation'] ?></h3>
                    <p class="text-muted mb-0">Last Donation</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-lightning-fill"></i> Donation Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="public_donate.php" class="btn btn-success">
                            <i class="bi bi-heart"></i> Make New Donation
                        </a>
                        <a href="donation_management.php" class="btn btn-outline-primary">
                            <i class="bi bi-list-check"></i> View All Donations
                        </a>
                        <a href="public_transparency.php" class="btn btn-outline-info">
                            <i class="bi bi-graph-up"></i> Impact Report
                        </a>
                    </div>
                </div>
            </div>

            <!-- Impact Summary -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="bi bi-heart-pulse text-danger"></i> Your Impact</h6>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-6">
                            <i class="bi bi-people impact-icon text-primary"></i>
                            <p class="small mb-0">Helping Monks</p>
                        </div>
                        <div class="col-6">
                            <i class="bi bi-hospital impact-icon text-success"></i>
                            <p class="small mb-0">Healthcare Support</p>
                        </div>
                    </div>
                    <small class="text-muted">Your donations contribute to the wellbeing of our monastic community</small>
                </div>
            </div>
        </div>

        <!-- Donation Categories -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-pie-chart"></i> Donation Categories</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($categories as $category): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold"><?= htmlspecialchars($category['name']) ?></small>
                            <small class="text-muted"><?= number_format(($category['collected']/$category['target_amount'])*100, 1) ?>%</small>
                        </div>
                        <div class="progress category-progress mb-1">
                            <div class="progress-bar bg-success" style="width: <?= min(100, ($category['collected']/$category['target_amount'])*100) ?>%"></div>
                        </div>
                        <small class="text-muted">
                            Rs. <?= number_format($category['collected']) ?> / Rs. <?= number_format($category['target_amount']) ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Donations -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Recent Donations</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recent_donations)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-gift fs-3 mb-2"></i>
                            <p class="mb-0">No donations yet</p>
                            <a href="public_donate.php" class="btn btn-sm btn-success mt-2">Make First Donation</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_donations as $donation): ?>
                        <div class="card donation-card mb-2">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($donation['category_name']) ?></h6>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($donation['created_at'])) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-success">Rs. <?= number_format($donation['amount']) ?></span><br>
                                        <span class="badge bg-<?= $donation['status'] == 'verified' ? 'success' : ($donation['status'] == 'paid' ? 'success' : 'warning') ?>">
                                            <?= ucfirst($donation['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>