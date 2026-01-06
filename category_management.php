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

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['form_name'])) {

    // ADD CATEGORY
    if ($_POST['form_name'] === "create" && !empty($_POST['name'])) {
        $name = trim($_POST['name']);

        // Check for duplicates
        $check = $con->prepare("SELECT category_id FROM categories WHERE LOWER(name) = LOWER(?)");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Category '$name' already exists!";
        } else {
            $stmt = $con->prepare("INSERT INTO categories (name, type, description) VALUES (?, 'donation', ?)");
            $description = $_POST['description'] ?? '';
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $success = "Category added successfully!";
            } else {
                $error = "Error adding category: " . $con->error;
            }
            $stmt->close();
        }
        $check->close();
    }

    // Update category
    if ($_POST['form_name'] === "update" && !empty($_POST['id']) && !empty($_POST['name'])) {
        $id   = intval($_POST['id']);
        $name = trim($_POST['name']);
		
        // Check for duplicates 
        $check = $con->prepare("SELECT category_id FROM categories WHERE LOWER(name) = LOWER(?) AND category_id != ?");
        $check->bind_param("si", $name, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Category '$name' already exists!";
        } else {
            $stmt = $con->prepare("UPDATE categories SET name=?, description=? WHERE category_id=?");
            $description = $_POST['description'] ?? '';
            $stmt->bind_param("ssi", $name, $description, $id);
            if ($stmt->execute()) {
                $success = "Category updated successfully!";
            } else {
                $error = "Error updating category: " . $con->error;
            }
            $stmt->close();
        }
        $check->close();
    }


    // Delete category
    if ($_POST['form_name'] === "delete" && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $con->prepare("DELETE FROM categories WHERE category_id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Category deleted successfully!";
        } else {
            $error = "Error deleting category: " . $con->error;
        }
        $stmt->close();
    }
}

// Fetch categories
$result = $con->query("SELECT * FROM categories ORDER BY category_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Category Management - Seela Suwa Herath Bikshu Gilan Arana</title>
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
                <h2 class="mb-0"><i class="bi bi-tag"></i> Category Management</h2>
                <p class="mb-0 mt-1 opacity-75">Manage donation and bill categories</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Category
                </button>
            </div>
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

    <!-- Category table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Categories</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $row['category_id'] ?></strong></td>
                    <td><i class="bi bi-tag-fill text-primary"></i> <?= htmlspecialchars($row['name']) ?></td>
                    <td>
                        <?php if ($row['type'] == 'donation'): ?>
                            <span class="badge bg-success">Donation</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Bill</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
                    <td>
                        <!-- Edit button -->
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $row['category_id'] ?>">
                            <i class="bi bi-pencil"></i> Edit
                        </button>

                        <!-- Delete button -->
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal<?= $row['category_id'] ?>">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $row['category_id'] ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="post">
                        <div class="modal-header bg-primary text-white">
                          <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Category</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="form_name" value="update">
                          <input type="hidden" name="id" value="<?= $row['category_id'] ?>">
                          <div class="mb-3">
                              <label class="form-label">Category Name</label>
                              <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                          </div>
                          <div class="mb-3">
                              <label class="form-label">Description</label>
                              <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($row['description'] ?? '') ?>">
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
                <div class="modal fade" id="deleteModal<?= $row['category_id'] ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-trash"></i> Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to delete category
                        <strong><?= htmlspecialchars($row['name']) ?></strong>?<br>
                        <small class="text-muted">This action cannot be undone.</small>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?= $row['category_id'] ?>">
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

<!-- Add Category Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="form_name" value="create">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Medicine, Food" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief description">
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <small>Category will be set as "Donation" type by default.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
