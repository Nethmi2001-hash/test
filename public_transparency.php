<?php
/**
 * Enhanced Public Transparency Dashboard
 * Shows donation usage and impact to build public trust
 * NO LOGIN REQUIRED - Anyone can view
 */
require_once __DIR__ . '/includes/db_config.php';

$con = getDBConnection();

// Get current year stats
$current_year = date('Y');

// Total donations this year
$donations_query = "SELECT 
                        COUNT(*) as count,
                        SUM(amount) as total,
                        AVG(amount) as average
                    FROM donations 
                    WHERE YEAR(created_at) = $current_year 
                    AND status IN ('paid', 'verified')";
$donations_stats = $con->query($donations_query)->fetch_assoc();

// Total expenses this year
$expenses_query = "SELECT 
                      COUNT(*) as count,
                      SUM(amount) as total
                   FROM bills 
                   WHERE YEAR(bill_date) = $current_year 
                   AND status = 'paid'";
$expenses_stats = $con->query($expenses_query)->fetch_assoc();

// Get lifetime statistics
$lifetime_donations = $con->query("SELECT SUM(amount) as total FROM donations WHERE status IN ('paid', 'verified')")->fetch_assoc()['total'] ?? 0;
$lifetime_expenses = $con->query("SELECT SUM(amount) as total FROM bills WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;

// Donations by category
$donations_by_category = $con->query("
    SELECT c.name, c.target_amount, SUM(d.amount) as total, COUNT(*) as count
    FROM donations d
    JOIN categories c ON d.category_id = c.category_id
    WHERE YEAR(d.created_at) = $current_year
    AND d.status IN ('paid', 'verified')
    GROUP BY c.category_id
    ORDER BY total DESC
");

// Expenses by category
$expenses_by_category = $con->query("
    SELECT c.name, SUM(b.amount) as total, COUNT(*) as count
    FROM bills b
    JOIN categories c ON b.category_id = c.category_id
    WHERE YEAR(b.bill_date) = $current_year
    AND b.status = 'paid'
    GROUP BY c.category_id
    ORDER BY total DESC
");

// Monthly trend
$monthly_trend = $con->query("
    SELECT 
        MONTH(created_at) as month,
        MONTHNAME(created_at) as month_name,
        SUM(amount) as donations
    FROM donations
    WHERE YEAR(created_at) = $current_year
    AND status IN ('paid', 'verified')
    GROUP BY MONTH(created_at)
    ORDER BY month
");

// Get monthly expenses for comparison
$monthly_expenses = $con->query("
    SELECT 
        MONTH(bill_date) as month,
        SUM(amount) as expenses
    FROM bills
    WHERE YEAR(bill_date) = $current_year
    AND status = 'paid'
    GROUP BY MONTH(bill_date)
    ORDER BY month
");

// Recent activities
$recent_activities = $con->query("
    SELECT 'donation' as type, donor_name as name, amount, created_at as date, 'Donation' as activity_type
    FROM donations
    WHERE status IN ('paid', 'verified')
    UNION ALL
    SELECT 'expense' as type, vendor as name, amount, bill_date as date, 'Expense' as activity_type
    FROM bills
    WHERE status = 'paid'
    ORDER BY date DESC
    LIMIT 15
");

// Statistics
$stats = [
    'donations' => $donations_stats['total'] ?? 0,
    'expenses' => $expenses_stats['total'] ?? 0,
    'balance' => ($donations_stats['total'] ?? 0) - ($expenses_stats['total'] ?? 0),
    'donor_count' => $donations_stats['count'] ?? 0,
    'avg_donation' => $donations_stats['average'] ?? 0,
    'lifetime_donations' => $lifetime_donations,
    'lifetime_expenses' => $lifetime_expenses,
    'lifetime_balance' => $lifetime_donations - $lifetime_expenses
];

// Prepare chart data
$monthly_data = [];
$expense_data = [];
for ($i = 1; $i <= 12; $i++) {
    $monthly_data[$i] = 0;
    $expense_data[$i] = 0;
}

while ($row = $monthly_trend->fetch_assoc()) {
    $monthly_data[$row['month']] = $row['donations'];
}

$monthly_expenses->data_seek(0);
while ($row = $monthly_expenses->fetch_assoc()) {
    $expense_data[$row['month']] = $row['expenses'];
}

$balance = ($donations_stats['total'] ?? 0) - ($expenses_stats['total'] ?? 0);
    AND b.status = 'paid'
    GROUP BY c.category_id
    ORDER BY total DESC
");

// Monthly trend
$monthly_trend = $con->query("
    SELECT 
        MONTH(created_at) as month,
        SUM(amount) as total
    FROM donations
    WHERE YEAR(created_at) = $current_year
    AND status IN ('paid', 'verified')
    GROUP BY MONTH(created_at)
    ORDER BY month
");

// Recent activities
$recent_activities = $con->query("
    SELECT 'donation' as type, donor_name as name, amount, created_at as date
    FROM donations
    WHERE status IN ('paid', 'verified')
    UNION ALL
    SELECT 'expense' as type, vendor_name as name, amount, bill_date as date
    FROM bills
    WHERE status = 'paid'
    ORDER BY date DESC
    LIMIT 20
");

$balance = ($donations_stats['total'] ?? 0) - ($expenses_stats['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transparency Dashboard - Seela Suwa Herath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --monastery-green: #A67C52;
            --monastery-dark-green: #8D6844;
            --monastery-gold: #A67C52;
            --monastery-cream: #FAF8F3;
            --monastery-accent: #7A1E1E;
        }
        body {
            background: linear-gradient(135deg, var(--monastery-cream) 0%, #eee3cc 100%);
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(64, 83, 56, 0.95) 0%, rgba(42, 58, 36, 0.95) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .mission-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 0.92rem;
            margin-bottom: 14px;
        }

        .founder-strip {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            margin-top: -24px;
            position: relative;
            z-index: 5;
            border: 1px solid rgba(110, 134, 98, 0.15);
            box-shadow: 0 12px 24px rgba(32, 42, 29, 0.12);
        }

        .founder-photo {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid #ECE5D8;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-label {
            color: #666;
            margin-top: 10px;
        }
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .activity-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--monastery-green);
        }
        .activity-item.expense {
            border-left-color: #dc3545;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .balance-positive {
            color: var(--success);
        }
        .balance-negative {
            color: var(--danger);
        }

        .stat-icon { font-size: 3rem; }

        .section-title {
            color: var(--monastery-accent);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .btn-helping-hand {
            background: var(--monastery-gold);
            color: #111827;
            border: none;
        }

        .btn-helping-hand:hover {
            background: #D97706;
            color: #111827;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#" style="color: var(--monastery-green); font-weight: bold;">
            <i class="bi bi-person-hearts"></i> Seela Suwa Herath - Transparency Dashboard
        </a>
        <div class="ms-auto">
            <a href="public_donate.php" class="btn btn-sm btn-helping-hand">
                <i class="bi bi-person-hearts"></i> Offer Support
            </a>
            <a href="login.php" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="bi bi-box-arrow-in-right"></i> Staff Login
            </a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="mission-badge"><i class="bi bi-shield-heart"></i> Public Trust & Healthcare Impact</div>
        <h1 class="mb-3"><i class="bi bi-bar-chart-line"></i> Financial Transparency</h1>
        <p class="lead">See exactly how donations are used for monk healthcare, medicines, and care services.</p>
        <p class="small">Updated in real-time • Year <?= $current_year ?></p>
    </div>
</section>

<section class="container">
    <div class="founder-strip interactive-lift">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <img src="images/img1.jpeg" alt="Solewewa Chandrasiri Thero - Founder" class="founder-photo">
            </div>
            <div class="col">
                <h6 class="mb-1 section-title"><i class="bi bi-award"></i> Founded by Ven. Solewewa Chandrasiri Thero</h6>
                <p class="mb-0 text-muted">This system exists to serve monk healthcare with dignity, transparency, and compassionate donor support.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-cash-coin stat-icon icon-tone-success"></i>
                    <div class="stat-number">Rs. <?= number_format($donations_stats['total'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Donations</div>
                    <small class="text-muted"><?= number_format($donations_stats['count'] ?? 0) ?> contributions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-receipt stat-icon icon-tone-danger"></i>
                    <div class="stat-number">Rs. <?= number_format($expenses_stats['total'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Expenses</div>
                    <small class="text-muted"><?= number_format($expenses_stats['count'] ?? 0) ?> bills paid</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-calculator stat-icon <?= $balance >= 0 ? 'icon-tone-success' : 'icon-tone-danger' ?>"></i>
                    <div class="stat-number <?= $balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                        Rs. <?= number_format(abs($balance), 0) ?>
                    </div>
                    <div class="stat-label"><?= $balance >= 0 ? 'Surplus' : 'Deficit' ?></div>
                    <small class="text-muted">Current balance</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-graph-up-arrow stat-icon icon-tone-primary"></i>
                    <div class="stat-number">Rs. <?= number_format($donations_stats['average'] ?? 0, 0) ?></div>
                    <div class="stat-label">Average Donation</div>
                    <small class="text-muted">Per contribution</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Charts Section -->
<section class="py-4">
    <div class="container">
        <div class="row">
            <!-- Donation Breakdown -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="section-title">
                        <i class="bi bi-pie-chart"></i> How Donations Are Categorized
                    </h5>
                    <canvas id="donationsChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Expense Breakdown -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 class="section-title">
                        <i class="bi bi-pie-chart-fill"></i> How Funds Are Used
                    </h5>
                    <canvas id="expensesChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Monthly Trend -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="chart-card">
                    <h5 class="section-title">
                        <i class="bi bi-graph-up"></i> Monthly Donation Trend <?= $current_year ?>
                    </h5>
                    <canvas id="monthlyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Activities -->
<section class="py-4">
    <div class="container">
        <div class="chart-card">
            <h5 class="section-title">
                <i class="bi bi-clock-history"></i> Recent Financial Activities
            </h5>
            <div class="row">
                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                <div class="col-md-6">
                    <div class="activity-item <?= $activity['type'] ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= $activity['type'] == 'donation' ? '💚 Donation' : '💸 Expense' ?></strong>
                                <p class="mb-0 small text-muted"><?= htmlspecialchars($activity['name'] ?? 'Anonymous') ?></p>
                            </div>
                            <div class="text-end">
                                <strong style="color: <?= $activity['type'] == 'donation' ? '#28a745' : '#dc3545' ?>">
                                    Rs. <?= number_format($activity['amount'], 2) ?>
                                </strong>
                                <p class="mb-0 small text-muted"><?= date('M d, Y', strtotime($activity['date'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5" style="background: white;">
    <div class="container text-center">
        <h3 style="color: var(--monastery-accent);">Support with a Helping Hand</h3>
        <p class="lead mb-4">Your donation directly helps monks receive healthcare and compassionate support</p>
        <a href="public_donate.php" class="btn btn-lg" style="background: linear-gradient(135deg, var(--monastery-gold) 0%, #D97706 100%); color: #111827; padding: 15px 50px;">
            <i class="bi bi-person-hearts"></i> Offer Support
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 text-center" style="background: #333; color: white;">
    <p class="mb-0">&copy; <?= date('Y') ?> Seela Suwa Herath Bikshu Gilan Arana</p>
    <p class="mb-0 small">Transparency builds trust • All data updated in real-time</p>
</footer>

<script>
// Donations by Category Chart
const donationsCtx = document.getElementById('donationsChart').getContext('2d');
new Chart(donationsCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            $donations_by_category->data_seek(0);
            $labels = [];
            $data = [];
            while ($row = $donations_by_category->fetch_assoc()) {
                $labels[] = "'" . $row['name'] . "'";
                $data[] = $row['total'];
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            data: [<?= implode(',', $data) ?>],
            backgroundColor: ['#2d5016', '#3f6a24', '#5f7f3a', '#8ea05e', '#D4AF37', '#e5c977']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Expenses by Category Chart
const expensesCtx = document.getElementById('expensesChart').getContext('2d');
new Chart(expensesCtx, {
    type: 'pie',
    data: {
        labels: [<?php 
            $expenses_by_category->data_seek(0);
            $labels = [];
            $data = [];
            while ($row = $expenses_by_category->fetch_assoc()) {
                $labels[] = "'" . $row['name'] . "'";
                $data[] = $row['total'];
            }
            echo implode(',', $labels);
        ?>],
        datasets: [{
            data: [<?= implode(',', $data) ?>],
            backgroundColor: ['#7f1d1d', '#9a3412', '#b45309', '#92400e', '#6b4423', '#3f3f46']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Monthly Trend Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyData = Array(12).fill(0);
<?php 
$monthly_trend->data_seek(0);
while ($row = $monthly_trend->fetch_assoc()) {
    echo "monthlyData[" . ($row['month'] - 1) . "] = " . $row['total'] . ";\n";
}
?>
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Donations (Rs.)',
            data: monthlyData,
            borderColor: '#2d5016',
            backgroundColor: 'rgba(45, 80, 22, 0.12)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ui-interactions.js"></script>
</body>
</html>
<?php $con->close(); ?>
