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
    <link rel="stylesheet" href="assets/css/modern-design.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *,*::before,*::after { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-body, #f8fafc);
            color: var(--text-primary, #0f172a);
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- Top Navigation ---- */
        .top-nav {
            background: var(--bg-card, #fff);
            border-bottom: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-xs);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-nav .nav-inner {
            max-width: 1260px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-nav .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--primary-700, #047857);
            text-decoration: none;
        }
        .top-nav .brand .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            color: #fff;
            border-radius: var(--border-radius-sm, 8px);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .top-nav .nav-actions { display: flex; align-items: center; gap: 10px; }
        .btn-cta {
            background: linear-gradient(135deg, var(--primary-500, #10b981), var(--primary-700, #047857));
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: var(--border-radius-full, 9999px);
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
            transition: all var(--transition);
        }
        .btn-cta:hover { opacity: .9; color: #fff; transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary, #64748b);
            border: 1px solid var(--border-color, #e2e8f0);
            padding: 8px 18px;
            border-radius: var(--border-radius-full, 9999px);
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none;
            transition: all var(--transition);
        }
        .btn-ghost:hover { background: var(--slate-100, #f1f5f9); color: var(--text-primary); }

        /* ---- Hero ---- */
        .hero {
            background: linear-gradient(135deg, var(--primary-800, #065f46) 0%, var(--primary-900, #064e3b) 60%, var(--slate-900, #0f172a) 100%);
            color: #fff;
            padding: 64px 0 72px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(circle at 30% 20%, rgba(16,185,129,.15) 0, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(251,191,36,.08) 0, transparent 40%);
            pointer-events: none;
        }
        .hero .badge-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: var(--border-radius-full);
            padding: 6px 18px;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: .3px;
            margin-bottom: 18px;
            backdrop-filter: blur(4px);
        }
        .hero h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.4rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 12px;
        }
        .hero .lead { opacity: .85; font-size: 1.05rem; max-width: 560px; margin: 0 auto 8px; }
        .hero .year-tag { opacity: .55; font-size: .82rem; }

        /* ---- Founder Strip ---- */
        .founder-strip {
            max-width: 800px;
            margin: -36px auto 0;
            position: relative;
            z-index: 5;
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-xl, 20px);
            padding: 22px 28px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .founder-strip img {
            width: 72px; height: 72px;
            object-fit: cover;
            border-radius: var(--border-radius, 12px);
            border: 3px solid var(--primary-100, #d1fae5);
            flex-shrink: 0;
        }
        .founder-strip h6 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
            font-size: .95rem;
        }
        .founder-strip p { color: var(--text-secondary); font-size: .85rem; margin: 0; }

        /* ---- Section Wrapper ---- */
        .section-wrap { max-width: 1260px; margin: 0 auto; padding: 0 24px; }

        /* ---- Stat Cards ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding-top: 56px;
            padding-bottom: 16px;
        }
        @media (max-width: 991px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575px) { .stats-grid { grid-template-columns: 1fr; } }

        .tcard {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-lg, 16px);
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }
        .tcard::after {
            content: '';
            position: absolute; top: 0; left: 0;
            width: 100%; height: 3px;
            background: linear-gradient(90deg, var(--stat-color, var(--primary-500)), transparent);
            opacity: 0; transition: opacity var(--transition);
        }
        .tcard:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .tcard:hover::after { opacity: 1; }

        .tcard .s-icon {
            width: 48px; height: 48px;
            border-radius: var(--border-radius, 12px);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .s-icon.emerald { background: var(--primary-100, #d1fae5); color: var(--primary-700, #047857); }
        .s-icon.rose { background: #ffe4e6; color: #be123c; }
        .s-icon.blue { background: #dbeafe; color: #1d4ed8; }
        .s-icon.amber { background: var(--accent-100, #fef3c7); color: var(--accent-700, #b45309); }

        .tcard .s-info { flex: 1; }
        .tcard .s-label {
            font-size: 11.5px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .8px;
            color: var(--text-secondary, #64748b);
            margin-bottom: 4px;
        }
        .tcard .s-value {
            font-size: 1.65rem; font-weight: 800;
            color: var(--text-primary, #0f172a);
            line-height: 1.2; letter-spacing: -.5px;
        }
        .tcard .s-sub {
            font-size: .78rem;
            color: var(--text-secondary, #64748b);
            margin-top: 2px;
        }
        .s-value.positive { color: var(--success, #059669); }
        .s-value.negative { color: var(--danger, #dc2626); }

        /* ---- Chart Cards ---- */
        .chart-card {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-xl, 20px);
            padding: 28px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .chart-card .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .chart-card .card-title i {
            color: var(--primary-500, #10b981);
        }

        /* ---- Activity Items ---- */
        .activity-item {
            padding: 14px 18px;
            background: var(--slate-50, #f8fafc);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius, 12px);
            margin-bottom: 10px;
            transition: all var(--transition);
            border-left: 4px solid var(--primary-400, #34d399);
        }
        .activity-item:hover { box-shadow: var(--shadow-sm); transform: translateX(2px); }
        .activity-item.expense { border-left-color: var(--danger, #dc2626); }
        .activity-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: .78rem; font-weight: 600;
            padding: 3px 10px; border-radius: var(--border-radius-full);
        }
        .activity-badge.donation { background: var(--success-light, #ecfdf5); color: var(--success, #059669); }
        .activity-badge.expense { background: var(--danger-light, #fef2f2); color: var(--danger, #dc2626); }
        .activity-name { font-size: .85rem; color: var(--text-secondary); margin: 0; }
        .activity-amount { font-weight: 700; font-size: .95rem; }
        .activity-amount.income { color: var(--success, #059669); }
        .activity-amount.cost { color: var(--danger, #dc2626); }
        .activity-date { font-size: .78rem; color: var(--text-secondary); margin: 0; }

        /* ---- CTA Section ---- */
        .cta-section {
            background: var(--bg-card, #fff);
            border-top: 1px solid var(--border-color, #e2e8f0);
            padding: 56px 0;
            text-align: center;
        }
        .cta-section h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800; font-size: 1.6rem;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .cta-section p { color: var(--text-secondary); max-width: 480px; margin: 0 auto 24px; }
        .btn-cta-lg {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
            color: #fff; border: none;
            padding: 14px 44px;
            border-radius: var(--border-radius-full);
            font-weight: 700; font-size: 1rem;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
            transition: all var(--transition);
            box-shadow: 0 4px 14px rgba(5,150,105,.3);
        }
        .btn-cta-lg:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,.35); color: #fff; }

        /* ---- Footer ---- */
        .site-footer {
            background: var(--slate-900, #0f172a);
            color: rgba(255,255,255,.6);
            padding: 28px 0;
            text-align: center;
            font-size: .85rem;
        }
        .site-footer strong { color: rgba(255,255,255,.85); }
    </style>
</head>
<body>

<!-- Top Navigation -->
<nav class="top-nav">
    <div class="nav-inner">
        <a href="#" class="brand">
            <span class="brand-icon"><i class="bi bi-heart-pulse"></i></span>
            Seela Suwa Herath
        </a>
        <div class="nav-actions">
            <a href="public_donate.php" class="btn-cta">
                <i class="bi bi-heart"></i> Offer Support
            </a>
            <a href="login.php" class="btn-ghost">
                <i class="bi bi-box-arrow-in-right"></i> Staff Login
            </a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="section-wrap" style="position:relative;z-index:1;">
        <div class="badge-pill"><i class="bi bi-shield-check"></i> Public Trust &amp; Healthcare Impact</div>
        <h1>Financial Transparency</h1>
        <p class="lead">See exactly how donations are used for monk healthcare, medicines, and care services.</p>
        <p class="year-tag">Updated in real-time &bull; Year <?= $current_year ?></p>
    </div>
</section>

<!-- Founder Strip -->
<div class="section-wrap">
    <div class="founder-strip">
        <img src="images/img1.jpeg" alt="Solewewa Chandrasiri Thero - Founder">
        <div>
            <h6><i class="bi bi-award-fill" style="color:var(--accent-500);"></i> Founded by Ven. Solewewa Chandrasiri Thero</h6>
            <p>This system exists to serve monk healthcare with dignity, transparency, and compassionate donor support.</p>
        </div>
    </div>
</div>

<!-- Stats Section -->
<section class="section-wrap">
    <div class="stats-grid">
        <div class="tcard" style="--stat-color: var(--success);">
            <div class="s-icon emerald"><i class="bi bi-cash-coin"></i></div>
            <div class="s-info">
                <div class="s-label">Total Donations</div>
                <div class="s-value">Rs. <?= number_format($donations_stats['total'] ?? 0, 0) ?></div>
                <div class="s-sub"><?= number_format($donations_stats['count'] ?? 0) ?> contributions</div>
            </div>
        </div>
        <div class="tcard" style="--stat-color: var(--danger);">
            <div class="s-icon rose"><i class="bi bi-receipt"></i></div>
            <div class="s-info">
                <div class="s-label">Total Expenses</div>
                <div class="s-value">Rs. <?= number_format($expenses_stats['total'] ?? 0, 0) ?></div>
                <div class="s-sub"><?= number_format($expenses_stats['count'] ?? 0) ?> bills paid</div>
            </div>
        </div>
        <div class="tcard" style="--stat-color: <?= $balance >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
            <div class="s-icon <?= $balance >= 0 ? 'emerald' : 'rose' ?>"><i class="bi bi-calculator"></i></div>
            <div class="s-info">
                <div class="s-label"><?= $balance >= 0 ? 'Surplus' : 'Deficit' ?></div>
                <div class="s-value <?= $balance >= 0 ? 'positive' : 'negative' ?>">Rs. <?= number_format(abs($balance), 0) ?></div>
                <div class="s-sub">Current balance</div>
            </div>
        </div>
        <div class="tcard" style="--stat-color: var(--info);">
            <div class="s-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="s-info">
                <div class="s-label">Average Donation</div>
                <div class="s-value">Rs. <?= number_format($donations_stats['average'] ?? 0, 0) ?></div>
                <div class="s-sub">Per contribution</div>
            </div>
        </div>
    </div>
</section>

<!-- Charts Section -->
<section class="section-wrap" style="padding-top:24px; padding-bottom:8px;">
    <div class="row g-4">
        <!-- Donation Breakdown -->
        <div class="col-md-6">
            <div class="chart-card">
                <div class="card-title"><i class="bi bi-pie-chart"></i> How Donations Are Categorized</div>
                <canvas id="donationsChart" height="300"></canvas>
            </div>
        </div>
        <!-- Expense Breakdown -->
        <div class="col-md-6">
            <div class="chart-card">
                <div class="card-title"><i class="bi bi-pie-chart-fill"></i> How Funds Are Used</div>
                <canvas id="expensesChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="chart-card" style="margin-top:4px;">
        <div class="card-title"><i class="bi bi-graph-up"></i> Monthly Donation Trend <?= $current_year ?></div>
        <canvas id="monthlyChart" height="100"></canvas>
    </div>
</section>

<!-- Recent Activities -->
<section class="section-wrap" style="padding-bottom:32px;">
    <div class="chart-card">
        <div class="card-title"><i class="bi bi-clock-history"></i> Recent Financial Activities</div>
        <div class="row g-3">
            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
            <div class="col-md-6">
                <div class="activity-item <?= $activity['type'] ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="activity-badge <?= $activity['type'] ?>">
                                <i class="bi <?= $activity['type'] == 'donation' ? 'bi-heart-fill' : 'bi-send-fill' ?>"></i>
                                <?= $activity['type'] == 'donation' ? 'Donation' : 'Expense' ?>
                            </span>
                            <p class="activity-name mt-1"><?= htmlspecialchars($activity['name'] ?? 'Anonymous') ?></p>
                        </div>
                        <div class="text-end">
                            <div class="activity-amount <?= $activity['type'] == 'donation' ? 'income' : 'cost' ?>">
                                Rs. <?= number_format($activity['amount'], 2) ?>
                            </div>
                            <p class="activity-date"><?= date('M d, Y', strtotime($activity['date'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="cta-section">
    <h3>Support with a Helping Hand</h3>
    <p>Your donation directly helps monks receive healthcare and compassionate support</p>
    <a href="public_donate.php" class="btn-cta-lg">
        <i class="bi bi-heart-pulse-fill"></i> Offer Support
    </a>
</section>

<!-- Footer -->
<footer class="site-footer">
    <p class="mb-1"><strong>&copy; <?= date('Y') ?> Seela Suwa Herath Bikshu Gilan Arana</strong></p>
    <p class="mb-0">Transparency builds trust &bull; All data updated in real-time</p>
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
            backgroundColor: ['#059669','#10b981','#34d399','#6ee7b7','#f59e0b','#fbbf24'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 } } }
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
            backgroundColor: ['#dc2626','#ef4444','#f97316','#f59e0b','#8b5cf6','#64748b'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 } } }
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
            borderColor: '#059669',
            backgroundColor: 'rgba(5, 150, 105, 0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#059669',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2.5
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
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.04)' },
                ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 } }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 } }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $con->close(); ?>
