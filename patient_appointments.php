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

$status_colors = [
    'scheduled' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger',
    'no-show' => 'secondary'
];

// Handle CREATE / UPDATE / DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $monk_id = intval($_POST['monk_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $room_slot_id = !empty($_POST['room_slot_id']) ? intval($_POST['room_slot_id']) : null;
        $app_date = $_POST['app_date'];
        $app_time = $_POST['app_time'];
        $notes = $_POST['notes'] ?? '';
        $created_by = $_SESSION['user_id'] ?? null;

        if ($room_slot_id) {
            $stmt = $con->prepare("INSERT INTO appointments (monk_id, doctor_id, room_slot_id, app_date, app_time, status, notes, created_by) VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)");
            $stmt->bind_param("iiisssi", $monk_id, $doctor_id, $room_slot_id, $app_date, $app_time, $notes, $created_by);
        } else {
            $stmt = $con->prepare("INSERT INTO appointments (monk_id, doctor_id, app_date, app_time, status, notes, created_by) VALUES (?, ?, ?, ?, 'scheduled', ?, ?)");
            $stmt->bind_param("iisssi", $monk_id, $doctor_id, $app_date, $app_time, $notes, $created_by);
        }
        
        if ($stmt->execute()) {
            $success = "Appointment created successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'update') {
        $app_id = intval($_POST['app_id']);
        $monk_id = intval($_POST['monk_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $room_slot_id = !empty($_POST['room_slot_id']) ? intval($_POST['room_slot_id']) : null;
        $app_date = $_POST['app_date'];
        $app_time = $_POST['app_time'];
        $status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';

        if ($room_slot_id) {
            $stmt = $con->prepare("UPDATE appointments SET monk_id=?, doctor_id=?, room_slot_id=?, app_date=?, app_time=?, status=?, notes=? WHERE app_id=?");
            $stmt->bind_param("iiissssi", $monk_id, $doctor_id, $room_slot_id, $app_date, $app_time, $status, $notes, $app_id);
        } else {
            $stmt = $con->prepare("UPDATE appointments SET monk_id=?, doctor_id=?, room_slot_id=NULL, app_date=?, app_time=?, status=?, notes=? WHERE app_id=?");
            $stmt->bind_param("iissssi", $monk_id, $doctor_id, $app_date, $app_time, $status, $notes, $app_id);
        }
        
        if ($stmt->execute()) {
            $success = "Appointment updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $app_id = intval($_POST['app_id']);
        $stmt = $con->prepare("DELETE FROM appointments WHERE app_id=?");
        $stmt->bind_param("i", $app_id);
        if ($stmt->execute()) {
            $success = "Appointment deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch appointments with all details
$appointments_query = "
    SELECT 
        a.*,
        m.full_name AS monk_name,
        d.full_name AS doctor_name,
        d.specialization,
        r.name AS room_name,
        u.name AS created_by_name
    FROM appointments a
    JOIN monks m ON a.monk_id = m.monk_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN room_slots rs ON a.room_slot_id = rs.room_slot_id
    LEFT JOIN rooms r ON rs.room_id = r.room_id
    LEFT JOIN users u ON a.created_by = u.user_id
    ORDER BY a.app_date DESC, a.app_time DESC
";
$appointments_res = $con->query($appointments_query);

// Fetch monks for dropdown
$monks_res = $con->query("SELECT monk_id, full_name FROM monks WHERE status='active' ORDER BY full_name ASC");
$monks = [];
while ($m = $monks_res->fetch_assoc()) {
    $monks[] = $m;
}

// Fetch doctors for dropdown
$doctors_res = $con->query("SELECT doctor_id, full_name, specialization FROM doctors WHERE status='active' ORDER BY full_name ASC");
$doctors = [];
while ($d = $doctors_res->fetch_assoc()) {
    $doctors[] = $d;
}

// Fetch room slots for dropdown
$rooms_res = $con->query("
    SELECT rs.room_slot_id, r.name, rs.day_of_week, rs.start_time, rs.end_time
    FROM room_slots rs
    JOIN rooms r ON rs.room_id = r.room_id
    WHERE rs.is_active = 1
    ORDER BY r.name, rs.day_of_week, rs.start_time
");
$room_slots = [];
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
while ($rs = $rooms_res->fetch_assoc()) {
    $room_slots[] = [
        'id' => $rs['room_slot_id'],
        'label' => $rs['name'] . ' - ' . $days[$rs['day_of_week']] . ' ' . 
                   date('g:i A', strtotime($rs['start_time'])) . '-' . 
                   date('g:i A', strtotime($rs['end_time']))
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Appointment Management - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
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
        .appointment-card {
            transition: transform 0.2s;
            border-left: 4px solid var(--monastery-saffron);
            background: white;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 124, 0, 0.2);
        }
        .page-header {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(245, 124, 0, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
        }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-calendar-check"></i> Appointment Management</h2>
                <p class="mb-0 mt-1 opacity-75">Schedule and manage monk appointments with doctors</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> New Appointment
                </button>
            </div>
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

    <!-- Appointments Grid -->
    <div class="row">
        <?php if ($appointments_res->num_rows == 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No appointments found. Click "New Appointment" to create one.
                </div>
            </div>
        <?php endif; ?>

        <?php while($row = $appointments_res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card appointment-card shadow-sm h-100">
                    <div class="card-header bg-<?= $status_colors[$row['status']] ?> text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar-event"></i> <?= date('M d, Y', strtotime($row['app_date'])) ?></span>
                            <span class="badge bg-light text-dark"><?= date('g:i A', strtotime($row['app_time'])) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-person"></i> Monk</h6>
                        <p class="mb-3"><strong><?= htmlspecialchars($row['monk_name']) ?></strong></p>

                        <h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-person-badge"></i> Doctor</h6>
                        <p class="mb-1"><strong><?= htmlspecialchars($row['doctor_name']) ?></strong></p>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($row['specialization']) ?></p>

                        <?php if($row['room_name']): ?>
                            <h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-door-open"></i> Room</h6>
                            <p class="mb-3"><?= htmlspecialchars($row['room_name']) ?></p>
                        <?php endif; ?>

                        <?php if($row['notes']): ?>
                            <h6 class="card-subtitle mb-2 text-muted"><i class="bi bi-card-text"></i> Notes</h6>
                            <p class="small mb-3"><?= htmlspecialchars($row['notes']) ?></p>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="badge bg-<?= $status_colors[$row['status']] ?>"><?= ucfirst($row['status']) ?></span>
                            <div>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['app_id'] ?>" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['app_id'] ?>" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php if($row['created_by_name']): ?>
                        <div class="card-footer text-muted small">
                            <i class="bi bi-person-circle"></i> Created by: <?= htmlspecialchars($row['created_by_name']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['app_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Appointment</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="form_name" value="update">
                                <input type="hidden" name="app_id" value="<?= $row['app_id'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Monk</label>
                                    <select name="monk_id" class="form-select" required>
                                        <?php foreach($monks as $monk): ?>
                                            <option value="<?= $monk['monk_id'] ?>" <?= $monk['monk_id'] == $row['monk_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($monk['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Doctor</label>
                                    <select name="doctor_id" class="form-select" required>
                                        <?php foreach($doctors as $doctor): ?>
                                            <option value="<?= $doctor['doctor_id'] ?>" <?= $doctor['doctor_id'] == $row['doctor_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($doctor['full_name']) ?> - <?= $doctor['specialization'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Room Slot (Optional)</label>
                                    <select name="room_slot_id" class="form-select">
                                        <option value="">-- No Room --</option>
                                        <?php foreach($room_slots as $slot): ?>
                                            <option value="<?= $slot['id'] ?>" <?= $slot['id'] == $row['room_slot_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($slot['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date</label>
                                        <input type="date" name="app_date" class="form-control" value="<?= $row['app_date'] ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Time</label>
                                        <input type="time" name="app_time" class="form-control" value="<?= $row['app_time'] ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="scheduled" <?= $row['status'] == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="no-show" <?= $row['status'] == 'no-show' ? 'selected' : '' ?>>No-Show</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($row['notes']) ?></textarea>
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
            <div class="modal fade" id="deleteModal<?= $row['app_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this appointment?</p>
                                <ul class="list-unstyled">
                                    <li><strong>Monk:</strong> <?= htmlspecialchars($row['monk_name']) ?></li>
                                    <li><strong>Doctor:</strong> <?= htmlspecialchars($row['doctor_name']) ?></li>
                                    <li><strong>Date:</strong> <?= date('M d, Y', strtotime($row['app_date'])) ?> at <?= date('g:i A', strtotime($row['app_time'])) ?></li>
                                </ul>
                                <input type="hidden" name="form_name" value="delete">
                                <input type="hidden" name="app_id" value="<?= $row['app_id'] ?>">
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
    </div>
</div>

<!-- Add New Appointment Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Monk</label>
                        <select name="monk_id" class="form-select" required>
                            <option value="">-- Select Monk --</option>
                            <?php foreach($monks as $monk): ?>
                                <option value="<?= $monk['monk_id'] ?>">
                                    <?= htmlspecialchars($monk['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Doctor</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">-- Select Doctor --</option>
                            <?php foreach($doctors as $doctor): ?>
                                <option value="<?= $doctor['doctor_id'] ?>">
                                    <?= htmlspecialchars($doctor['full_name']) ?> - <?= $doctor['specialization'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Slot (Optional)</label>
                        <select name="room_slot_id" class="form-select">
                            <option value="">-- No Room --</option>
                            <?php foreach($room_slots as $slot): ?>
                                <option value="<?= $slot['id'] ?>">
                                    <?= htmlspecialchars($slot['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="app_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time</label>
                            <input type="time" name="app_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <small>Appointment will be created with "Scheduled" status.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
