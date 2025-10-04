<?php
session_start();
include 'navbar.php'; 

$servername = "localhost";
$dbusername = "root";
$db_password = "";
$dbname = "nethmi";

$con = new mysqli($servername, $dbusername, $db_password, $dbname);
if ($con->connect_error) die("Connection failed: " . $con->connect_error);

$error = "";
$success = "";

// Handle CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    if ($_POST['form_name'] === 'create' || $_POST['form_name'] === 'update') {
        $id = $_POST['id'] ?? null;
        $patient_id = $_POST['patient_id'];
        $doctor_id = $_POST['doctor_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $amount = $_POST['amount'];
        $order_number = $_POST['order_number'];

        if ($id && $_POST['form_name'] === 'update') {
            $stmt = $con->prepare("UPDATE patient_appointments SET patient_id=?, doctor_id=?, appointment_date=?, appointment_time=?, amount=?, order_number=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("iissdsi", $patient_id, $doctor_id, $appointment_date, $appointment_time, $amount, $order_number, $id);
            if ($stmt->execute()) $success = "Appointment updated successfully!";
            else $error = $stmt->error;
            $stmt->close();
        } else {
            $stmt = $con->prepare("INSERT INTO patient_appointments (patient_id, doctor_id, appointment_date, appointment_time, amount, order_number, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissds", $patient_id, $doctor_id, $appointment_date, $appointment_time, $amount, $order_number);
            if ($stmt->execute()) $success = "Appointment created successfully!";
            else $error = $stmt->error;
            $stmt->close();
        }
    }

    // Delete
    if ($_POST['form_name'] === 'delete' && !empty($_POST['id'])) {
        $id = $_POST['id'];
        $stmt = $con->prepare("DELETE FROM patient_appointments WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $success = "Appointment deleted successfully!";
        else $error = $stmt->error;
        $stmt->close();
    }
}

// Fetch appointments
$appointments_res = $con->query("
    SELECT pa.*, p.username AS patient_name, d.username AS doctor_name
    FROM patient_appointments pa
    JOIN user p ON pa.patient_id = p.id
    JOIN user d ON pa.doctor_id = d.id
    ORDER BY pa.appointment_date DESC, pa.appointment_time DESC
");

// Fetch users for dropdown
$users_res = $con->query("SELECT id, username FROM user ORDER BY username ASC");
$users = [];
while ($u = $users_res->fetch_assoc()) $users[$u['id']] = $u['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">Patient Appointments</h2>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Appointments Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Amount</th>
                <th>Order Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $appointments_res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['appointment_time'] ?></td>
                <td><?= $row['amount'] ?></td>
                <td><?= htmlspecialchars($row['order_number']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header bg-primary text-white">
                      <h5 class="modal-title">Edit Appointment</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="update">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="mb-3">
                            <label>Patient</label>
                            <select name="patient_id" class="form-control" required>
                                <?php foreach($users as $uid=>$uname): ?>
                                    <option value="<?= $uid ?>" <?= $uid==$row['patient_id']?'selected':'' ?>><?= htmlspecialchars($uname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Doctor</label>
                            <select name="doctor_id" class="form-control" required>
                                <?php foreach($users as $uid=>$uname): ?>
                                    <option value="<?= $uid ?>" <?= $uid==$row['doctor_id']?'selected':'' ?>><?= htmlspecialchars($uname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Date</label>
                            <input type="date" class="form-control" name="appointment_date" value="<?= $row['appointment_date'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Time</label>
                            <input type="time" class="form-control" name="appointment_time" value="<?= $row['appointment_time'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" step="0.01" class="form-control" name="amount" value="<?= $row['amount'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Order Number</label>
                            <input type="text" class="form-control" name="order_number" value="<?= htmlspecialchars($row['order_number']) ?>" required>
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
            <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this appointment for <strong><?= htmlspecialchars($row['patient_name']) ?></strong>?
                        <input type="hidden" name="form_name" value="delete">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
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
        </tbody>
    </table>

    <!-- Add New Appointment -->
    <div class="card mt-4 p-3">
        <h4>Add New Appointment</h4>
        <form method="post">
            <input type="hidden" name="form_name" value="create">
            <div class="row mb-3">
                <div class="col">
                    <label>Patient</label>
                    <select name="patient_id" class="form-control" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach($users as $uid=>$uname): ?>
                            <option value="<?= $uid ?>"><?= htmlspecialchars($uname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <label>Doctor</label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach($users as $uid=>$uname): ?>
                            <option value="<?= $uid ?>"><?= htmlspecialchars($uname) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label>Date</label>
                    <input type="date" class="form-control" name="appointment_date" required>
                </div>
                <div class="col">
                    <label>Time</label>
                    <input type="time" class="form-control" name="appointment_time" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col">
                    <label>Amount</label>
                    <input type="number" step="0.01" class="form-control" name="amount" required>
                </div>
                <div class="col">
                    <label>Order Number</label>
                    <input type="text" class="form-control" name="order_number" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Add Appointment</button>
        </form>
    </div>
</div>
</body>
</html>
