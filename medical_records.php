<?php
session_start();
include 'navbar.php';

// Access control - Only doctors can access
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

$error = "";
$success = "";

// Get doctor information
$doctor_query = $conn->prepare("SELECT * FROM doctors WHERE email = ? OR full_name LIKE ?");
$search_name = '%' . $_SESSION['username'] . '%';
$doctor_query->bind_param("ss", $_SESSION['email'], $search_name);
$doctor_query->execute();
$doctor_info = $doctor_query->get_result()->fetch_assoc();
$doctor_id = $doctor_info['doctor_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    if ($form_name === 'create_record' && $doctor_id) {
        $monk_id = intval($_POST['monk_id']);
        $diagnosis = trim($_POST['diagnosis']);
        $prescription = trim($_POST['prescription']);
        $notes = trim($_POST['notes'] ?? '');
        $follow_up_date = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $record_date = $_POST['record_date'];

        if (empty($monk_id) || empty($diagnosis)) {
            $error = "Monk selection and diagnosis are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO medical_records (monk_id, doctor_id, record_date, diagnosis, prescription, notes, follow_up_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'];
            $stmt->bind_param("iisssssi", $monk_id, $doctor_id, $record_date, $diagnosis, $prescription, $notes, $follow_up_date, $created_by);
            
            if ($stmt->execute()) {
                $success = "Medical record added successfully! Record ID: " . $stmt->insert_id;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get monks for dropdown
$monks = $conn->query("SELECT monk_id, full_name FROM monks WHERE status = 'active' ORDER BY full_name");

// Get recent medical records for this doctor
$recent_records = [];
if ($doctor_id) {
    $result = $conn->prepare("
        SELECT mr.*, m.full_name as monk_name 
        FROM medical_records mr
        JOIN monks m ON mr.monk_id = m.monk_id
        WHERE mr.doctor_id = ?
        ORDER BY mr.record_date DESC, mr.created_at DESC
        LIMIT 20
    ");
    $result->bind_param("i", $doctor_id);
    $result->execute();
    $recent_records = $result->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get monk_id from URL if specified
$selected_monk_id = $_GET['monk_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - Monastery Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-file-medical-fill text-primary"></i> Medical Records Management</h2>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if (!$doctor_id): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                Doctor profile not found. Please contact administrator to setup your doctor profile.
            </div>
            <?php else: ?>

            <!-- Add Medical Record Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-plus-circle"></i> Add New Medical Record</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="form_name" value="create_record">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Patient (Monk) *</label>
                                    <select name="monk_id" class="form-select" required>
                                        <option value="">Select Monk</option>
                                        <?php while ($monk = $monks->fetch_assoc()): ?>
                                        <option value="<?= $monk['monk_id'] ?>" <?= $selected_monk_id == $monk['monk_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($monk['full_name']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Record Date *</label>
                                    <input type="date" name="record_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Diagnosis *</label>
                                    <textarea name="diagnosis" class="form-control" rows="3" placeholder="Enter diagnosis details..." required></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prescription</label>
                                    <textarea name="prescription" class="form-control" rows="3" placeholder="Enter prescription details..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Follow-up Date</label>
                                    <input type="date" name="follow_up_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Medical Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Medical Records -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-clock-history"></i> Recent Medical Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_records)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-file-medical fs-1 mb-3"></i>
                        <p>No medical records found. Add your first record using the form above.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Diagnosis</th>
                                    <th>Prescription</th>
                                    <th>Follow-up</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('M d, Y', strtotime($record['record_date'])) ?></strong><br>
                                        <small class="text-muted"><?= date('g:i A', strtotime($record['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($record['monk_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-info"><?= htmlspecialchars(substr($record['diagnosis'], 0, 100)) ?><?= strlen($record['diagnosis']) > 100 ? '...' : '' ?></span>
                                    </td>
                                    <td>
                                        <?php if ($record['prescription']): ?>
                                        <span class="text-success"><?= htmlspecialchars(substr($record['prescription'], 0, 80)) ?><?= strlen($record['prescription']) > 80 ? '...' : '' ?></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['follow_up_date']): ?>
                                        <span class="badge bg-<?= strtotime($record['follow_up_date']) < time() ? 'danger' : 'warning' ?>">
                                            <?= date('M d, Y', strtotime($record['follow_up_date'])) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewRecord(<?= $record['record_id'] ?>)">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php endif; // End doctor_id check ?>
        </div>
    </div>
</div>

<!-- View Record Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Medical Record Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="recordContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewRecord(recordId) {
    // You can implement this to show full record details
    // For now, just alert the record ID
    alert('View record ID: ' + recordId + '\\nFull record view feature can be implemented here.');
}
</script>

</body>
</html>