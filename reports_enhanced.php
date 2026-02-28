<?php
require_once __DIR__ . '/includes/auth_enhanced.php';
require_once __DIR__ . '/includes/db_config.php';

// Require authentication and admin/helper role
requireAuth(['admin', 'helper']);

$conn = getDBConnection();

// Get parameters
$report_type = $_GET['report_type'] ?? 'financial';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'html';

// Report generation functions
function generateFinancialReport($conn, $start_date, $end_date) {
    $report = [
        'title' => 'Financial Report',
        'period' => "$start_date to $end_date",
        'summary' => [],
        'donations_by_category' => [],
        'expenses_by_category' => [],
        'monthly_trend' => [],
        'balance_analysis' => []
    ];
    
    // Summary totals
    $donations_total = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count 
        FROM donations 
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
        AND status IN ('verified', 'paid')
    ")->fetch_assoc();
    
    $expenses_total = $conn->query("
        SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count 
        FROM bills 
        WHERE bill_date BETWEEN '$start_date' AND '$end_date' 
        AND status = 'paid'
    ")->fetch_assoc();
    
    $report['summary'] = [
        'total_donations' => $donations_total['total'],
        'donation_count' => $donations_total['count'],
        'total_expenses' => $expenses_total['total'],
        'expense_count' => $expenses_total['count'],
        'net_balance' => $donations_total['total'] - $expenses_total['total'],
        'avg_donation' => $donations_total['count'] > 0 ? $donations_total['total'] / $donations_total['count'] : 0
    ];
    
    // Donations by category
    $result = $conn->query("
        SELECT c.name, c.target_amount, SUM(d.amount) as collected, COUNT(*) as count
        FROM donations d
        JOIN categories c ON d.category_id = c.category_id
        WHERE d.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND d.status IN ('verified', 'paid')
        GROUP BY c.category_id
        ORDER BY collected DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $report['donations_by_category'][] = $row;
    }
    
    // Expenses by category  
    $result = $conn->query("
        SELECT c.name, SUM(b.amount) as spent, COUNT(*) as count
        FROM bills b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.bill_date BETWEEN '$start_date' AND '$end_date'
        AND b.status = 'paid'
        GROUP BY c.category_id
        ORDER BY spent DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $report['expenses_by_category'][] = $row;
    }
    
    return $report;
}

function generateHealthReport($conn, $start_date, $end_date) {
    $report = [
        'title' => 'Healthcare Report',
        'period' => "$start_date to $end_date",
        'summary' => [],
        'appointments_by_status' => [],
        'appointments_by_doctor' => [],
        'medical_conditions' => [],
        'medication_usage' => []
    ];
    
    // Appointment summary
    $appointments = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show,
            COUNT(DISTINCT monk_id) as unique_patients
        FROM appointments
        WHERE app_date BETWEEN '$start_date' AND '$end_date'
    ")->fetch_assoc();
    
    $report['summary'] = $appointments;
    
    // By doctor
    $result = $conn->query("
        SELECT d.full_name, d.specialization, COUNT(*) as appointments,
               SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.app_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY d.doctor_id
        ORDER BY appointments DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $report['appointments_by_doctor'][] = $row;
    }
    
    // Medical records analysis (if available)
    $result = $conn->query("
        SELECT diagnosis, COUNT(*) as count
        FROM medical_records
        WHERE record_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY diagnosis
        ORDER BY count DESC
        LIMIT 10
    ");
    while ($row = $result->fetch_assoc()) {
        $report['medical_conditions'][] = $row;
    }
    
    return $report;
}

function generateDonorReport($conn, $start_date, $end_date) {
    $report = [
        'title' => 'Donor Report',
        'period' => "$start_date to $end_date",
        'summary' => [],
        'top_donors' => [],
        'donation_methods' => [],
        'donor_retention' => []
    ];
    
    // Donor summary
    $summary = $conn->query("
        SELECT 
            COUNT(DISTINCT donor_email) as unique_donors,
            COUNT(*) as total_donations,
            SUM(amount) as total_amount,
            AVG(amount) as avg_donation
        FROM donations
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND status IN ('verified', 'paid')
    ")->fetch_assoc();
    
    $report['summary'] = $summary;
    
    // Top donors
    $result = $conn->query("
        SELECT donor_name, donor_email, SUM(amount) as total_donated, COUNT(*) as donation_count
        FROM donations
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND status IN ('verified', 'paid')
        GROUP BY donor_email
        ORDER BY total_donated DESC
        LIMIT 20
    ");
    while ($row = $result->fetch_assoc()) {
        $report['top_donors'][] = $row;
    }
    
    // Payment methods
    $result = $conn->query("
        SELECT payment_method, COUNT(*) as count, SUM(amount) as total
        FROM donations
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
        AND status IN ('verified', 'paid')
        GROUP BY payment_method
        ORDER BY total DESC
    ");
    while ($row = $result->fetch_assoc()) {
        $report['donation_methods'][] = $row;
    }
    
    return $report;
}

// Generate report based on type
$report_data = [];
switch ($report_type) {
    case 'financial':
        $report_data = generateFinancialReport($conn, $start_date, $end_date);
        break;
    case 'healthcare':
        $report_data = generateHealthReport($conn, $start_date, $end_date);
        break;
    case 'donors':
        $report_data = generateDonorReport($conn, $start_date, $end_date);
        break;
    default:
        $report_data = generateFinancialReport($conn, $start_date, $end_date);
}

// Output based on format
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($report_data, JSON_PRETTY_PRINT);
    exit;
}

include 'navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Reports - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-header {
            background: linear-gradient(135deg, #6E8662 0%, #4F6645 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }
        .metric-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        .print-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Report Header -->
    <div class="report-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="bi bi-graph-up"></i> <?= $report_data['title'] ?></h1>
                <p class="lead mb-0">Period: <?= $report_data['period'] ?></p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group">
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['format' => 'json'])) ?>" class="btn btn-light">
                        <i class="bi bi-download"></i> Export JSON
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select">
                        <option value="financial" <?= $report_type == 'financial' ? 'selected' : '' ?>>Financial Report</option>
                        <option value="healthcare" <?= $report_type == 'healthcare' ? 'selected' : '' ?>>Healthcare Report</option>
                        <option value="donors" <?= $report_type == 'donors' ? 'selected' : '' ?>>Donor Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Content -->
    <div class="print-section">
        <?php if ($report_type === 'financial'): ?>
        
        <!-- Financial Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-success">Rs. <?= number_format($report_data['summary']['total_donations']) ?></div>
                    <p class="text-muted mb-0">Total Donations</p>
                    <small><?= $report_data['summary']['donation_count'] ?> transactions</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-danger">Rs. <?= number_format($report_data['summary']['total_expenses']) ?></div>
                    <p class="text-muted mb-0">Total Expenses</p>
                    <small><?= $report_data['summary']['expense_count'] ?> bills</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number <?= $report_data['summary']['net_balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        Rs. <?= number_format(abs($report_data['summary']['net_balance'])) ?>
                    </div>
                    <p class="text-muted mb-0"><?= $report_data['summary']['net_balance'] >= 0 ? 'Net Surplus' : 'Net Deficit' ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-info">Rs. <?= number_format($report_data['summary']['avg_donation']) ?></div>
                    <p class="text-muted mb-0">Average Donation</p>
                </div>
            </div>
        </div>

        <!-- Categories Breakdown -->
        <div class="row">
            <div class="col-md-6">
                <h5><i class="bi bi-cash-coin"></i> Donations by Category</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Target</th>
                                <th>Collected</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['donations_by_category'] as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td>Rs. <?= number_format($category['target_amount']) ?></td>
                                <td>Rs. <?= number_format($category['collected']) ?></td>
                                <td>
                                    <?php $progress = ($category['collected'] / $category['target_amount']) * 100; ?>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: <?= min(100, $progress) ?>%"></div>
                                    </div>
                                    <small><?= number_format($progress, 1) ?>%</small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h5><i class="bi bi-receipt"></i> Expenses by Category</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount Spent</th>
                                <th>Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['expenses_by_category'] as $expense): ?>
                            <tr>
                                <td><?= htmlspecialchars($expense['name']) ?></td>
                                <td>Rs. <?= number_format($expense['spent']) ?></td>
                                <td><?= $expense['count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'healthcare'): ?>
        
        <!-- Healthcare Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-primary"><?= $report_data['summary']['total'] ?></div>
                    <p class="text-muted mb-0">Total Appointments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-success"><?= $report_data['summary']['completed'] ?></div>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-warning"><?= $report_data['summary']['cancelled'] ?></div>
                    <p class="text-muted mb-0">Cancelled</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-info"><?= $report_data['summary']['unique_patients'] ?></div>
                    <p class="text-muted mb-0">Unique Patients</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5><i class="bi bi-person-badge"></i> Appointments by Doctor</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Appointments</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['appointments_by_doctor'] as $doctor): ?>
                            <tr>
                                <td>Dr. <?= htmlspecialchars($doctor['full_name']) ?></td>
                                <td><?= htmlspecialchars($doctor['specialization']) ?></td>
                                <td><?= $doctor['appointments'] ?></td>
                                <td><?= $doctor['completed'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h5><i class="bi bi-heart-pulse"></i> Common Medical Conditions</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Condition</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['medical_conditions'] as $condition): ?>
                            <tr>
                                <td><?= htmlspecialchars($condition['diagnosis']) ?></td>
                                <td><?= $condition['count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($report_type === 'donors'): ?>
        
        <!-- Donor Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-primary"><?= $report_data['summary']['unique_donors'] ?></div>
                    <p class="text-muted mb-0">Unique Donors</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-success"><?= $report_data['summary']['total_donations'] ?></div>
                    <p class="text-muted mb-0">Total Donations</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-info">Rs. <?= number_format($report_data['summary']['total_amount']) ?></div>
                    <p class="text-muted mb-0">Total Amount</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-number text-warning">Rs. <?= number_format($report_data['summary']['avg_donation']) ?></div>
                    <p class="text-muted mb-0">Average Donation</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <h5><i class="bi bi-people"></i> Top Donors</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Donor Name</th>
                                <th>Email</th>
                                <th>Total Donated</th>
                                <th>Donations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['top_donors'] as $donor): ?>
                            <tr>
                                <td><?= htmlspecialchars($donor['donor_name']) ?></td>
                                <td><?= htmlspecialchars($donor['donor_email']) ?></td>
                                <td>Rs. <?= number_format($donor['total_donated']) ?></td>
                                <td><?= $donor['donation_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <h5><i class="bi bi-credit-card"></i> Payment Methods</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Count</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['donation_methods'] as $method): ?>
                            <tr>
                                <td><?= htmlspecialchars($method['payment_method']) ?></td>
                                <td><?= $method['count'] ?></td>
                                <td>Rs. <?= number_format($method['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>