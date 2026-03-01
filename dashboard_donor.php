<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['username'] ?? 'Donor';
$userEmail = $_SESSION['email'] ?? '';
$userRole = $_SESSION['role_name'] ?? 'Donor';

// Debug: Log current session info
error_log("Dashboard Donor - User ID: $userId, Email: $userEmail, Role: $userRole");

// Get donor's donation stats with better filtering
$stats = [
    'total_donated' => 0,
    'donation_count' => 0,
    'this_month' => 0,
    'verified_count' => 0
];

// Use both donor_user_id and donor_email for filtering to ensure we get only this user's data
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status IN ('paid', 'verified')");
$stmt->bind_param("is", $userId, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_donated'] = $row['total'];
    $stats['donation_count'] = $row['cnt'];
}

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status IN ('paid', 'verified') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stmt->bind_param("is", $userId, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) $stats['this_month'] = $result->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as c FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status = 'verified'");
$stmt->bind_param("is", $userId, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) $stats['verified_count'] = $result->fetch_assoc()['c'];

// Recent donations
$recent_donations = [];
$stmt = $conn->prepare("
    SELECT d.*, c.name as category_name 
    FROM donations d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    WHERE (d.donor_user_id = ? OR d.donor_email = ?) 
    ORDER BY d.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("is", $userId, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) while ($row = $result->fetch_assoc()) $recent_donations[] = $row;

// Donations by category
$by_category = [];
$stmt = $conn->prepare("
    SELECT c.name as category_name, COALESCE(SUM(d.amount), 0) as total 
    FROM donations d 
    JOIN categories c ON d.category_id = c.category_id 
    WHERE (d.donor_user_id = ? OR d.donor_email = ?) AND d.status IN ('paid', 'verified')
    GROUP BY c.category_id, c.name
    ORDER BY total DESC
");
$stmt->bind_param("is", $userId, $userEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) while ($row = $result->fetch_assoc()) $by_category[] = $row;

// Monthly donation trend (last 6 months)
$monthly_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $trendStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status IN ('paid', 'verified')");
    $trendStmt->bind_param("iss", $userId, $userEmail, $month);
    $trendStmt->execute();
    $trendResult = $trendStmt->get_result();
    $monthlyAmount = 0;
    if ($trendResult) {
        $trendRow = $trendResult->fetch_assoc();
        $monthlyAmount = $trendRow['total'] ?? 0;
    }
    $monthly_trend[] = [
        'month' => date('M', strtotime($month)),
        'amount' => (float)$monthlyAmount
    ];
    $trendStmt->close();
}

// Overall monastery transparency data
$total_monastery_donations = 0;
$total_monastery_expenses = 0;
$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as t FROM donations WHERE status IN ('paid', 'verified')");
$stmt->execute();
$result = $stmt->get_result();
if ($result) $total_monastery_donations = $result->fetch_assoc()['t'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as t FROM bills");
$stmt->execute();
$result = $stmt->get_result();
if ($result) $total_monastery_expenses = $result->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Seela Suwa Herath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            overflow-x: hidden;
        }
        .container-fluid {
            max-width: 100%;
            height: auto;
        }
        .chart-card {
            max-height: 400px;
            overflow: hidden;
        }
        .chart-card canvas {
            max-height: 300px !important;
            width: 100% !important;
        }
        .welcome-card {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Welcome -->
    <div class="welcome-card animate-fade-in">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h2>
                    <?php if ($userRole === 'Donor'): ?>
                        <i class="bi bi-suit-heart me-2"></i>Welcome, <?= htmlspecialchars($userName) ?>!
                    <?php elseif ($userRole === 'Doctor'): ?>
                        <i class="bi bi-heart-pulse me-2"></i>Welcome, Dr. <?= htmlspecialchars($userName) ?>!
                    <?php else: ?>
                        <i class="bi bi-person-heart me-2"></i>Welcome, <?= htmlspecialchars($userName) ?>!
                    <?php endif; ?>
                </h2>
                <p style="margin:0;">
                    <?php if ($userRole === 'Donor'): ?>
                        Thank you for your generous contributions to Seela Suwa Herath Bikshu Gilan Arana.
                    <?php elseif ($userRole === 'Doctor'): ?>
                        View your donation history and support the monastery healthcare mission.
                    <?php else: ?>
                        Support Seela Suwa Herath Bikshu Gilan Arana through your donations.
                    <?php endif; ?>
                </p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <div class="welcome-date">
                    <i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4 stagger-children">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #f97316;">
                <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <div class="stat-label">My Total Donations</div>
                    <div class="stat-value">Rs.<?= number_format($stats['total_donated']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #0284c7;">
                <div class="stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-receipt"></i></div>
                <div class="stat-info">
                    <div class="stat-label">My Donation Count</div>
                    <div class="stat-value"><?= $stats['donation_count'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #7c3aed;">
                <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-calendar-month"></i></div>
                <div class="stat-info">
                    <div class="stat-label">This Month</div>
                    <div class="stat-value">Rs.<?= number_format($stats['this_month']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #d97706;">
                <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="bi bi-patch-check"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Verified Donations</div>
                    <div class="stat-value"><?= $stats['verified_count'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="chart-card animate-fade-in" style="height:100%;">
                <div class="chart-header">
                    <h6><i class="bi bi-graph-up me-2"></i>My Donation Trend</h6>
                    <span class="badge-modern badge-neutral">Last 6 months</span>
                </div>
                <?php if (count($monthly_trend) > 0 && array_sum(array_column($monthly_trend, 'amount')) > 0): ?>
                    <canvas id="trendChart" height="160"></canvas>
                <?php else: ?>
                    <div class="empty-state" style="padding:48px 16px;">
                        <i class="bi bi-graph-up" style="font-size:36px;color:var(--primary-400);"></i>
                        <h5 style="font-size:16px;margin-top:16px;">No donation history yet</h5>
                        <p style="font-size:13px;color:var(--text-secondary);">Start donating to see your contribution trend</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card animate-fade-in" style="height:100%;">
                <div class="chart-header">
                    <h6><i class="bi bi-pie-chart me-2"></i>My Donations by Category</h6>
                </div>
                <?php if (count($by_category) > 0): ?>
                    <canvas id="categoryChart" height="200"></canvas>
                <?php else: ?>
                    <div class="empty-state" style="padding:48px 16px;">
                        <i class="bi bi-pie-chart" style="font-size:36px;color:var(--text-muted);"></i>
                        <p style="margin-top:8px;font-size:13px;color:var(--text-secondary);">No category data yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Transparency Card -->
    <div class="modern-card mb-4 animate-fade-in">
        <div class="card-header-modern">
            <h6><i class="bi bi-shield-check me-2"></i>Monastery Transparency</h6>
            <a href="public_transparency.php" class="btn btn-sm" style="background:var(--primary-100);color:var(--primary-700);font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px;">
                View Full Report <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body-modern" style="padding:24px;">
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div style="font-size:28px;font-weight:800;color:var(--primary-600);">Rs.<?= number_format($total_monastery_donations) ?></div>
                    <div style="font-size:13px;color:var(--text-secondary);margin-top:4px;">Total Donations Received</div>
                </div>
                <div class="col-md-4 text-center">
                    <div style="font-size:28px;font-weight:800;color:#d97706;">Rs.<?= number_format($total_monastery_expenses) ?></div>
                    <div style="font-size:13px;color:var(--text-secondary);margin-top:4px;">Total Expenses</div>
                </div>
                <div class="col-md-4 text-center">
                    <?php $balance = $total_monastery_donations - $total_monastery_expenses; ?>
                    <div style="font-size:28px;font-weight:800;color:<?= $balance >= 0 ? '#f97316' : '#dc2626' ?>;">Rs.<?= number_format(abs($balance)) ?></div>
                    <div style="font-size:13px;color:var(--text-secondary);margin-top:4px;"><?= $balance >= 0 ? 'Balance' : 'Deficit' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Donations -->
    <div class="modern-card mb-4 animate-fade-in">
        <div class="card-header-modern">
            <h6><i class="bi bi-clock-history me-2"></i>My Recent Donations</h6>
            <?php if (count($recent_donations) > 0): ?>
                <span class="badge-modern badge-neutral"><?= count($recent_donations) ?> recent</span>
            <?php endif; ?>
        </div>
        <div class="card-body-modern" style="padding:0;">
            <?php if (count($recent_donations) > 0): ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_donations as $don): ?>
                        <tr>
                            <td style="font-weight:600;"><?= $don['donation_id'] ?></td>
                            <td><?= date('M j, Y', strtotime($don['created_at'])) ?></td>
                            <td><span class="badge-modern badge-neutral"><?= htmlspecialchars($don['category_name'] ?? 'N/A') ?></span></td>
                            <td>
                                <?php
                                    $method_icons = ['cash' => 'bi-cash', 'bank' => 'bi-bank', 'card_sandbox' => 'bi-credit-card'];
                                    $icon = $method_icons[$don['method']] ?? 'bi-wallet2';
                                ?>
                                <i class="bi <?= $icon ?> me-1"></i><?= ucfirst(str_replace('_', ' ', $don['method'])) ?>
                            </td>
                            <td style="font-weight:700;color:var(--primary-600);">Rs.<?= number_format($don['amount'], 2) ?></td>
                            <td>
                                <?php
                                    $status_map = [
                                        'pending' => 'badge-warning',
                                        'paid' => 'badge-success',
                                        'verified' => 'badge-primary',
                                        'failed' => 'badge-danger',
                                        'cancelled' => 'badge-neutral'
                                    ];
                                    $badge = $status_map[$don['status']] ?? 'badge-neutral';
                                ?>
                                <span class="badge-modern <?= $badge ?> badge-dot"><?= ucfirst($don['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($don['status'] === 'verified' || $don['status'] === 'paid'): ?>
                                    <a href="generate_receipt.php?id=<?= $don['donation_id'] ?>" target="_blank" class="btn btn-sm" style="background:var(--primary-100);color:var(--primary-700);font-size:11px;padding:4px 10px;border-radius:6px;">
                                        <i class="bi bi-download me-1"></i>PDF
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty-state" style="padding:48px 16px;">
                    <i class="bi bi-gift" style="font-size:48px;color:var(--primary-400);"></i>
                    <h5 style="font-size:16px;margin-top:16px;">No donations yet</h5>
                    <p style="font-size:13px;color:var(--text-secondary);">Make your first donation to support the monastery</p>
                    <a href="public_donate.php" class="btn btn-sm" style="background:var(--primary-500);color:#fff;padding:8px 20px;border-radius:8px;font-weight:600;margin-top:8px;">
                        <i class="bi bi-heart me-1"></i>Donate Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4 stagger-children">
        <div class="col-xl-3 col-md-6">
            <a href="public_donate.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-heart"></i></div>
                <span class="quick-action-label">Make Donation</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="donation_management.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-list-ul"></i></div>
                <span class="quick-action-label">All Donations</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="public_transparency.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-shield-check"></i></div>
                <span class="quick-action-label">Transparency</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="chatbot.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#cffafe;color:#0891b2;"><i class="bi bi-robot"></i></div>
                <span class="quick-action-label">AI Assistant</span>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Add error boundary
    window.addEventListener('error', function(e) {
        console.error('JavaScript error:', e.error);
        return true; // Prevent default browser error handling
    });
    
    // Trend Chart
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        const trendCtx = trendCanvas.getContext('2d');
        
        // Destroy existing chart if it exists
        if (window.trendChart instanceof Chart) {
            window.trendChart.destroy();
        }
        
        const trendLabels = <?= json_encode(array_slice(array_column($monthly_trend, 'month'), 0, 6), JSON_NUMERIC_CHECK) ?>;
        const trendData = <?= json_encode(array_slice(array_column($monthly_trend, 'amount'), 0, 6), JSON_NUMERIC_CHECK) ?>;
        
        // Validate data
        if (!Array.isArray(trendLabels) || !Array.isArray(trendData) || trendLabels.length === 0) {
            console.warn('Invalid trend data');
            return;
        }
        
        try {
            window.trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Donations (Rs.)',
                        data: trendData,
                        backgroundColor: 'rgba(249, 115, 22, 0.08)',
                        borderColor: '#f97316',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#f97316',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000
                    },
                    plugins: { 
                        legend: { display: false } 
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(0,0,0,0.04)' }, 
                            ticks: { callback: function(value) { return 'Rs.' + value.toLocaleString(); } }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating trend chart:', error);
        }
    }

    <?php if (count($by_category) > 0): ?>
    // Category Chart
    const catCanvas = document.getElementById('categoryChart');
    if (catCanvas) {
        const catCtx = catCanvas.getContext('2d');
        
        // Destroy existing chart if it exists
        if (window.categoryChart instanceof Chart) {
            window.categoryChart.destroy();
        }
        
        const catLabels = <?= json_encode(array_slice(array_column($by_category, 'category_name'), 0, 10)) ?>;
        const catData = <?= json_encode(array_slice(array_column($by_category, 'total'), 0, 10), JSON_NUMERIC_CHECK) ?>;
        const catColors = ['#f97316', '#0284c7', '#7c3aed', '#d97706', '#dc2626', '#0891b2'];
        
        // Validate data
        if (!Array.isArray(catLabels) || !Array.isArray(catData) || catLabels.length === 0) {
            console.warn('Invalid category data');
            return;
        }
        
        try {
            window.categoryChart = new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catData,
                        backgroundColor: catColors.slice(0, catLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000
                    },
                    plugins: {
                        legend: { 
                            position: 'bottom', 
                            labels: { 
                                font: { size: 12, family: 'Inter' }, 
                                padding: 16, 
                                boxWidth: 12 
                            } 
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating category chart:', error);
        }
    }
    <?php endif; ?>
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
