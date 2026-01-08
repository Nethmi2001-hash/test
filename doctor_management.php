<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "monastery_healthcare";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create') {
        $full_name = trim($_POST['full_name']);
        $specialization = trim($_POST['specialization']);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($full_name) || empty($specialization)) {
            $error = "Doctor name and specialization are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO doctors (full_name, specialization, phone, email, license_number, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $specialization, $phone, $email, $license_number, $status);
            
            if ($stmt->execute()) {
                $success = "Doctor added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'update') {
        $doctor_id = intval($_POST['doctor_id']);
        $full_name = trim($_POST['full_name']);
        $specialization = trim($_POST['specialization']);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $license_number = trim($_POST['license_number'] ?? '');
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE doctors SET full_name=?, specialization=?, phone=?, email=?, license_number=?, status=? WHERE doctor_id=?");
        $stmt->bind_param("ssssssi", $full_name, $specialization, $phone, $email, $license_number, $status, $doctor_id);
        
        if ($stmt->execute()) {
            $success = "Doctor updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $doctor_id = intval($_POST['doctor_id']);
        $stmt = $conn->prepare("DELETE FROM doctors WHERE doctor_id=?");
        $stmt->bind_param("i", $doctor_id);
        
        if ($stmt->execute()) {
            $success = "Doctor deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all doctors
$doctors = [];
$result = $conn->query("SELECT * FROM doctors ORDER BY full_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'];
$stats['active'] = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status='active'")->fetch_assoc()['count'];
$stats['inactive'] = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status='inactive'")->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Monastery System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-light: #ffa726;
            --monastery-dark: #e65100;
            --monastery-pale: #fff3e0;
        }
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .stat-card {
            border-left: 4px solid var(--monastery-saffron);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .doctor-card {
            border-left: 3px solid var(--monastery-light);
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .doctor-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left-color: var(--monastery-saffron);
        }
        .lotus-divider {
            text-align: center;
            color: var(--monastery-saffron);
            font-size: 24px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Doctors</h6>
                                <h3 class="mb-0" style="color: var(--monastery-saffron);"><?= $stats['total'] ?></h3>
                            </div>
                            <div class="fs-1" style="color: var(--monastery-light);">
                                <i class="bi bi-person-badge"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Doctors</h6>
                                <h3 class="mb-0 text-success"><?= $stats['active'] ?></h3>
                            </div>
                            <div class="fs-1 text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Inactive Doctors</h6>
                                <h3 class="mb-0 text-secondary"><?= $stats['inactive'] ?></h3>
                            </div>
                            <div class="fs-1 text-secondary">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Doctor Button -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person-plus"></i> Doctor Management</h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle"></i> Add New Doctor
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($doctors) > 0): ?>
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-1" style="color: var(--monastery-dark);">
                                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($doctor['full_name']) ?>
                                    </h6>
                                    <small class="text-muted">ID: #<?= $doctor['doctor_id'] ?></small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge" style="background: var(--monastery-pale); color: var(--monastery-dark);">
                                        <?= htmlspecialchars($doctor['specialization']) ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($doctor['phone']): ?>
                                        <small><i class="bi bi-telephone"></i> <?= htmlspecialchars($doctor['phone']) ?></small><br>
                                    <?php endif; ?>
                                    <?php if ($doctor['email']): ?>
                                        <small><i class="bi bi-envelope"></i> <?= htmlspecialchars($doctor['email']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <?php
                                    $status_class = $doctor['status'] == 'active' ? 'success' : 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>">
                                        <?= strtoupper($doctor['status']) ?>
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-primary" onclick="editDoctor(<?= htmlspecialchars(json_encode($doctor)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this doctor?')">
                                        <input type="hidden" name="form_name" value="delete">
                                        <input type="hidden" name="doctor_id" value="<?= $doctor['doctor_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php if ($doctor['license_number']): ?>
                                <div class="row mt-2">
                                    <div class="col">
                                        <small class="text-muted"><i class="bi bi-award"></i> License: <?= htmlspecialchars($doctor['license_number']) ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted">No doctors found. Click "Add New Doctor" to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="create">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" class="form-control" placeholder="e.g., General Medicine, Cardiology" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+94 77 123 4567">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="doctor@example.com">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Number</label>
                                <input type="text" name="license_number" class="form-control" placeholder="Medical License #">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--monastery-saffron); border: none;">
                            <i class="bi bi-save"></i> Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Doctor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="update">
                        <input type="hidden" name="doctor_id" id="edit_doctor_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                <input type="text" name="specialization" id="edit_specialization" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Number</label>
                                <input type="text" name="license_number" id="edit_license_number" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--monastery-saffron); border: none;">
                            <i class="bi bi-save"></i> Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDoctor(doctor) {
            document.getElementById('edit_doctor_id').value = doctor.doctor_id;
            document.getElementById('edit_full_name').value = doctor.full_name;
            document.getElementById('edit_specialization').value = doctor.specialization;
            document.getElementById('edit_phone').value = doctor.phone || '';
            document.getElementById('edit_email').value = doctor.email || '';
            document.getElementById('edit_license_number').value = doctor.license_number || '';
            document.getElementById('edit_status').value = doctor.status;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>
