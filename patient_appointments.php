<?php
session_start();

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

// Compute stats
$total_count = $appointments_res->num_rows;
$all_appointments = [];
$stat_scheduled = 0;
$stat_completed = 0;
$stat_cancelled = 0;
while ($row = $appointments_res->fetch_assoc()) {
    $all_appointments[] = $row;
    if ($row['status'] === 'scheduled') $stat_scheduled++;
    elseif ($row['status'] === 'completed') $stat_completed++;
    elseif ($row['status'] === 'cancelled') $stat_cancelled++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
<?php include 'navbar.php'; ?>

    <?php if($error): ?>
        <div class="alert-modern alert-danger-modern">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert-modern alert-success-modern">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Total Appointments</span>
                    <span class="stat-value"><?= $total_count ?></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Scheduled</span>
                    <span class="stat-value"><?= $stat_scheduled ?></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon emerald"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?= $stat_completed ?></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon rose"><i class="bi bi-x-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Cancelled</span>
                    <span class="stat-value"><?= $stat_cancelled ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Search Section -->
    <div id="advanced-search" data-type="appointments" class="mb-4"></div>

    <!-- Appointments Table -->
    <div id="appointments-list">
        <div class="modern-table-wrapper">
            <div class="modern-table-header">
                <h5><i class="bi bi-calendar-check"></i> Appointment Management</h5>
                <button class="btn-modern btn-primary-modern" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> New Appointment
                </button>
            </div>
            <div class="table-responsive-modern">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Monk</th>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Room</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($all_appointments) == 0): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle"></i> No appointments found. Click "New Appointment" to create one.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach($all_appointments as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($row['app_date'])) ?></strong><br>
                                    <small class="text-muted"><?= date('g:i A', strtotime($row['app_time'])) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['monk_name']) ?></td>
                                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($row['specialization']) ?></td>
                                <td><?= $row['room_name'] ? htmlspecialchars($row['room_name']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $row['notes'] ? htmlspecialchars($row['notes']) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php
                                        $badge_class = 'badge-neutral';
                                        if ($row['status'] === 'scheduled') $badge_class = 'badge-primary';
                                        elseif ($row['status'] === 'completed') $badge_class = 'badge-success';
                                        elseif ($row['status'] === 'cancelled') $badge_class = 'badge-danger';
                                        elseif ($row['status'] === 'no-show') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge-modern <?= $badge_class ?> badge-dot"><?= ucfirst($row['status']) ?></span>
                                </td>
                                <td><?= $row['created_by_name'] ? htmlspecialchars($row['created_by_name']) : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <button class="btn-icon" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['app_id'] ?>" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn-icon danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['app_id'] ?>" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['app_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Appointment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="form_name" value="update">
                                                <input type="hidden" name="app_id" value="<?= $row['app_id'] ?>">
                                                
                                                <div class="form-group-modern">
                                                    <label class="form-label-modern">Monk</label>
                                                    <select name="monk_id" class="form-select-modern" required>
                                                        <?php foreach($monks as $monk): ?>
                                                            <option value="<?= $monk['monk_id'] ?>" <?= $monk['monk_id'] == $row['monk_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($monk['full_name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label-modern">Doctor</label>
                                                    <select name="doctor_id" class="form-select-modern" required>
                                                        <?php foreach($doctors as $doctor): ?>
                                                            <option value="<?= $doctor['doctor_id'] ?>" <?= $doctor['doctor_id'] == $row['doctor_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($doctor['full_name']) ?> - <?= $doctor['specialization'] ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label-modern">Room Slot (Optional)</label>
                                                    <select name="room_slot_id" class="form-select-modern">
                                                        <option value="">-- No Room --</option>
                                                        <?php foreach($room_slots as $slot): ?>
                                                            <option value="<?= $slot['id'] ?>" <?= $slot['id'] == $row['room_slot_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($slot['label']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group-modern">
                                                            <label class="form-label-modern">Date</label>
                                                            <input type="date" name="app_date" class="form-control-modern" value="<?= $row['app_date'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group-modern">
                                                            <label class="form-label-modern">Time</label>
                                                            <input type="time" name="app_time" class="form-control-modern" value="<?= $row['app_time'] ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label-modern">Status</label>
                                                    <select name="status" class="form-select-modern" required>
                                                        <option value="scheduled" <?= $row['status'] == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                        <option value="no-show" <?= $row['status'] == 'no-show' ? 'selected' : '' ?>>No-Show</option>
                                                    </select>
                                                </div>

                                                <div class="form-group-modern">
                                                    <label class="form-label-modern">Notes</label>
                                                    <textarea name="notes" class="form-control-modern" rows="3"><?= htmlspecialchars($row['notes']) ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn-modern btn-outline-modern" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn-modern btn-primary-modern">Save Changes</button>
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
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                                <button type="button" class="btn-modern btn-outline-modern" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn-modern" style="background:#ef4444;color:#fff;">Yes, Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div><!-- END appointments-list -->

<!-- Add New Appointment Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="form-group-modern">
                        <label class="form-label-modern">Monk</label>
                        <select name="monk_id" class="form-select-modern" required>
                            <option value="">-- Select Monk --</option>
                            <?php foreach($monks as $monk): ?>
                                <option value="<?= $monk['monk_id'] ?>">
                                    <?= htmlspecialchars($monk['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Doctor</label>
                        <select name="doctor_id" class="form-select-modern" required>
                            <option value="">-- Select Doctor --</option>
                            <?php foreach($doctors as $doctor): ?>
                                <option value="<?= $doctor['doctor_id'] ?>">
                                    <?= htmlspecialchars($doctor['full_name']) ?> - <?= $doctor['specialization'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Room Slot (Optional)</label>
                        <select name="room_slot_id" class="form-select-modern">
                            <option value="">-- No Room --</option>
                            <?php foreach($room_slots as $slot): ?>
                                <option value="<?= $slot['id'] ?>">
                                    <?= htmlspecialchars($slot['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Date</label>
                                <input type="date" name="app_date" class="form-control-modern" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-modern">
                                <label class="form-label-modern">Time</label>
                                <input type="time" name="app_time" class="form-control-modern" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Notes (Optional)</label>
                        <textarea name="notes" class="form-control-modern" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>

                    <div class="alert-modern alert-success-modern">
                        <i class="bi bi-info-circle"></i> <small>Appointment will be created with "Scheduled" status.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modern btn-outline-modern" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-modern btn-primary-modern">Create Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Advanced Search System -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/advanced-search.js"></script>
<script>
// Initialize Advanced Search for Appointments
window.addEventListener('load', function() {
    new AdvancedSearch('appointments');
});
</script>

</body>
</html>
