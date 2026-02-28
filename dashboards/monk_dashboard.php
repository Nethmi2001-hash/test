<?php
require_once __DIR__ . '/../includes/db_config.php';
$conn = getDBConnection();

// Get monk information
$monk_query = $conn->prepare("SELECT * FROM monks WHERE full_name LIKE ? OR phone = ?");
$search_name = '%' . $_SESSION['username'] . '%';
$search_phone = $_SESSION['phone'] ?? '';
$monk_query->bind_param("ss", $search_name, $search_phone);
$monk_query->execute();
$monk_info = $monk_query->get_result()->fetch_assoc();

$monk_id = $monk_info['monk_id'] ?? null;

// Get monk statistics
$stats = [
    'total_appointments' => 0,
    'upcoming_appointments' => 0,
    'medical_records' => 0,
    'last_checkup' => 'Never'
];

if ($monk_id) {
    // Total appointments
    $result = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE monk_id = ?");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $stats['total_appointments'] = $result->get_result()->fetch_assoc()['count'];

    // Upcoming appointments
    $result = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE monk_id = ? AND app_date >= CURRENT_DATE() AND status = 'scheduled'");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $stats['upcoming_appointments'] = $result->get_result()->fetch_assoc()['count'];

    // Medical records count  
    $result = $conn->prepare("SELECT COUNT(*) as count FROM medical_records WHERE monk_id = ?");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $stats['medical_records'] = $result->get_result()->fetch_assoc()['count'];

    // Last checkup
    $result = $conn->prepare("SELECT MAX(app_date) as last_date FROM appointments WHERE monk_id = ? AND status = 'completed'");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $last_checkup = $result->get_result()->fetch_assoc()['last_date'];
    if ($last_checkup) {
        $stats['last_checkup'] = date('M d, Y', strtotime($last_checkup));
    }
}

// Get upcoming appointments
$upcoming_appointments = [];
if ($monk_id) {
    $result = $conn->prepare("
        SELECT a.*, d.full_name as doctor_name, d.specialization 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.monk_id = ? AND a.app_date >= CURRENT_DATE() AND a.status = 'scheduled'
        ORDER BY a.app_date, a.app_time
        LIMIT 5
    ");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $upcoming_appointments = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get recent medical records
$recent_records = [];
if ($monk_id) {
    $result = $conn->prepare("
        SELECT mr.*, d.full_name as doctor_name 
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.monk_id = ?
        ORDER BY mr.record_date DESC
        LIMIT 5
    ");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $recent_records = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monk Dashboard - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/monastery-theme.css">
    <style>
        .monk-header {
            background: linear-gradient(135deg, #8A5A3B 0%, #6E4428 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        .health-card {
            border-left: 4px solid var(--accent);
            transition: transform 0.3s ease;
        }
        .health-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Monk Header -->
    <div class="monk-header text-center">
        <h1><i class="bi bi-person-circle"></i> My Health Dashboard</h1>
        <p class="lead mb-0">Ven. <?= htmlspecialchars($_SESSION['username']) ?></p>
        <?php if ($monk_info): ?>
        <p class="mb-0">
            <?php if ($monk_info['blood_group']): ?>Blood Group: <?= htmlspecialchars($monk_info['blood_group']) ?> | <?php endif; ?>
            Phone: <?= htmlspecialchars($monk_info['phone'] ?? 'Not provided') ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Health Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-check text-primary fs-1"></i>
                    <h3 class="mt-2"><?= $stats['total_appointments'] ?></h3>
                    <p class="text-muted mb-0">Total Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-clock text-warning fs-1"></i>
                    <h3 class="mt-2"><?= $stats['upcoming_appointments'] ?></h3>
                    <p class="text-muted mb-0">Upcoming Appointments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-medical text-success fs-1"></i>
                    <h3 class="mt-2"><?= $stats['medical_records'] ?></h3>
                    <p class="text-muted mb-0">Medical Records</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-heart-pulse text-info fs-1"></i>
                    <h3 class="mt-2 fs-6"><?= $stats['last_checkup'] ?></h3>
                    <p class="text-muted mb-0">Last Checkup</p>
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
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">
                            <i class="bi bi-calendar-plus"></i> Book Appointment
                        </button>
                        <a href="my_medical_records.php" class="btn btn-outline-primary">
                            <i class="bi bi-file-medical"></i> View Medical History
                        </a>
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#healthInfoModal">
                            <i class="bi bi-heart"></i> Update Health Info
                        </button>
                    </div>
                </div>
            </div>

            <!-- Health Information -->
            <?php if ($monk_info && ($monk_info['allergies'] || $monk_info['chronic_conditions'] || $monk_info['current_medications'])): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6><i class="bi bi-exclamation-triangle text-warning"></i> Health Alerts</h6>
                </div>
                <div class="card-body">
                    <?php if ($monk_info['allergies']): ?>
                    <small class="text-danger d-block mb-1">
                        <strong>Allergies:</strong> <?= htmlspecialchars($monk_info['allergies']) ?>
                    </small>
                    <?php endif; ?>
                    <?php if ($monk_info['chronic_conditions']): ?>
                    <small class="text-warning d-block mb-1">
                        <strong>Conditions:</strong> <?= htmlspecialchars($monk_info['chronic_conditions']) ?>
                    </small>
                    <?php endif; ?>
                    <?php if ($monk_info['current_medications']): ?>
                    <small class="text-info d-block">
                        <strong>Medications:</strong> <?= htmlspecialchars($monk_info['current_medications']) ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Appointments -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-calendar-event"></i> Upcoming Appointments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-calendar-x fs-3 mb-2"></i>
                            <p class="mb-0">No upcoming appointments</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                        <div class="card health-card mb-3">
                            <div class="card-body pb-2">
                                <h6 class="mb-1">Dr. <?= htmlspecialchars($appointment['doctor_name']) ?></h6>
                                <small class="text-muted d-block"><?= htmlspecialchars($appointment['specialization']) ?></small>
                                <small class="text-primary">
                                    <i class="bi bi-calendar"></i> <?= date('M d, Y g:i A', strtotime($appointment['app_date'] . ' ' . $appointment['app_time'])) ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Medical Records -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-medical-fill"></i> Recent Medical Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_records)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-file-medical fs-3 mb-2"></i>
                            <p class="mb-0">No medical records yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_records as $record): ?>
                        <div class="card health-card mb-3">
                            <div class="card-body pb-2">
                                <h6 class="mb-1">Dr. <?= htmlspecialchars($record['doctor_name']) ?></h6>
                                <small class="text-muted d-block "><?= date('M d, Y', strtotime($record['record_date'])) ?></small>
                                <?php if ($record['diagnosis']): ?>
                                <small class="text-info d-block">Diagnosis: <?= htmlspecialchars(substr($record['diagnosis'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Book Appointment Modal -->
<div class="modal fade" id="bookAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book New Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>To book an appointment, please contact the healthcare office or visit the patient appointments page.</p>
                <div class="text-center">
                    <a href="patient_appointments.php" class="btn btn-primary">Go to Appointments</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>