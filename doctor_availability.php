<?php
session_start();
include 'navbar.php';

$servername = "localhost";
$dbusername = "root";
$db_password = "";
$dbname = "monastery_healthcare";

$con = new mysqli($servername, $dbusername, $db_password, $dbname);
if ($con->connect_error) die("Connection failed: " . $con->connect_error);

$error = "";
$success = "";

$days_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Handle CREATE/UPDATE/DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $doctor_id = intval($_POST['doctor_id']);
        $day_of_week = intval($_POST['day_of_week']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $stmt = $con->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("iiss", $doctor_id, $day_of_week, $start_time, $end_time);
        if ($stmt->execute()) {
            $success = "Availability added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'update') {
        $avail_id = intval($_POST['avail_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $day_of_week = intval($_POST['day_of_week']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $con->prepare("UPDATE doctor_availability SET doctor_id=?, day_of_week=?, start_time=?, end_time=?, is_active=? WHERE avail_id=?");
        $stmt->bind_param("iissii", $doctor_id, $day_of_week, $start_time, $end_time, $is_active, $avail_id);
        if ($stmt->execute()) {
            $success = "Availability updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $avail_id = intval($_POST['avail_id']);
        $stmt = $con->prepare("DELETE FROM doctor_availability WHERE avail_id=?");
        $stmt->bind_param("i", $avail_id);
        if ($stmt->execute()) {
            $success = "Availability deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch all availabilities
$avail_query = "SELECT da.*, d.full_name, d.specialization 
                FROM doctor_availability da 
                JOIN doctors d ON da.doctor_id = d.doctor_id 
                ORDER BY d.full_name, da.day_of_week, da.start_time";
$avail_res = $con->query($avail_query);

// Fetch doctors for dropdown
$doctors_res = $con->query("SELECT doctor_id, full_name, specialization FROM doctors WHERE status='active' ORDER BY full_name ASC");
$doctors = [];
while ($d = $doctors_res->fetch_assoc()) {
    $doctors[] = $d;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Availability - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-light: #ffa726;
            --monastery-dark: #e65100;
            --monastery-pale: #fff3e0;
        }
        body {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        }
        .availability-card {
            border-left: 4px solid var(--monastery-saffron);
            transition: transform 0.2s;
            background: white;
        }
        .availability-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .day-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        .time-badge {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .doctor-header {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
        }
        .specialization-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4 mb-5">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="bi bi-calendar-week"></i> Doctor Availability Management</h2>
            <p class="text-muted">Manage weekly schedules for doctors</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Add New Schedule
            </button>
        </div>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Availability Cards -->
    <div class="row">
        <?php 
        $current_doctor = null;
        while($row = $avail_res->fetch_assoc()): 
            if ($current_doctor !== $row['doctor_id']) {
                if ($current_doctor !== null) {
                    echo '</div></div></div>'; // Close previous doctor card
                }
                $current_doctor = $row['doctor_id'];
        ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="doctor-header">
                        <h5 class="mb-1"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($row['full_name']) ?></h5>
                        <span class="specialization-badge"><?= htmlspecialchars($row['specialization']) ?></span>
                    </div>
                    <div class="card-body">
        <?php } ?>
                        <div class="availability-card card mb-2">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <span class="badge bg-primary day-badge">
                                            <?= $days_map[$row['day_of_week']] ?>
                                        </span>
                                    </div>
                                    <div class="col-md-5">
                                        <span class="time-badge">
                                            <i class="bi bi-clock"></i> 
                                            <?= date('g:i A', strtotime($row['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($row['end_time'])) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2">
                                        <?php if($row['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['avail_id'] ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['avail_id'] ?>" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $row['avail_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Availability</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="form_name" value="update">
                                            <input type="hidden" name="avail_id" value="<?= $row['avail_id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Doctor</label>
                                                <select name="doctor_id" class="form-select" required>
                                                    <?php foreach($doctors as $doc): ?>
                                                        <option value="<?= $doc['doctor_id'] ?>" <?= $doc['doctor_id'] == $row['doctor_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($doc['full_name']) ?> - <?= $doc['specialization'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Day of Week</label>
                                                <select name="day_of_week" class="form-select" required>
                                                    <?php foreach($days_map as $idx => $day_name): ?>
                                                        <option value="<?= $idx ?>" <?= $idx == $row['day_of_week'] ? 'selected' : '' ?>><?= $day_name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Start Time</label>
                                                    <input type="time" name="start_time" class="form-control" value="<?= $row['start_time'] ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">End Time</label>
                                                    <input type="time" name="end_time" class="form-control" value="<?= $row['end_time'] ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="active<?= $row['avail_id'] ?>" <?= $row['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="active<?= $row['avail_id'] ?>">Active</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal<?= $row['avail_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this availability?</p>
                                            <p><strong><?= htmlspecialchars($row['full_name']) ?></strong> - 
                                            <?= $days_map[$row['day_of_week']] ?> 
                                            (<?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?>)</p>
                                            <input type="hidden" name="form_name" value="delete">
                                            <input type="hidden" name="avail_id" value="<?= $row['avail_id'] ?>">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

        <?php endwhile; ?>
        <?php if ($current_doctor !== null): ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($avail_res->num_rows == 0): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No availability schedules found. Click "Add New Schedule" to create one.
        </div>
    <?php endif; ?>
</div>

<!-- Add New Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Availability</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">-- Select Doctor --</option>
                            <?php foreach($doctors as $doc): ?>
                                <option value="<?= $doc['doctor_id'] ?>">
                                    <?= htmlspecialchars($doc['full_name']) ?> - <?= $doc['specialization'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="">-- Select Day --</option>
                            <?php foreach($days_map as $idx => $day_name): ?>
                                <option value="<?= $idx ?>"><?= $day_name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <small>Times are in 24-hour format. Schedule will be set as active by default.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Availability</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
