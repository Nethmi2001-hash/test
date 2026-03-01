<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

$userId = (int)($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['username'] ?? 'Donor';
$userEmail = $_SESSION['email'] ?? '';
$userRole = $_SESSION['role_name'] ?? 'Donor';

// If session lacks user data, reload it from DB using the logged-in user's ID
if (($userId <= 0 && empty($userEmail)) || ($userId <= 0 && !empty($userEmail))) {
    // Try looking up by email first
    if (!empty($userEmail)) {
        $fallbackStmt = $conn->prepare("SELECT u.user_id, u.name, u.email FROM users u WHERE u.email = ? LIMIT 1");
        $fallbackStmt->bind_param("s", $userEmail);
        $fallbackStmt->execute();
        $fallbackResult = $fallbackStmt->get_result();
        if ($fallbackResult && $fallbackRow = $fallbackResult->fetch_assoc()) {
            $userId = (int)$fallbackRow['user_id'];
            $userEmail = $fallbackRow['email'];
            $userName = $fallbackRow['name'];
        }
        $fallbackStmt->close();
    }
}

// If still no user, try to get any Donor from roles join
if ($userId <= 0 && empty($userEmail)) {
    $fallbackStmt = $conn->prepare("SELECT u.user_id, u.name, u.email FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'Donor' AND u.status = 'active' LIMIT 1");
    $fallbackStmt->execute();
    $fallbackResult = $fallbackStmt->get_result();
    if ($fallbackResult && $fallbackRow = $fallbackResult->fetch_assoc()) {
        $userId = (int)$fallbackRow['user_id'];
        $userEmail = $fallbackRow['email'];
        $userName = $fallbackRow['name'];
    }
    $fallbackStmt->close();
}

// Debug: Log current session info
error_log("Dashboard Donor - User ID: $userId, Email: $userEmail, Role: $userRole");

// Get donor's donation stats with better filtering - use both ID and email for maximum compatibility
$stats = [
    'total_donated' => 0,
    'donation_count' => 0,
    'this_month' => 0,
    'verified_count' => 0
];

// Debug: Check what we're filtering by
error_log("Filtering by - User ID: $userId, Email: $userEmail");

// Simplified query - use either user ID or email, whichever is available
if ($userId > 0 || !empty($userEmail)) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status IN ('paid', 'verified', 'pending')");
    $stmt->bind_param("is", $userId, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_donated'] = $row['total'];
        $stats['donation_count'] = $row['cnt'];
        error_log("Stats query result - Total: {$row['total']}, Count: {$row['cnt']}");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status IN ('paid', 'verified', 'pending') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->bind_param("is", $userId, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['this_month'] = $result->fetch_assoc()['total'];
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND status = 'verified'");
    $stmt->bind_param("is", $userId, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats['verified_count'] = $result->fetch_assoc()['c'];
    }
    $stmt->close();
} else {
    error_log("No valid user ID or email for donation queries");
}

// Recent donations
$recent_donations = [];
if ($userId > 0 || !empty($userEmail)) {
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
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_donations[] = $row;
        }
    }
    $stmt->close();
}

// Monthly donation trend (last 6 months)
$monthly_trend = [];
if ($userId > 0 || !empty($userEmail)) {
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $trendStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE (donor_user_id = ? OR donor_email = ?) AND DATE_FORMAT(created_at, '%Y-%m') = ? AND status IN ('paid', 'verified', 'pending')");
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
}

// Donations by category
$by_category = [];
if ($userId > 0 || !empty($userEmail)) {
    $stmt = $conn->prepare("
        SELECT c.name as category_name, COALESCE(SUM(d.amount), 0) as total 
        FROM donations d 
        JOIN categories c ON d.category_id = c.category_id 
        WHERE (d.donor_user_id = ? OR d.donor_email = ?) AND d.status IN ('paid', 'verified', 'pending')
        GROUP BY c.category_id, c.name
        ORDER BY total DESC
    ");
    $stmt->bind_param("is", $userId, $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $by_category[] = $row;
        }
    }
    $stmt->close();
}

// Debug output (remove in production)
error_log("User ID: " . $userId . ", Email: " . $userEmail);
error_log("Monthly Trend: " . json_encode($monthly_trend));
error_log("By Category: " . json_encode($by_category));

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
                <?php 
                // Debug output 
                echo "<!-- Monthly trend count: " . count($monthly_trend) . ", sum: " . array_sum(array_column($monthly_trend, 'amount')) . " -->\n";
                ?>
                <canvas id="trendChart" height="160"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card animate-fade-in" style="height:100%;">
                <div class="chart-header">
                    <h6><i class="bi bi-pie-chart me-2"></i>My Donations by Category</h6>
                </div>
                <?php 
                // Debug output 
                echo "<!-- Category count: " . count($by_category) . " -->\n";
                ?>
                <canvas id="categoryChart" height="200"></canvas>
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
                            <th>Date</th>
                            <th>Category</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_donations as $don): ?>
                        <tr>
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
        
        console.log('Trend data:', {labels: trendLabels, data: trendData});
        
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
        
        console.log('Category data:', {labels: catLabels, data: catData});
        
        // Handle empty data case
        if (!Array.isArray(catLabels) || !Array.isArray(catData) || catLabels.length === 0) {
            // Show empty state chart
            const emptyLabels = ['No Data'];
            const emptyData = [1];
            const emptyColors = ['#e5e7eb'];
            
            try {
                window.categoryChart = new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: emptyLabels,
                        datasets: [{
                            data: emptyData,
                            backgroundColor: emptyColors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        }
                    }
                });
            } catch (error) {
                console.error('Error creating empty category chart:', error);
            }
        } else {
            // Show actual data chart
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
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
