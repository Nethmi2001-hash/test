<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

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
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #F7F4EE 0%, #E8DCC5 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 2.75rem 3rem;
            border-radius: 20px;
            margin-bottom: 2.5rem;
            box-shadow: 0 15px 45px rgba(79, 102, 69, 0.3),
                        0 5px 15px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -60%;
            right: -15%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.22) 0%, transparent 65%);
            border-radius: 50%;
            animation: pulse 4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.22; }
            50% { transform: scale(1.1); opacity: 0.35; }
        }
        .page-header h2 {
            font-weight: 800;
            font-size: 2rem;
            text-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .report-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08),
                        0 2px 8px rgba(110, 134, 98, 0.05);
            margin-bottom: 2rem;
            border: 1px solid rgba(110, 134, 98, 0.12);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .report-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(110, 134, 98, 0.2), transparent);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .report-card:hover {
            box-shadow: 0 16px 48px rgba(0,0,0,0.12),
                        0 4px 16px rgba(110, 134, 98, 0.15);
            transform: translateY(-6px);
        }
        .report-card:hover::after {
            opacity: 1;
        }
        .report-card h5 {
            color: var(--accent);
            border-left: 5px solid var(--primary);
            padding-left: 18px;
            margin-bottom: 1.75rem;
            font-weight: 800;
            font-size: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 2.25rem 2rem;
            border-radius: 18px;
            text-align: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 12px 36px rgba(79, 102, 69, 0.3),
                        0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.15);
        }
        .stat-badge::before {
            content: '';
            position: absolute;
            top: -60%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.25) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-10px, -10px) rotate(5deg); }
        }
        .stat-badge:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 20px 50px rgba(79, 102, 69, 0.4),
                        0 8px 20px rgba(0,0,0,0.15);
        }
        .stat-badge h3 {
            margin: 0;
            font-size: 2.75rem;
            font-weight: 900;
            text-shadow: 0 3px 12px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }
        .stat-badge p {
            margin: 12px 0 0 0;
            opacity: 0.95;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            position: relative;
            z-index: 2;
        }
        .stat-badge i {
            font-size: 4rem;
            opacity: 0.15;
            position: absolute;
            bottom: -15px;
            right: 20px;
            z-index: 1;
        }
        .table-custom {
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid rgba(110, 134, 98, 0.15);
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }
        .table-custom thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
        }
        .table-custom thead th {
            font-weight: 700;
            border: none;
            padding: 16px 18px;
            font-size: 0.9rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .table-custom tbody td {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(110, 134, 98, 0.08);
            font-size: 0.95rem;
        }
        .table-custom tbody tr {
            transition: all 0.3s ease;
            background: #fff;
        }
        .table-custom tbody tr:hover {
            background: linear-gradient(90deg, rgba(110, 134, 98, 0.08) 0%, rgba(110, 134, 98, 0.03) 100%);
            transform: scale(1.01);
        }
        .btn-export {
            background: linear-gradient(135deg, var(--success) 0%, #1f5323 100%);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
            padding: 11px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.25);
        }
        .btn-export:hover {
            background: linear-gradient(135deg, #1f5323 0%, #164018 100%);
            color: #fff;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 30px rgba(46, 125, 50, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }
        .filter-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid rgba(110, 134, 98, 0.15);
            box-shadow: 0 8px 32px rgba(0,0,0,0.06),
                        inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }
        .filter-section .form-label {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .filter-section .form-control,
        .filter-section .form-select {
            border: 2px solid rgba(110, 134, 98, 0.2);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(110, 134, 98, 0.12);
            background: #fff;
            transform: translateY(-2px);
        }
        .progress-custom {
            height: 24px;
            border-radius: 12px;
            background: rgba(110, 134, 98, 0.1);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-bar-custom {
            background: linear-gradient(90deg, var(--success) 0%, var(--primary) 100%);
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);
            transition: all 0.4s ease;
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
    <div class="alert" style="background: linear-gradient(135deg, rgba(110, 134, 98, 0.08) 0%, rgba(79, 102, 69, 0.05) 100%); border-left: 3px solid var(--primary); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <img src="images/img1.jpeg" alt="Founder" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
        <div style="font-size: 0.875rem; line-height: 1.4;">
            <div style="font-weight: 600; color: var(--primary);">Seela Suwa Herath Bikshu Gilan Arana</div>
            <div style="opacity: 0.75; font-size: 0.8rem;">Founded by Ven. Solewewa Chandrasiri Thero</div>
        </div>
    </div>

    <!-- Date Range Selector -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-calendar-event"></i> Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label"><i class="bi bi-calendar-check"></i> End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label"><i class="bi bi-file-earmark-bar-graph"></i> Report Type</label>
                <select name="report_type" class="form-select">
                    <option value="financial" <?= $report_type == 'financial' ? 'selected' : '' ?>>💰 Financial Summary</option>
                    <option value="appointments" <?= $report_type == 'appointments' ? 'selected' : '' ?>>📅 Appointment Statistics</option>
                    <option value="donors" <?= $report_type == 'donors' ? 'selected' : '' ?>>🤝 Donor Report</option>
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
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--success) 0%, #246229 100%); position: relative;">
                    <i class="bi bi-cash-coin" style="position: absolute; bottom: -10px; right: 15px; font-size: 3rem; opacity: 0.2;"></i>
                    <h3>Rs. <?= number_format($total_donations, 2) ?></h3>
                    <p><i class="bi bi-arrow-up-circle"></i> Total Donations</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--danger) 0%, #8A5A3B 100%); position: relative;">
                    <i class="bi bi-receipt" style="position: absolute; bottom: -10px; right: 15px; font-size: 3rem; opacity: 0.2;"></i>
                    <h3>Rs. <?= number_format($total_expenses, 2) ?></h3>
                    <p><i class="bi bi-arrow-down-circle"></i> Total Expenses</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="stat-badge" style="background: linear-gradient(135deg, <?= ($total_donations - $total_expenses) >= 0 ? 'var(--success), #246229' : 'var(--danger), #8A5A3B' ?>); position: relative;">
                    <i class="bi bi-<?= ($total_donations - $total_expenses) >= 0 ? 'graph-up' : 'graph-down' ?>" style="position: absolute; bottom: -10px; right: 15px; font-size: 3rem; opacity: 0.2;"></i>
                    <h3>Rs. <?= number_format($total_donations - $total_expenses, 2) ?></h3>
                    <p><i class="bi bi-<?= ($total_donations - $total_expenses) >= 0 ? 'check-circle' : 'exclamation-triangle' ?>"></i> Net Balance <?= ($total_donations - $total_expenses) >= 0 ? '(Surplus)' : '(Deficit)' ?></p>
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
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
                    <i class="bi bi-calendar-check" style="position: absolute; bottom: -15px; right: 20px; font-size: 4rem; opacity: 0.15;"></i>
                    <h3><?= $appointment_stats['total'] ?></h3>
                    <p><i class="bi bi-list-check"></i> Total Appointments</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--success) 0%, #1f5323 100%);">
                    <i class="bi bi-check-circle" style="position: absolute; bottom: -15px; right: 20px; font-size: 4rem; opacity: 0.15;"></i>
                    <h3><?= $appointment_stats['completed'] ?></h3>
                    <p><i class="bi bi-check2-all"></i> Completed</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--accent) 0%, #6D4628 100%);">
                    <i class="bi bi-x-circle" style="position: absolute; bottom: -15px; right: 20px; font-size: 4rem; opacity: 0.15;"></i>
                    <h3><?= $appointment_stats['cancelled'] ?></h3>
                    <p><i class="bi bi-x-octagon"></i> Cancelled</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-badge" style="background: linear-gradient(135deg, var(--danger) 0%, #8B1F28 100%);">
                    <i class="bi bi-exclamation-triangle" style="position: absolute; bottom: -15px; right: 20px; font-size: 4rem; opacity: 0.15;"></i>
                    <h3><?= $appointment_stats['no_show'] ?></h3>
                    <p><i class="bi bi-person-x"></i> No Show</p>
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
                '#6E8662',
                '#4F6645',
                '#8A5A3B',
                '#2E7D32',
                '#5D7C51',
                '#9A6D4F'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 13,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                }
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
                '#B4232C',
                '#8A5A3B',
                '#A67951',
                '#C08860',
                '#7A4E34',
                '#9B3036'
            ],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 13,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                cornerRadius: 8,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                }
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
