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

// Handle CREATE / UPDATE / DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $room_id = intval($_POST['room_id']);
        $day_of_week = intval($_POST['day_of_week']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        if (strtotime($end_time) <= strtotime($start_time)) {
            $error = "End time must be after start time.";
        } else {
            $stmt = $con->prepare("INSERT INTO room_slots (room_id, day_of_week, start_time, end_time, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("iiss", $room_id, $day_of_week, $start_time, $end_time);
            if ($stmt->execute()) {
                $success = "Room slot added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'update') {
        $room_slot_id = intval($_POST['room_slot_id']);
        $room_id = intval($_POST['room_id']);
        $day_of_week = intval($_POST['day_of_week']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (strtotime($end_time) <= strtotime($start_time)) {
            $error = "End time must be after start time.";
        } else {
            $stmt = $con->prepare("UPDATE room_slots SET room_id=?, day_of_week=?, start_time=?, end_time=?, is_active=? WHERE room_slot_id=?");
            $stmt->bind_param("iissii", $room_id, $day_of_week, $start_time, $end_time, $is_active, $room_slot_id);
            if ($stmt->execute()) {
                $success = "Room slot updated successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'delete') {
        $room_slot_id = intval($_POST['room_slot_id']);
        $stmt = $con->prepare("DELETE FROM room_slots WHERE room_slot_id=?");
        $stmt->bind_param("i", $room_slot_id);
        if ($stmt->execute()) {
            $success = "Room slot deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch room slots with room info
$slots_query = "SELECT rs.*, r.name as room_name, r.type 
                FROM room_slots rs 
                JOIN rooms r ON rs.room_id = r.room_id 
                ORDER BY r.name, rs.day_of_week, rs.start_time";
$slots_res = $con->query($slots_query);

// Fetch rooms for dropdown
$rooms_res = $con->query("SELECT room_id, name, type FROM rooms ORDER BY name ASC");
$rooms = [];
while ($r = $rooms_res->fetch_assoc()) {
    $rooms[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Room Slot Management - Seela Suwa Herath Bikshu Gilan Arana</title>
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
        .page-header {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(245, 124, 0, 0.3);
        }
        .slot-card {
            transition: transform 0.2s;
            border-left: 4px solid var(--monastery-saffron);
            background: white;
        }
        .slot-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 124, 0, 0.2);
        }
        .time-badge {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.9rem;
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
                <h2 class="mb-0"><i class="bi bi-clock-history"></i> Room Slot Management</h2>
                <p class="mb-0 mt-1 opacity-75">Manage time slots for room bookings</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Time Slot
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

    <!-- Room Slots Grid -->
    <div class="row">
        <?php if ($slots_res->num_rows == 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No room slots found. Click "Add Time Slot" to create one.
                </div>
            </div>
        <?php endif; ?>

        <?php 
        $current_room = null;
        while($row = $slots_res->fetch_assoc()): 
            if ($current_room !== $row['room_id']) {
                if ($current_room !== null) {
                    echo '</div></div></div>'; // Close previous room card
                }
                $current_room = $row['room_id'];
        ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-door-open"></i> <?= htmlspecialchars($row['room_name']) ?></h5>
                        <small class="opacity-75"><?= htmlspecialchars($row['type']) ?></small>
                    </div>
                    <div class="card-body">
        <?php } ?>
                        <div class="slot-card card mb-2">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <span class="badge bg-primary"><?= $days_map[$row['day_of_week']] ?></span>
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
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['room_slot_id'] ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['room_slot_id'] ?>" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $row['room_slot_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Room Slot</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="form_name" value="update">
                                            <input type="hidden" name="room_slot_id" value="<?= $row['room_slot_id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Room</label>
                                                <select name="room_id" class="form-select" required>
                                                    <?php foreach($rooms as $room): ?>
                                                        <option value="<?= $room['room_id'] ?>" <?= $room['room_id'] == $row['room_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($room['name']) ?> - <?= $room['type'] ?>
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
                                                <input type="checkbox" name="is_active" class="form-check-input" id="active<?= $row['room_slot_id'] ?>" <?= $row['is_active'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="active<?= $row['room_slot_id'] ?>">Active</label>
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
                        <div class="modal fade" id="deleteModal<?= $row['room_slot_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete this room slot?</p>
                                            <p><strong><?= htmlspecialchars($row['room_name']) ?></strong> - 
                                            <?= $days_map[$row['day_of_week']] ?> 
                                            (<?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?>)</p>
                                            <input type="hidden" name="form_name" value="delete">
                                            <input type="hidden" name="room_slot_id" value="<?= $row['room_slot_id'] ?>">
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
        <?php if ($current_room !== null): ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add New Slot Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Room Slot</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">-- Select Room --</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?= $room['room_id'] ?>">
                                    <?= htmlspecialchars($room['name']) ?> - <?= $room['type'] ?>
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
                        <i class="bi bi-info-circle"></i> <small>Slot will be set as active by default.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
