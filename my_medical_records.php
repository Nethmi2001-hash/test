<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

// Get monk information
$monk_query = $conn->prepare("SELECT * FROM monks WHERE full_name LIKE ? OR phone = ?");
$search_name = '%' . $_SESSION['username'] . '%';
$search_phone = $_SESSION['phone'] ?? '';
$monk_query->bind_param("ss", $search_name, $search_phone);
$monk_query->execute();
$monk_info = $monk_query->get_result()->fetch_assoc();

$monk_id = $monk_info['monk_id'] ?? null;

// Get medical records
$medical_records = [];
if ($monk_id) {
    $result = $conn->prepare("
        SELECT mr.*, d.full_name as doctor_name, d.specialization 
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.monk_id = ?
        ORDER BY mr.record_date DESC, mr.created_at DESC
    ");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $medical_records = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get appointment history
$appointments = [];
if ($monk_id) {
    $result = $conn->prepare("
        SELECT a.*, d.full_name as doctor_name, d.specialization 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.monk_id = ?
        ORDER BY a.app_date DESC, a.app_time DESC
    ");
    $result->bind_param("i", $monk_id);
    $result->execute();
    $appointments = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Medical Records - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <style>
        .health-timeline {
            border-left: 3px solid var(--primary);
            margin-left: 2rem;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid white;
        }
        .record-card {
            transition: transform 0.2s ease;
        }
        .record-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-file-medical text-primary"></i> My Medical Records</h2>
            </div>

            <?php if (!$monk_id): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                Monk profile not found. Please contact administrator to setup your profile.
            </div>
            <?php else: ?>

            <!-- Health Summary -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-person-circle"></i> Health Profile</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($monk_info['full_name']) ?></p>
                                    <p><strong>Blood Group:</strong> <?= htmlspecialchars($monk_info['blood_group'] ?? 'Not specified') ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($monk_info['phone'] ?? 'Not provided') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($monk_info['birth_date']): ?>
                                    <p><strong>Age:</strong> <?= date('Y') - date('Y', strtotime($monk_info['birth_date'])) ?> years</p>
                                    <?php endif; ?>
                                    <p><strong>Emergency Contact:</strong> <?= htmlspecialchars($monk_info['emergency_contact'] ?? 'Not provided') ?></p>
                                </div>
                            </div>

                            <?php if ($monk_info['allergies'] || $monk_info['chronic_conditions'] || $monk_info['current_medications']): ?>
                            <hr>
                            <div class="row">
                                <?php if ($monk_info['allergies']): ?>
                                <div class="col-md-4">
                                    <h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Allergies</h6>
                                    <p class="small"><?= htmlspecialchars($monk_info['allergies']) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($monk_info['chronic_conditions']): ?>
                                <div class="col-md-4">
                                    <h6 class="text-warning"><i class="bi bi-heart-pulse"></i> Chronic Conditions</h6>
                                    <p class="small"><?= htmlspecialchars($monk_info['chronic_conditions']) ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($monk_info['current_medications']): ?>
                                <div class="col-md-4">
                                    <h6 class="text-info"><i class="bi bi-capsule"></i> Current Medications</h6>
                                    <p class="small"><?= htmlspecialchars($monk_info['current_medications']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6><i class="bi bi-graph-up"></i> Health Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="mb-3">
                                    <h4 class="text-primary"><?= count($medical_records) ?></h4>
                                    <small class="text-muted">Total Medical Records</small>
                                </div>
                                <div class="mb-3">
                                    <h4 class="text-success"><?= count($appointments) ?></h4>
                                    <small class="text-muted">Total Appointments</small>
                                </div>
                                <?php 
                                $recent_visit = array_filter($appointments, fn($a) => $a['status'] == 'completed');
                                $last_visit = !empty($recent_visit) ? reset($recent_visit) : null;
                                ?>
                                <?php if ($last_visit): ?>
                                <div class="mb-3">
                                    <h6 class="text-info"><?= date('M d, Y', strtotime($last_visit['app_date'])) ?></h6>
                                    <small class="text-muted">Last Visit</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records Timeline -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Medical History Timeline</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($medical_records)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-file-medical fs-1 mb-3"></i>
                        <h5>No Medical Records Yet</h5>
                        <p>Your medical records will appear here after doctor consultations.</p>
                    </div>
                    <?php else: ?>
                    <div class="health-timeline">
                        <?php foreach ($medical_records as $record): ?>
                        <div class="timeline-item">
                            <div class="card record-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6 class="card-title">
                                                <i class="bi bi-calendar-event text-primary"></i>
                                                <?= date('F j, Y', strtotime($record['record_date'])) ?>
                                            </h6>
                                            <h6 class="text-muted">
                                                <i class="bi bi-person-badge"></i>
                                                Dr. <?= htmlspecialchars($record['doctor_name']) ?>
                                                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($record['specialization']) ?></span>
                                            </h6>

                                            <div class="mt-3">
                                                <h6 class="text-info"><i class="bi bi-clipboard2-pulse"></i> Diagnosis:</h6>
                                                <p class="mb-2"><?= htmlspecialchars($record['diagnosis']) ?></p>

                                                <?php if ($record['prescription']): ?>
                                                <h6 class="text-success"><i class="bi bi-capsule"></i> Prescription:</h6>
                                                <p class="mb-2"><?= htmlspecialchars($record['prescription']) ?></p>
                                                <?php endif; ?>

                                                <?php if ($record['notes']): ?>
                                                <h6 class="text-secondary"><i class="bi bi-sticky"></i> Notes:</h6>
                                                <p class="mb-2"><?= htmlspecialchars($record['notes']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">
                                                Record created:<br>
                                                <?= date('M d, Y g:i A', strtotime($record['created_at'])) ?>
                                            </small>

                                            <?php if ($record['follow_up_date']): ?>
                                            <div class="mt-3">
                                                <span class="badge bg-<?= strtotime($record['follow_up_date']) < time() ? 'danger' : 'warning' ?> fs-6">
                                                    <i class="bi bi-calendar-check"></i>
                                                    Follow-up: <?= date('M d, Y', strtotime($record['follow_up_date'])) ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; // End monk_id check ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>