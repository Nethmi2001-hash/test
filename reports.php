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

// Get date range from request or default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'financial';

// Financial Report Data
$donations_by_category = [];
$expenses_by_category = [];
$total_donations = 0;
$total_expenses = 0;

if ($report_type == 'financial') {
    // Donations by category
    $result = $conn->query("
        SELECT c.name as category, SUM(d.amount) as total
        FROM donations d
        JOIN categories c ON d.category_id = c.category_id
        WHERE d.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND d.status = 'verified'
        GROUP BY c.category_id, c.name
        ORDER BY total DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $donations_by_category[] = $row;
            $total_donations += $row['total'];
        }
    }

    // Expenses by category
    $result = $conn->query("
        SELECT c.name as category, SUM(b.amount) as total
        FROM bills b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
        AND b.status = 'approved'
        GROUP BY c.category_id, c.name
        ORDER BY total DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $expenses_by_category[] = $row;
            $total_expenses += $row['total'];
        }
    }
}

// Appointment Statistics
$appointment_stats = [
    'total' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'no_show' => 0,
    'by_doctor' => []
];

if ($report_type == 'appointments') {
    $result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show
        FROM appointments
        WHERE app_date BETWEEN '$start_date' AND '$end_date'
    ");
    if ($result) {
        $appointment_stats = array_merge($appointment_stats, $result->fetch_assoc());
    }

    // By doctor
    $result = $conn->query("
        SELECT d.full_name as doctor, COUNT(*) as count,
               SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.app_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY d.doctor_id, d.full_name
        ORDER BY count DESC
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointment_stats['by_doctor'][] = $row;
        }
    }
}

// Donor Report
$top_donors = [];
if ($report_type == 'donors') {
    $result = $conn->query("
        SELECT donor_name, donor_email, COUNT(*) as donation_count, SUM(amount) as total_amount
        FROM donations
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND status = 'verified'
        GROUP BY donor_name, donor_email
        ORDER BY total_amount DESC
        LIMIT 20
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $top_donors[] = $row;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
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
        .report-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .report-card h5 {
            color: var(--monastery-saffron);
            border-left: 4px solid var(--monastery-saffron);
            padding-left: 15px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .stat-badge {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-badge h3 {
            margin: 0;
            font-size: 2rem;
        }
        .stat-badge p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .table-custom {
            border-radius: 10px;
            overflow: hidden;
        }
        .table-custom thead {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
        }
        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
        }
        .btn-export:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
            color: white;
        }
        .nav-pills .nav-link {
            color: var(--monastery-saffron);
            border: 2px solid transparent;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
        }
        .progress-custom {
            height: 25px;
            border-radius: 12px;
            background: #f0f0f0;
        }
        .progress-bar-custom {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border-radius: 12px;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4 mb-5 px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-graph-up-arrow"></i> Reports & Analytics</h2>
                <p class="mb-0 mt-1 opacity-75">Comprehensive financial and operational reports</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Report
                </button>
                <button class="btn btn-export" onclick="exportToCSV()">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Date Range Selector -->
    <div class="report-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label"><strong>Start Date</strong></label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label"><strong>End Date</strong></label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label"><strong>Report Type</strong></label>
                <select name="report_type" class="form-select">
                    <option value="financial" <?= $report_type == 'financial' ? 'selected' : '' ?>>Financial Summary</option>
                    <option value="appointments" <?= $report_type == 'appointments' ? 'selected' : '' ?>>Appointment Statistics</option>
                    <option value="donors" <?= $report_type == 'donors' ? 'selected' : '' ?>>Donor Report</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Generate
                </button>
            </div>
        </form>
    </div>

    <!-- Financial Report -->
    <?php if ($report_type == 'financial'): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="stat-badge" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3>Rs. <?= number_format($total_donations, 2) ?></h3>
                    <p>Total Donations</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-badge" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                    <h3>Rs. <?= number_format($total_expenses, 2) ?></h3>
                    <p>Total Expenses</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="stat-badge" style="background: linear-gradient(135deg, <?= ($total_donations - $total_expenses) >= 0 ? '#28a745, #20c997' : '#dc3545, #fd7e14' ?>);">
                    <h3>Rs. <?= number_format($total_donations - $total_expenses, 2) ?></h3>
                    <p>Net Balance <?= ($total_donations - $total_expenses) >= 0 ? '(Surplus)' : '(Deficit)' ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Donations by Category -->
            <div class="col-md-6">
                <div class="report-card">
                    <h5><i class="bi bi-cash-coin"></i> Donations by Category</h5>
                    <?php if (count($donations_by_category) > 0): ?>
                        <canvas id="donationsChart" height="250"></canvas>
                        <table class="table table-custom mt-3">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donations_by_category as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['category']) ?></td>
                                        <td class="text-end">Rs. <?= number_format($item['total'], 2) ?></td>
                                        <td class="text-end"><?= number_format(($item['total'] / $total_donations) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No donation data for selected period</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expenses by Category -->
            <div class="col-md-6">
                <div class="report-card">
                    <h5><i class="bi bi-receipt"></i> Expenses by Category</h5>
                    <?php if (count($expenses_by_category) > 0): ?>
                        <canvas id="expensesChart" height="250"></canvas>
                        <table class="table table-custom mt-3">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses_by_category as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['category']) ?></td>
                                        <td class="text-end">Rs. <?= number_format($item['total'], 2) ?></td>
                                        <td class="text-end"><?= number_format(($item['total'] / $total_expenses) * 100, 1) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No expense data for selected period</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Appointment Report -->
    <?php if ($report_type == 'appointments'): ?>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-badge">
                    <h3><?= $appointment_stats['total'] ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3><?= $appointment_stats['completed'] ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                    <h3><?= $appointment_stats['cancelled'] ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);">
                    <h3><?= $appointment_stats['no_show'] ?></h3>
                    <p>No Show</p>
                </div>
            </div>
        </div>

        <div class="report-card">
            <h5><i class="bi bi-person-badge"></i> Appointments by Doctor</h5>
            <?php if (count($appointment_stats['by_doctor']) > 0): ?>
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Doctor Name</th>
                            <th class="text-center">Total Appointments</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointment_stats['by_doctor'] as $doctor): ?>
                            <tr>
                                <td><?= htmlspecialchars($doctor['doctor']) ?></td>
                                <td class="text-center"><?= $doctor['count'] ?></td>
                                <td class="text-center"><?= $doctor['completed'] ?></td>
                                <td class="text-center">
                                    <?php 
                                    $rate = ($doctor['count'] > 0) ? ($doctor['completed'] / $doctor['count']) * 100 : 0;
                                    ?>
                                    <div class="progress progress-custom">
                                        <div class="progress-bar progress-bar-custom" style="width: <?= $rate ?>%">
                                            <?= number_format($rate, 0) ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4">No appointment data for selected period</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Donor Report -->
    <?php if ($report_type == 'donors'): ?>
        <div class="report-card">
            <h5><i class="bi bi-people"></i> Top Donors</h5>
            <?php if (count($top_donors) > 0): ?>
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Donor Name</th>
                            <th>Email</th>
                            <th class="text-center">Donations</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_donors as $index => $donor): ?>
                            <tr>
                                <td>
                                    <?php if ($index < 3): ?>
                                        <span class="badge bg-warning text-dark">🏆 #<?= $index + 1 ?></span>
                                    <?php else: ?>
                                        #<?= $index + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($donor['donor_name']) ?></td>
                                <td><?= htmlspecialchars($donor['donor_email']) ?></td>
                                <td class="text-center"><?= $donor['donation_count'] ?></td>
                                <td class="text-end"><strong>Rs. <?= number_format($donor['total_amount'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted text-center py-4">No donor data for selected period</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Donations Chart
<?php if ($report_type == 'financial' && count($donations_by_category) > 0): ?>
const donationsCtx = document.getElementById('donationsChart').getContext('2d');
new Chart(donationsCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($donations_by_category, 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($donations_by_category, 'total')) ?>,
            backgroundColor: [
                '#28a745',
                '#20c997',
                '#17a2b8',
                '#6610f2',
                '#6f42c1',
                '#e83e8c'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Expenses Chart
<?php if ($report_type == 'financial' && count($expenses_by_category) > 0): ?>
const expensesCtx = document.getElementById('expensesChart').getContext('2d');
new Chart(expensesCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($expenses_by_category, 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($expenses_by_category, 'total')) ?>,
            backgroundColor: [
                '#dc3545',
                '#fd7e14',
                '#ffc107',
                '#ff9800',
                '#f57c00',
                '#e65100'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

// Export to CSV
function exportToCSV() {
    const reportType = '<?= $report_type ?>';
    const startDate = '<?= $start_date ?>';
    const endDate = '<?= $end_date ?>';
    
    window.location.href = `export_report.php?type=${reportType}&start=${startDate}&end=${endDate}`;
}

// Print styles
window.onbeforeprint = function() {
    document.body.style.background = 'white';
};
</script>

</body>
</html>
