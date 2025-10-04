
<?php
session_start();

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include 'navbar.php';

// Database connection
$servername  = "localhost";
$db_username = "root";
$db_password = "";
$dbname      = "nethmi";

$con = new mysqli($servername, $db_username, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$success = "";
$error   = "";

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['form_name'])) {

    // Create title
    if ($_POST['form_name'] === "create" && !empty($_POST['user_title'])) {
        $title = trim($_POST['user_title']);
        $stmt = $con->prepare("INSERT INTO user_titles (user_title) VALUES (?)");
        $stmt->bind_param("s", $title);
        if ($stmt->execute()) {
            $success = "Title added successfully!";
        } else {
            $error = "Error: " . $con->error;
        }
        $stmt->close();
    }

    // Update title
    if ($_POST['form_name'] === "update" && !empty($_POST['id']) && !empty($_POST['user_title'])) {
        $id    = intval($_POST['id']);
        $title = trim($_POST['user_title']);
        $stmt = $con->prepare("UPDATE user_titles SET user_title=? WHERE id=?");
        $stmt->bind_param("si", $title, $id);
        if ($stmt->execute()) {
            $success = "Title updated successfully!";
        } else {
            $error = "Error updating title: " . $con->error;
        }
        $stmt->close();
    }

    // Delete title
    if ($_POST['form_name'] === "delete" && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $con->prepare("DELETE FROM user_titles WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Title deleted successfully!";
        } else {
            $error = "Error deleting title: " . $con->error;
        }
        $stmt->close();
    }
}

// Fetch titles
$result = $con->query("SELECT * FROM user_titles ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Title Management</h2>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add new title -->
    <form method="post" class="d-flex mb-4">
        <input type="hidden" name="form_name" value="create">
       <input type="text" name="user_title" class="form-control me-2" placeholder="New title" style="text-transform: capitalize;" required>
        <button type="submit" class="btn btn-success">Add</button>
    </form>

    <!-- Titles table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr><th>ID</th><th>User Title</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_title']) ?></td>
                    <td>
                        <!-- Edit button -->
                        <button type="button" class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $row['id'] ?>">
                            Edit
                        </button>

                        <!-- Delete button -->
                        <button type="button" class="btn btn-sm btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal<?= $row['id'] ?>">
                            Delete
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="post">
                        <div class="modal-header bg-warning">
                          <h5 class="modal-title">Edit Title</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="form_name" value="update">
                          <input type="hidden" name="id" value="<?= $row['id'] ?>">
                          <input type="text" name="user_title" class="form-control" 
                                 value="<?= htmlspecialchars($row['user_title']) ?>" required>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-warning">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to delete title
                        <strong><?= htmlspecialchars($row['user_title']) ?></strong>?<br>
                        <small class="text-muted">This action cannot be undone.</small>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="form_name" value="delete">
                            <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
document.querySelectorAll('[data-bs-target]').forEach(btn => {
  console.log('Button:', btn.getAttribute('data-bs-target'));
});
</script>

</body>
</html>
