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

// Handle CREATE/UPDATE/DELETE via form_name
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['form_name'])) {
    $days = ['mon','tue','wed','thu','fri','sat','sun'];
    $id = $_POST['id'] ?? null;

    if ($_POST['form_name'] === 'create' || $_POST['form_name'] === 'update') {
        $user_id = $_POST['user_id'];
        $data = [];
        foreach($days as $day) {
            $data[$day.'_am'] = $_POST[$day.'_am'] ?? '';
            $data[$day.'_pm'] = $_POST[$day.'_pm'] ?? '';
        }

        if ($id && $_POST['form_name'] === 'update') {
            $stmt = $con->prepare("UPDATE doctor_availability SET user_id=?, mon_am=?, mon_pm=?, tue_am=?, tue_pm=?, wed_am=?, wed_pm=?, thu_am=?, thu_pm=?, fri_am=?, fri_pm=?, sat_am=?, sat_pm=?, sun_am=?, sun_pm=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("issssssssssssssi",
                $user_id, $data['mon_am'], $data['mon_pm'], $data['tue_am'], $data['tue_pm'], $data['wed_am'], $data['wed_pm'],
                $data['thu_am'], $data['thu_pm'], $data['fri_am'], $data['fri_pm'], $data['sat_am'], $data['sat_pm'], $data['sun_am'], $data['sun_pm'], $id
            );
            if ($stmt->execute()) $success = "Availability updated successfully!";
            else $error = $stmt->error;
            $stmt->close();
        } else {
            $stmt = $con->prepare("INSERT INTO doctor_availability (user_id, mon_am, mon_pm, tue_am, tue_pm, wed_am, wed_pm, thu_am, thu_pm, fri_am, fri_pm, sat_am, sat_pm, sun_am, sun_pm) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssssssss",
                $user_id, $data['mon_am'], $data['mon_pm'], $data['tue_am'], $data['tue_pm'], $data['wed_am'], $data['wed_pm'],
                $data['thu_am'], $data['thu_pm'], $data['fri_am'], $data['fri_pm'], $data['sat_am'], $data['sat_pm'], $data['sun_am'], $data['sun_pm']
            );
            if ($stmt->execute()) $success = "Availability added successfully!";
            else $error = $stmt->error;
            $stmt->close();
        }
    }

    // Delete
    if ($_POST['form_name'] === 'delete' && !empty($id)) {
        $stmt = $con->prepare("DELETE FROM doctor_availability WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $success = "Availability deleted successfully!";
        else $error = $stmt->error;
        $stmt->close();
    }
}

// Fetch availability
$avail_res = $con->query("SELECT da.*, u.username FROM doctor_availability da JOIN user u ON da.user_id=u.id ORDER BY da.id DESC");

// Fetch doctors
$doctors_res = $con->query("SELECT id, username FROM user ORDER BY username ASC");
$doctors = [];
while ($d = $doctors_res->fetch_assoc()) $doctors[$d['id']] = $d['username'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Doctor Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">Doctor Availability</h2>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Table -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th><th>Doctor</th>
                <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
                    <th><?= $day ?> AM</th>
                    <th><?= $day ?> PM</th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $avail_res->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <?php foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day): ?>
                    <td><?= htmlspecialchars($row[$day.'_am']) ?></td>
                    <td><?= htmlspecialchars($row[$day.'_pm']) ?></td>
                <?php endforeach; ?>
                <td>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header bg-primary text-white">
                      <h5 class="modal-title">Edit Availability</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="form_name" value="update">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="mb-3">
                            <label>Doctor</label>
                            <select name="user_id" class="form-control" required>
                                <?php foreach($doctors as $uid=>$uname): ?>
                                    <option value="<?= $uid ?>" <?= $uid==$row['user_id']?'selected':'' ?>><?= htmlspecialchars($uname) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day): ?>
                        <div class="row mb-2">
                            <div class="col">
                                <label><?= ucfirst($day) ?> AM</label>
                                <input type="time" class="form-control" name="<?= $day ?>_am" value="<?= $row[$day.'_am'] ?>">
                            </div>
                            <div class="col">
                                <label><?= ucfirst($day) ?> PM</label>
                                <input type="time" class="form-control" name="<?= $day ?>_pm" value="<?= $row[$day.'_pm'] ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                        Are you sure you want to delete availability for <strong><?= htmlspecialchars($row['username']) ?></strong>?
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

    <!-- Add New Availability -->
    <div class="card mt-4 p-4">
        <h4>Add New Availability</h4>
        <form method="post">
            <input type="hidden" name="form_name" value="create">
            <div class="mb-3">
                <label>Doctor</label>
                <select name="user_id" class="form-control" required>
                    <option value="">-- Select Doctor --</option>
                    <?php foreach($doctors as $uid=>$uname): ?>
                        <option value="<?= $uid ?>"><?= htmlspecialchars($uname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach(['mon','tue','wed','thu','fri','sat','sun'] as $day): ?>
            <div class="row mb-2">
                <div class="col">
                    <label><?= ucfirst($day) ?> AM</label>
                    <input type="time" class="form-control" name="<?= $day ?>_am">
                </div>
                <div class="col">
                    <label><?= ucfirst($day) ?> PM</label>
                    <input type="time" class="form-control" name="<?= $day ?>_pm">
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success mt-3">Add Availability</button>
        </form>
    </div>
</div>
</body>
</html>
