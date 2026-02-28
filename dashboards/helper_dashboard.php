<?php
require_once __DIR__ . '/../includes/db_config.php';
$conn = getDBConnection();

// Get helper statistics
$stats = [
    'appointments_today' => 0,
    'pending_donations' => 0,
    'recent_patients' => 0,
    'tasks_completed' => 0
];

// Today's appointments
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE app_date = CURRENT_DATE()");
if ($result) $stats['appointments_today'] = $result->fetch_assoc()['count'];

// Pending donations
$result = $conn->query("SELECT COUNT(*) as count FROM donations WHERE status = 'pending'");
if ($result) $stats['pending_donations'] = $result->fetch_assoc()['count'];

// Recent patients (this week)
$result = $conn->query("SELECT COUNT(DISTINCT monk_id) as count FROM appointments WHERE WEEK(app_date) = WEEK(CURRENT_DATE())");
if ($result) $stats['recent_patients'] = $result->fetch_assoc()['count'];

// Today's appointments details
$todays_appointments = $conn->query("
    SELECT a.*, m.full_name as monk_name, d.full_name as doctor_name
    FROM appointments a
    JOIN monks m ON a.monk_id = m.monk_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.app_date = CURRENT_DATE()
    ORDER BY a.app_time
")->fetch_all(MYSQLI_ASSOC);

// Recent donations needing verification
$pending_donations = $conn->query("
    SELECT d.*, c.name as category_name 
    FROM donations d
    JOIN categories c ON d.category_id = c.category_id
    WHERE d.status = 'pending'
    ORDER BY d.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helper Dashboard - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/monastery-theme.css">
    <style>
        .helper-header {
            background: linear-gradient(135deg, #6f42c1 0%, #5a379c 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .task-card {
            border-left: 4px solid var(--bs-warning);
            transition: transform 0.3s ease;
        }
        .task-card:hover {
            transform: translateX(5px);
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Helper Header -->
    <div class="helper-header text-center">
        <h1><i class="bi bi-person-gear"></i> Helper Dashboard</h1>
        <p class="lead mb-0"><?= htmlspecialchars($_SESSION['username']) ?></p>
        <p class="mb-0">Administrative Support & Patient Care</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-day text-primary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['appointments_today'] ?></h3>
                    <p class="text-muted mb-0">Today's Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock text-warning fs-1"></i>
                    <h3 class="mt-2"><?= $stats['pending_donations'] ?></h3>
                    <p class="text-muted mb-0">Pending Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people text-success fs-1"></i>
                    <h3 class="mt-2"><?= $stats['recent_patients'] ?></h3>
                    <p class="text-muted mb-0">Patients This Week</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle text-info fs-1"></i>
                    <h3 class="mt-2"><?= $stats['tasks_completed'] ?></h3>
                    <p class="text-muted mb-0">Tasks Today</p>
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
                        <a href="patient_appointments.php" class="btn btn-primary">
                            <i class="bi bi-calendar-plus"></i> Schedule Appointment
                        </a>
                        <a href="donation_management.php" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Verify Donations
                        </a>
                        <a href="monk_management.php" class="btn btn-info">
                            <i class="bi bi-people"></i> Update Patient Info
                        </a>
                        <a href="generate_receipt.php" class="btn btn-success">
                            <i class="bi bi-file-earmark-pdf"></i> Generate Receipts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-calendar-day"></i> Today's Schedule</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($todays_appointments)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-calendar-x fs-3 mb-2"></i>
                            <p class="mb-0">No appointments today</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todays_appointments as $appointment): ?>
                        <div class="card task-card mb-2">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($appointment['monk_name']) ?></h6>
                                        <small class="text-muted">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="fw-bold"><?= date('g:i A', strtotime($appointment['app_time'])) ?></small><br>
                                        <span class="badge bg-<?= $appointment['status'] == 'scheduled' ? 'warning' : 'success' ?>">
                                            <?= ucfirst($appointment['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Donations -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-exclamation-triangle text-warning"></i> Pending Donations</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($pending_donations)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle fs-3 mb-2"></i>
                            <p class="mb-0">All donations verified!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_donations as $donation): ?>
                        <div class="card task-card mb-2">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($donation['donor_name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($donation['category_name']) ?></small><br>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($donation['created_at'])) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-success">Rs. <?= number_format($donation['amount']) ?></span><br>
                                        <a href="donation_management.php?verify=<?= $donation['donation_id'] ?>" class="btn btn-sm btn-warning">
                                            Verify
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>