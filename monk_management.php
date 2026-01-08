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
        $title_id = !empty($_POST['title_id']) ? intval($_POST['title_id']) : null;
        $ordination_date = !empty($_POST['ordination_date']) ? $_POST['ordination_date'] : null;
        $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $phone = trim($_POST['phone'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $chronic_conditions = trim($_POST['chronic_conditions'] ?? '');
        $current_medications = trim($_POST['current_medications'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'] ?? 'active';

        if (empty($full_name)) {
            $error = "Monk name is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO monks (full_name, title_id, ordination_date, birth_date, phone, emergency_contact, blood_group, allergies, chronic_conditions, current_medications, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissssssssss", $full_name, $title_id, $ordination_date, $birth_date, $phone, $emergency_contact, $blood_group, $allergies, $chronic_conditions, $current_medications, $notes, $status);
            
            if ($stmt->execute()) {
                $success = "Monk added successfully!";
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($form_name === 'update') {
        $monk_id = intval($_POST['monk_id']);
        $full_name = trim($_POST['full_name']);
        $title_id = !empty($_POST['title_id']) ? intval($_POST['title_id']) : null;
        $ordination_date = !empty($_POST['ordination_date']) ? $_POST['ordination_date'] : null;
        $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $phone = trim($_POST['phone'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $chronic_conditions = trim($_POST['chronic_conditions'] ?? '');
        $current_medications = trim($_POST['current_medications'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE monks SET full_name=?, title_id=?, ordination_date=?, birth_date=?, phone=?, emergency_contact=?, blood_group=?, allergies=?, chronic_conditions=?, current_medications=?, notes=?, status=? WHERE monk_id=?");
        $stmt->bind_param("sissssssssssi", $full_name, $title_id, $ordination_date, $birth_date, $phone, $emergency_contact, $blood_group, $allergies, $chronic_conditions, $current_medications, $notes, $status, $monk_id);
        
        if ($stmt->execute()) {
            $success = "Monk updated successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($form_name === 'delete') {
        $monk_id = intval($_POST['monk_id']);
        $stmt = $conn->prepare("DELETE FROM monks WHERE monk_id=?");
        $stmt->bind_param("i", $monk_id);
        
        if ($stmt->execute()) {
            $success = "Monk deleted successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get titles for dropdown
$titles = [];
$title_result = $conn->query("SELECT * FROM titles ORDER BY title_name ASC");
if ($title_result) {
    while ($row = $title_result->fetch_assoc()) {
        $titles[] = $row;
    }
}

// Get all monks with title info
$monks = [];
$result = $conn->query("
    SELECT m.*, t.title_name 
    FROM monks m 
    LEFT JOIN titles t ON m.title_id = t.title_id 
    ORDER BY m.full_name ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monks[] = $row;
    }
}

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM monks")->fetch_assoc()['count'];
$stats['active'] = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status='active'")->fetch_assoc()['count'];
$stats['inactive'] = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status='inactive'")->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monk Management - Monastery System</title>
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
        .monk-card {
            border-left: 3px solid var(--monastery-light);
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .monk-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-left-color: var(--monastery-saffron);
        }
        .medical-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.9em;
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
                                <h6 class="text-muted mb-1">Total Monks</h6>
                                <h3 class="mb-0" style="color: var(--monastery-saffron);"><?= $stats['total'] ?></h3>
                            </div>
                            <div class="fs-1" style="color: var(--monastery-light);">
                                🙏
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
                                <h6 class="text-muted mb-1">Active Monks</h6>
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
                                <h6 class="text-muted mb-1">Inactive Monks</h6>
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

        <!-- Monks List -->
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🪷 Monk Management & Medical Records</h5>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle"></i> Add New Monk
                    </button>
                </div>
            </div>
            <div class="card-body">
                <?php if (count($monks) > 0): ?>
                    <?php foreach ($monks as $monk): ?>
                        <div class="monk-card">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-1" style="color: var(--monastery-dark);">
                                        <?php if ($monk['title_name']): ?>
                                            <span class="badge" style="background: var(--monastery-pale); color: var(--monastery-dark);">
                                                <?= htmlspecialchars($monk['title_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($monk['full_name']) ?>
                                    </h6>
                                    <small class="text-muted">Monk ID: #<?= $monk['monk_id'] ?></small>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($monk['phone']): ?>
                                        <small><i class="bi bi-telephone"></i> <?= htmlspecialchars($monk['phone']) ?></small><br>
                                    <?php endif; ?>
                                    <?php if (!empty($monk['ordination_date'])): ?>
                                        <small><i class="bi bi-calendar3"></i> Ordained: <?= date('M Y', strtotime($monk['ordination_date'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($monk['blood_group']): ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($monk['blood_group']) ?></span>
                                    <?php endif; ?>
                                    <?php
                                    $status_class = $monk['status'] == 'active' ? 'success' : 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>">
                                        <?= strtoupper($monk['status']) ?>
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-primary" onclick="editMonk(<?= htmlspecialchars(json_encode($monk)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this monk record?')">
                                        <input type="hidden" name="form_name" value="delete">
                                        <input type="hidden" name="monk_id" value="<?= $monk['monk_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Medical Information -->
                            <?php if (!empty($monk['allergies']) || !empty($monk['chronic_conditions']) || !empty($monk['current_medications'])): ?>
                                <div class="medical-info">
                                    <strong><i class="bi bi-heart-pulse"></i> Medical Information:</strong>
                                    <?php if (!empty($monk['allergies'])): ?>
                                        <div><strong>Allergies:</strong> <?= htmlspecialchars($monk['allergies']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($monk['chronic_conditions'])): ?>
                                        <div><strong>Chronic Conditions:</strong> <?= htmlspecialchars($monk['chronic_conditions']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($monk['current_medications'])): ?>
                                        <div><strong>Current Medications:</strong> <?= htmlspecialchars($monk['current_medications']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="fs-1">🙏</div>
                        <p class="text-muted">No monk records found. Click "Add New Monk" to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Monk Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New Monk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="create">
                        
                        <h6 class="text-muted mb-3">Basic Information</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Title</label>
                                <select name="title_id" class="form-select">
                                    <option value="">-- No Title --</option>
                                    <?php foreach ($titles as $title): ?>
                                        <option value="<?= $title['title_id'] ?>"><?= htmlspecialchars($title['title_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" placeholder="Enter monk's full name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ordination Date</label>
                                <input type="date" name="ordination_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+94 77 123 4567">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" class="form-control" placeholder="Name & Phone">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="bi bi-heart-pulse"></i> Medical History</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Allergies</label>
                            <textarea name="allergies" class="form-control" rows="2" placeholder="e.g., Penicillin, Peanuts, etc."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chronic Conditions</label>
                            <textarea name="chronic_conditions" class="form-control" rows="2" placeholder="e.g., Diabetes, Hypertension, Asthma, etc."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Medications</label>
                            <textarea name="current_medications" class="form-control" rows="2" placeholder="List current medications and dosages"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Any other important medical or personal information"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--monastery-saffron); border: none;">
                            <i class="bi bi-save"></i> Add Monk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Monk Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); color: white;">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Monk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="update">
                        <input type="hidden" name="monk_id" id="edit_monk_id">
                        
                        <h6 class="text-muted mb-3">Basic Information</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Title</label>
                                <select name="title_id" id="edit_title_id" class="form-select">
                                    <option value="">-- No Title --</option>
                                    <?php foreach ($titles as $title): ?>
                                        <option value="<?= $title['title_id'] ?>"><?= htmlspecialchars($title['title_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ordination Date</label>
                                <input type="date" name="ordination_date" id="edit_ordination_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" id="edit_birth_date" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="text" name="emergency_contact" id="edit_emergency_contact" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select name="blood_group" id="edit_blood_group" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-muted mb-3"><i class="bi bi-heart-pulse"></i> Medical History</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Allergies</label>
                            <textarea name="allergies" id="edit_allergies" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chronic Conditions</label>
                            <textarea name="chronic_conditions" id="edit_chronic_conditions" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Medications</label>
                            <textarea name="current_medications" id="edit_current_medications" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: var(--monastery-saffron); border: none;">
                            <i class="bi bi-save"></i> Update Monk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMonk(monk) {
            document.getElementById('edit_monk_id').value = monk.monk_id;
            document.getElementById('edit_full_name').value = monk.full_name;
            document.getElementById('edit_title_id').value = monk.title_id || '';
            document.getElementById('edit_ordination_date').value = monk.ordination_date || '';
            document.getElementById('edit_birth_date').value = monk.birth_date || '';
            document.getElementById('edit_phone').value = monk.phone || '';
            document.getElementById('edit_emergency_contact').value = monk.emergency_contact || '';
            document.getElementById('edit_blood_group').value = monk.blood_group || '';
            document.getElementById('edit_allergies').value = monk.allergies || '';
            document.getElementById('edit_chronic_conditions').value = monk.chronic_conditions || '';
            document.getElementById('edit_current_medications').value = monk.current_medications || '';
            document.getElementById('edit_notes').value = monk.notes || '';
            document.getElementById('edit_status').value = monk.status;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>
