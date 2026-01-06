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

// Handle CREATE / UPDATE / DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $capacity = intval($_POST['capacity']);
        $status = $_POST['status'];

        // Check duplicate
        $check = $con->prepare("SELECT room_id FROM rooms WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Room name already exists.";
        } else {
            $stmt = $con->prepare("INSERT INTO rooms (name, type, capacity, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $name, $type, $capacity, $status);
            if ($stmt->execute()) {
                $success = "Room added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }

    if ($form_name === 'update') {
        $room_id = intval($_POST['room_id']);
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $capacity = intval($_POST['capacity']);
        $status = $_POST['status'];

        // Check duplicate (excluding current)
        $check = $con->prepare("SELECT room_id FROM rooms WHERE name = ? AND room_id != ?");
        $check->bind_param("si", $name, $room_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Room name already exists.";
        } else {
            $stmt = $con->prepare("UPDATE rooms SET name=?, type=?, capacity=?, status=? WHERE room_id=?");
            $stmt->bind_param("ssisi", $name, $type, $capacity, $status, $room_id);
            if ($stmt->execute()) {
                $success = "Room updated successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }

    if ($form_name === 'delete') {
        $room_id = intval($_POST['room_id']);
        $stmt = $con->prepare("DELETE FROM rooms WHERE room_id=?");
        $stmt->bind_param("i", $room_id);
        if ($stmt->execute()) {
            $success = "Room deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch rooms
$rooms_res = $con->query("SELECT * FROM rooms ORDER BY name ASC");

$status_colors = [
    'available' => 'success',
    'occupied' => 'warning',
    'maintenance' => 'danger'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Room Management - Seela Suwa Herath Bikshu Gilan Arana</title>
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
        .room-card {
            transition: transform 0.2s;
            border-left: 4px solid var(--monastery-saffron);
            background: white;
        }
        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 124, 0, 0.2);
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
                <h2 class="mb-0"><i class="bi bi-door-open"></i> Room Management</h2>
                <p class="mb-0 mt-1 opacity-75">Manage consultation and treatment rooms</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Room
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

    <!-- Rooms Grid -->
    <div class="row">
        <?php if ($rooms_res->num_rows == 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No rooms found. Click "Add Room" to create one.
                </div>
            </div>
        <?php endif; ?>

        <?php while($row = $rooms_res->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card room-card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-door-closed"></i> <?= htmlspecialchars($row['name']) ?></h5>
                            <span class="badge bg-<?= $status_colors[$row['status']] ?>"><?= ucfirst($row['status']) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted mb-1"><i class="bi bi-tag"></i> Type</h6>
                            <p class="mb-0"><strong><?= htmlspecialchars($row['type']) ?></strong></p>
                        </div>
                        <div class="mb-3">
                            <h6 class="text-muted mb-1"><i class="bi bi-people"></i> Capacity</h6>
                            <p class="mb-0"><strong><?= $row['capacity'] ?> person(s)</strong></p>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['room_id'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['room_id'] ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['room_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Room</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="form_name" value="update">
                                <input type="hidden" name="room_id" value="<?= $row['room_id'] ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label">Room Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($row['type']) ?>" placeholder="e.g., Consultation, Treatment" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" name="capacity" class="form-control" value="<?= $row['capacity'] ?>" min="1" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="available" <?= $row['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="occupied" <?= $row['status'] == 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                        <option value="maintenance" <?= $row['status'] == 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
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
            <div class="modal fade" id="deleteModal<?= $row['room_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete <strong><?= htmlspecialchars($row['name']) ?></strong>?</p>
                                <p class="text-muted small">This will also delete all associated room slots.</p>
                                <input type="hidden" name="form_name" value="delete">
                                <input type="hidden" name="room_id" value="<?= $row['room_id'] ?>">
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

<!-- Add New Room Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Room</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Room Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Consultation Room 1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <input type="text" name="type" class="form-control" placeholder="e.g., Consultation, Treatment" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="1" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
