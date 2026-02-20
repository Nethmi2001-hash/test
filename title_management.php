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
$dbname      = "monastery_healthcare";

$con = new mysqli($servername, $db_username, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$success = "";
$error   = "";

//  Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_name'])) {
    $form_name = $_POST['form_name'];

    //  Create title
    if ($form_name === "create" && !empty($_POST['user_title'])) {
        $title = trim($_POST['user_title']);

        // Check if title exists
        $check_stmt = $con->prepare("SELECT title_id FROM titles WHERE LOWER(title_name) = LOWER(?)");
        $check_stmt->bind_param("s", $title);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "This title already exists. Please enter a different one.";
        } else {
            $stmt = $con->prepare("INSERT INTO titles (title_name) VALUES (?)");
            $stmt->bind_param("s", $title);
            if ($stmt->execute()) {
                $success = "Title added successfully!";
            } else {
                $error = "Error: " . $con->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }

    //  Update title
    elseif ($form_name === "update" && !empty($_POST['id']) && !empty($_POST['user_title'])) {
	$id = intval($_POST['id']);
    $title = trim($_POST['user_title']);

    // Normalize title for comparison
    $title_lower = strtolower($title);

    //  Check for duplicates excluding the current record
    $check_stmt = $con->prepare("
        SELECT title_id FROM titles 
        WHERE LOWER(TRIM(title_name)) = ? AND title_id != ?
    ");
    $check_stmt->bind_param("si", $title_lower, $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "This title already exists. Please enter a different one.";
    } else {
        //  update
        $stmt = $con->prepare("UPDATE titles SET title_name = ? WHERE title_id = ?");
        $stmt->bind_param("si", $title, $id);
        if ($stmt->execute()) {
            $success = "Title updated successfully!";
        } else {
            $error = "Error updating title: " . $con->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}
    //  Delete title
    elseif ($form_name === "delete" && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $con->prepare("DELETE FROM titles WHERE title_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Title deleted successfully!";
        } else {
            $error = "Error deleting title: " . $con->error;
        }
        $stmt->close();
    }
} 
//  Fetch titles for table (always runs)
$result = $con->query("SELECT * FROM titles ORDER BY title_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title Management - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="container mt-4 mb-5">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-award"></i> Title Management</h2>
                <p class="mb-0 mt-1 opacity-75">Manage honorific titles for monks</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Title
                </button>
            </div>
        </div>
    </div>

    <!-- Founder Identity Strip -->
    <div class="alert" style="background: linear-gradient(135deg, rgba(110, 134, 98, 0.08) 0%, rgba(79, 102, 69, 0.05) 100%); border-left: 3px solid var(--primary); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <img src="images/img1.jpeg" alt="Founder" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
        <div style="font-size: 0.875rem; line-height: 1.4;">
            <div style="font-weight: 600; color: var(--primary);">Seela Suwa Herath Bikshu Gilan Arana</div>
            <div style="opacity: 0.75; font-size: 0.8rem;">Founded by Ven. Solewewa Chandrasiri Thero</div>
        </div>
    </div>

    <!-- Alerts -->
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

    <!-- Titles table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Titles</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $row['title_id'] ?></strong></td>
                            <td><i class="bi bi-award-fill text-primary"></i> <?= htmlspecialchars($row['title_name']) ?></td>
                            <td>
                                <!-- Edit button -->
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal<?= $row['title_id'] ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>

                                        <!-- Delete button -->
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal<?= $row['title_id'] ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $row['title_id'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <form method="post">
                                <div class="modal-header bg-primary text-white">
                                  <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Title</h5>
                                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                  <input type="hidden" name="form_name" value="update">
                                  <input type="hidden" name="id" value="<?= $row['title_id'] ?>">
                                  <div class="mb-3">
                                      <label class="form-label">Title Name</label>
                                      <input type="text" name="user_title" class="form-control" 
                                             value="<?= htmlspecialchars($row['title_name']) ?>" required>
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
                        <div class="modal fade" id="deleteModal<?= $row['title_id'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                Are you sure you want to delete title
                                <strong><?= htmlspecialchars($row['title_name']) ?></strong>?<br>
                                <small class="text-muted">This action cannot be undone.</small>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $row['title_id'] ?>">
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
    </div>
</div>

<!-- Add New Title Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Title</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    <div class="mb-3">
                        <label class="form-label">Title Name</label>
                        <input type="text" name="user_title" class="form-control" placeholder="e.g., Ven., Rev., Most Ven." required>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <small>Title will be used as honorific prefix for monks.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Title</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
