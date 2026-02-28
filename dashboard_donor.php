<?php
if (!isset($_SESSION['logged_in']) || (basename($_SERVER['PHP_SELF']) === 'dashboard_donor.php')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}
require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['username'] ?? 'Donor';

// Get donor's donation stats
$stats = [
    'total_donated' => 0,
    'donation_count' => 0,
    'this_month' => 0,
    'verified_count' => 0
];

$r = $conn->query("SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt FROM donations WHERE donor_user_id = $userId AND status IN ('paid', 'verified')");
if ($r) {
    $row = $r->fetch_assoc();
    $stats['total_donated'] = $row['total'];
    $stats['donation_count'] = $row['cnt'];
}

$r = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE donor_user_id = $userId AND status IN ('paid', 'verified') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($r) $stats['this_month'] = $r->fetch_assoc()['total'];

$r = $conn->query("SELECT COUNT(*) as c FROM donations WHERE donor_user_id = $userId AND status = 'verified'");
if ($r) $stats['verified_count'] = $r->fetch_assoc()['c'];

// Recent donations
$recent_donations = [];
$r = $conn->query("
    SELECT d.*, c.name as category_name 
    FROM donations d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    WHERE d.donor_user_id = $userId 
    ORDER BY d.created_at DESC 
    LIMIT 10
");
if ($r) while ($row = $r->fetch_assoc()) $recent_donations[] = $row;

// Donations by category
$by_category = [];
$r = $conn->query("
    SELECT c.name as category_name, COALESCE(SUM(d.amount), 0) as total 
    FROM donations d 
    JOIN categories c ON d.category_id = c.category_id 
    WHERE d.donor_user_id = $userId AND d.status IN ('paid', 'verified')
    GROUP BY c.category_id, c.name
    ORDER BY total DESC
");
if ($r) while ($row = $r->fetch_assoc()) $by_category[] = $row;

// Monthly donation trend (last 6 months)
$monthly_trend = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $r = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE donor_user_id = $userId AND DATE_FORMAT(created_at, '%Y-%m') = '$month' AND status IN ('paid', 'verified')");
    $monthly_trend[] = [
        'month' => date('M', strtotime($month)),
        'amount' => $r ? $r->fetch_assoc()['total'] : 0
    ];
}

// Overall monastery transparency data
$total_monastery_donations = 0;
$total_monastery_expenses = 0;
$r = $conn->query("SELECT COALESCE(SUM(amount), 0) as t FROM donations WHERE status IN ('paid', 'verified')");
if ($r) $total_monastery_donations = $r->fetch_assoc()['t'];
$r = $conn->query("SELECT COALESCE(SUM(amount), 0) as t FROM bills");
if ($r) $total_monastery_expenses = $r->fetch_assoc()['t'];
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
</head>
<body>

<?php include 'navbar.php'; ?>

    <!-- Welcome -->
    <div class="welcome-card animate-fade-in">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h2><i class="bi bi-suit-heart me-2"></i>Welcome, <?= htmlspecialchars($userName) ?>!</h2>
                <p style="margin:0;">Thank you for your generous contributions to Seela Suwa Herath Bikshu Gilan Arana.</p>
            </div>
            <div class="welcome-date">
                <i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4 stagger-children">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #f97316;">
                <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Total Donated</div>
                    <div class="stat-value">Rs.<?= number_format($stats['total_donated']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #0284c7;">
                <div class="stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-receipt"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Total Donations</div>
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
                    <div class="stat-label">Verified</div>
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
                <canvas id="trendChart" height="160"></canvas>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card animate-fade-in" style="height:100%;">
                <div class="chart-header">
                    <h6><i class="bi bi-pie-chart me-2"></i>By Category</h6>
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

<?php include 'includes/footer.php'; ?>

<script>
// Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthly_trend, 'month')) ?>,
        datasets: [{
            label: 'Donations (Rs.)',
            data: <?= json_encode(array_column($monthly_trend, 'amount')) ?>,
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
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'Rs.' + v.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});

<?php if (count($by_category) > 0): ?>
// Category Chart
const catCtx = document.getElementById('categoryChart').getContext('2d');
const catColors = ['#f97316', '#0284c7', '#7c3aed', '#d97706', '#dc2626', '#0891b2'];
new Chart(catCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($by_category, 'category_name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($by_category, 'total')) ?>,
            backgroundColor: catColors.slice(0, <?= count($by_category) ?>),
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12, family: 'Inter' }, padding: 16, boxWidth: 12 } }
        }
    }
});
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
