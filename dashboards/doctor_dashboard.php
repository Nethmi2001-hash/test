<?php
require_once __DIR__ . '/../includes/db_config.php';
$conn = getDBConnection();

// Get doctor's information
$doctor_query = $conn->prepare("SELECT * FROM doctors WHERE email = ? OR full_name LIKE ?");
$search_name = '%' . $_SESSION['username'] . '%';
$doctor_query->bind_param("ss", $_SESSION['email'], $search_name);
$doctor_query->execute();
$doctor_info = $doctor_query->get_result()->fetch_assoc();

$doctor_id = $doctor_info['doctor_id'] ?? null;

// Get doctor statistics
$stats = [
    'todays_appointments' => 0,
    'weekly_appointments' => 0,
    'total_patients' => 0,
    'pending_appointments' => 0
];

if ($doctor_id) {
    // Today's appointments
    $result = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND app_date = CURRENT_DATE()");
    $result->bind_param("i", $doctor_id);
    $result->execute();
    $stats['todays_appointments'] = $result->get_result()->fetch_assoc()['count'];

    // This week's appointments  
    $result = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND WEEK(app_date) = WEEK(CURRENT_DATE())");
    $result->bind_param("i", $doctor_id);
    $result->execute();
    $stats['weekly_appointments'] = $result->get_result()->fetch_assoc()['count'];

    // Total unique patients
    $result = $conn->prepare("SELECT COUNT(DISTINCT monk_id) as count FROM appointments WHERE doctor_id = ?");
    $result->bind_param("i", $doctor_id);
    $result->execute();
    $stats['total_patients'] = $result->get_result()->fetch_assoc()['count'];
}

// Get today's appointments
$todays_appointments = [];
if ($doctor_id) {
    $result = $conn->prepare("
        SELECT a.*, m.full_name as monk_name, m.phone as monk_phone
        FROM appointments a
        JOIN monks m ON a.monk_id = m.monk_id
        WHERE a.doctor_id = ? AND a.app_date = CURRENT_DATE()
        ORDER BY a.app_time ASC
    ");
    $result->bind_param("i", $doctor_id);
    $result->execute();
    $todays_appointments = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/monastery-theme.css">
    <style>
        .doctor-header {
            background: linear-gradient(135deg, #0D6EFD 0%, #0B5ED7 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .appointment-card {
            border-left: 4px solid var(--bs-primary);
            transition: transform 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateX(5px);
        }
        .status-scheduled { border-left-color: #ffc107; }
        .status-completed { border-left-color: #198754; }
        .status-cancelled { border-left-color: #dc3545; }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Doctor Header -->
    <div class="doctor-header text-center">
        <h1><i class="bi bi-person-badge-fill"></i> Doctor Dashboard</h1>
        <p class="lead mb-0">Dr. <?= htmlspecialchars($_SESSION['username']) ?></p>
        <?php if ($doctor_info): ?>
        <p class="mb-0">Specialization: <?= htmlspecialchars($doctor_info['specialization']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-day text-primary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['todays_appointments'] ?></h3>
                    <p class="text-muted mb-0">Today's Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-week text-success fs-1"></i>
                    <h3 class="mt-2"><?= $stats['weekly_appointments'] ?></h3>
                    <p class="text-muted mb-0">This Week</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people text-info fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_patients'] ?></h3>
                    <p class="text-muted mb-0">Total Patients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock text-warning fs-1"></i>
                    <h3 class="mt-2"><?= count(array_filter($todays_appointments, fn($a) => $a['status'] == 'scheduled')) ?></h3>
                    <p class="text-muted mb-0">Pending Today</p>
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
                        <a href="medical_records.php" class="btn btn-primary">
                            <i class="bi bi-file-medical"></i> Add Medical Record
                        </a>
                        <a href="patient_appointments.php" class="btn btn-outline-primary">
                            <i class="bi bi-calendar-check"></i> View All Appointments
                        </a>
                        <a href="doctor_availability.php" class="btn btn-outline-primary">
                            <i class="bi bi-clock-history"></i> Manage Availability
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="bi bi-calendar-day"></i> Today's Schedule</h5>
                    <span class="badge bg-primary"><?= count($todays_appointments) ?> appointments</span>
                </div>
                <div class="card-body">
                    <?php if (empty($todays_appointments)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x fs-1 mb-3"></i>
                            <p>No appointments scheduled for today</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todays_appointments as $appointment): ?>
                        <div class="card appointment-card status-<?= $appointment['status'] ?> mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <h6 class="mb-1"><?= date('g:i A', strtotime($appointment['app_time'])) ?></h6>
                                        <span class="badge bg-<?= $appointment['status'] == 'scheduled' ? 'warning' : ($appointment['status'] == 'completed' ? 'success' : 'danger') ?>">
                                            <?= ucfirst($appointment['status']) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?= htmlspecialchars($appointment['monk_name']) ?></h6>
                                        <small class="text-muted">
                                            <?= $appointment['monk_phone'] ? 'Phone: ' . htmlspecialchars($appointment['monk_phone']) : '' ?>
                                        </small>
                                        <?php if ($appointment['notes']): ?>
                                        <br><small class="text-muted">Notes: <?= htmlspecialchars($appointment['notes']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <?php if ($appointment['status'] == 'scheduled'): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-success" onclick="completeAppointment(<?= $appointment['app_id'] ?>)">
                                                <i class="bi bi-check"></i> Complete
                                            </button>
                                            <a href="medical_records.php?monk_id=<?= $appointment['monk_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-file-medical"></i> Records  
                                            </a>
                                        </div>
                                        <?php endif; ?>
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
<script>
function completeAppointment(appId) {
    if (confirm('Mark this appointment as completed?')) {
        fetch('api/update_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                app_id: appId,
                status: 'completed'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating appointment');
            }
        });
    }
}
</script>
</body>
</html>