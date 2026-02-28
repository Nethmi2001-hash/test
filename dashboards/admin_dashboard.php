<?php
require_once __DIR__ . '/../includes/db_config.php';
$conn = getDBConnection();

// Get admin dashboard statistics
$stats = [
    'total_monks' => 0,
    'total_doctors' => 0,
    'total_appointments' => 0,
    'total_donations' => 0,
    'pending_appointments' => 0,
    'monthly_donation_amount' => 0,
    'total_expenses' => 0,
    'balance' => 0
];

// Get statistics
$result = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status = 'active'");
if ($result) $stats['total_monks'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'");
if ($result) $stats['total_doctors'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE MONTH(app_date) = MONTH(CURRENT_DATE()) AND YEAR(app_date) = YEAR(CURRENT_DATE())");
if ($result) $stats['total_appointments'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND status IN ('verified', 'paid')");
if ($result) $stats['monthly_donation_amount'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM bills WHERE MONTH(bill_date) = MONTH(CURRENT_DATE()) AND status = 'paid'");
if ($result) $stats['total_expenses'] = $result->fetch_assoc()['total'];

$stats['balance'] = $stats['monthly_donation_amount'] - $stats['total_expenses'];

// Get recent activities
$recent_donations = $conn->query("SELECT donor_name, amount, created_at FROM donations WHERE status IN ('verified', 'paid') ORDER BY created_at DESC LIMIT 5");
$recent_appointments = $conn->query("
    SELECT m.full_name as monk_name, d.full_name as doctor_name, app_date, app_time 
    FROM appointments a
    JOIN monks m ON a.monk_id = m.monk_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.status = 'scheduled' AND a.app_date >= CURRENT_DATE()
    ORDER BY a.app_date, a.app_time LIMIT 5
");

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Admin Dashboard - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/monastery-theme.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #6E8662 0%, #4F6645 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .quick-action {
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }
        .quick-action:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Admin Header -->
    <div class="admin-header text-center">
        <h1><i class="bi bi-person-gear"></i> Admin Dashboard</h1>
        <p class="lead mb-0">Complete system management and oversight</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill text-primary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_monks'] ?></h3>
                    <p class="text-muted mb-0">Active Monks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-badge-fill text-success fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_doctors'] ?></h3>
                    <p class="text-muted mb-0">Active Doctors</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-currency-dollar text-warning fs-1"></i>
                    <h3 class="mt-2">Rs. <?= number_format($stats['monthly_donation_amount']) ?></h3>
                    <p class="text-muted mb-0">Monthly Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-info fs-1"></i>
                    <h3 class="mt-2 <?= $stats['balance'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        Rs. <?= number_format($stats['balance']) ?>
                    </h3>
                    <p class="text-muted mb-0">Monthly Balance</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-lightning-fill"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="monk_management.php" class="btn quick-action">
                            <i class="bi bi-person-plus"></i> Manage Monks
                        </a>
                        <a href="doctor_management.php" class="btn quick-action">
                            <i class="bi bi-person-badge"></i> Manage Doctors
                        </a>
                        <a href="donation_management.php" class="btn quick-action">
                            <i class="bi bi-currency-dollar"></i> Manage Donations
                        </a>
                        <a href="bill_management.php" class="btn quick-action">
                            <i class="bi bi-receipt"></i> Manage Expenses
                        </a>
                        <a href="reports.php" class="btn quick-action">
                            <i class="bi bi-bar-chart"></i> Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Donations -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-heart-fill text-danger"></i> Recent Donations</h5>
                </div>
                <div class="card-body">
                    <?php while($donation = $recent_donations->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <strong><?= htmlspecialchars($donation['donor_name']) ?></strong><br>
                            <small class="text-muted"><?= date('M d, Y', strtotime($donation['created_at'])) ?></small>
                        </div>
                        <span class="badge bg-success">Rs. <?= number_format($donation['amount']) ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-calendar-check"></i> Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    <?php while($appointment = $recent_appointments->fetch_assoc()): ?>
                    <div class="mb-2 pb-2 border-bottom">
                        <strong><?= htmlspecialchars($appointment['monk_name']) ?></strong><br>
                        <small class="text-muted">
                            Dr. <?= htmlspecialchars($appointment['doctor_name']) ?><br>
                            <?= date('M d, Y g:i A', strtotime($appointment['app_date'] . ' ' . $appointment['app_time'])) ?>
                        </small>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>