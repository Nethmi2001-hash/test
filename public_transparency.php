<?php
/**
 * Public Transparency Dashboard
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

// Donations by category
$donations_by_category = $con->query("
    SELECT c.name, SUM(d.amount) as total, COUNT(*) as count
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
        }
        body {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        }
        .hero-section {
            background: linear-gradient(135deg, rgba(245, 124, 0, 0.95) 0%, rgba(255, 152, 0, 0.95) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
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
            color: var(--monastery-saffron);
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
            border-left: 4px solid var(--monastery-saffron);
        }
        .activity-item.expense {
            border-left-color: #dc3545;
        }
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .balance-positive {
            color: #28a745;
        }
        .balance-negative {
            color: #dc3545;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#" style="color: var(--monastery-saffron); font-weight: bold;">
            🪷 Seela Suwa Herath - Transparency Dashboard
        </a>
        <div class="ms-auto">
            <a href="public_donate.php" class="btn btn-sm" style="background: var(--monastery-saffron); color: white;">
                <i class="bi bi-heart-fill"></i> Donate
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
        <h1 class="mb-3">📊 Financial Transparency</h1>
        <p class="lead">See exactly how your donations help our monastery and monks</p>
        <p class="small">Updated in real-time • Year <?= $current_year ?></p>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-cash-coin" style="font-size: 3rem; color: #28a745;"></i>
                    <div class="stat-number">Rs. <?= number_format($donations_stats['total'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Donations</div>
                    <small class="text-muted"><?= number_format($donations_stats['count'] ?? 0) ?> contributions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-receipt" style="font-size: 3rem; color: #dc3545;"></i>
                    <div class="stat-number">Rs. <?= number_format($expenses_stats['total'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Expenses</div>
                    <small class="text-muted"><?= number_format($expenses_stats['count'] ?? 0) ?> bills paid</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-calculator" style="font-size: 3rem; color: <?= $balance >= 0 ? '#28a745' : '#dc3545' ?>;"></i>
                    <div class="stat-number <?= $balance >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                        Rs. <?= number_format(abs($balance), 0) ?>
                    </div>
                    <div class="stat-label"><?= $balance >= 0 ? 'Surplus' : 'Deficit' ?></div>
                    <small class="text-muted">Current balance</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-graph-up-arrow" style="font-size: 3rem; color: var(--monastery-saffron);"></i>
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
                    <h5 style="color: var(--monastery-saffron); margin-bottom: 20px;">
                        <i class="bi bi-pie-chart"></i> How Donations Are Categorized
                    </h5>
                    <canvas id="donationsChart" height="300"></canvas>
                </div>
            </div>
            
            <!-- Expense Breakdown -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h5 style="color: #dc3545; margin-bottom: 20px;">
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
                    <h5 style="color: var(--monastery-saffron); margin-bottom: 20px;">
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
            <h5 style="color: var(--monastery-saffron); margin-bottom: 20px;">
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
        <h3 style="color: var(--monastery-saffron);">Support Our Mission</h3>
        <p class="lead mb-4">Your donation directly helps monks receive healthcare and support</p>
        <a href="public_donate.php" class="btn btn-lg" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white; padding: 15px 50px;">
            <i class="bi bi-heart-fill"></i> Make a Donation
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
            backgroundColor: ['#28a745', '#20c997', '#17a2b8', '#6610f2', '#6f42c1', '#e83e8c']
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
            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#ff9800', '#f57c00', '#e65100']
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
            borderColor: '#f57c00',
            backgroundColor: 'rgba(245, 124, 0, 0.1)',
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
</body>
</html>
<?php $con->close(); ?>
