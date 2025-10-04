<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername  = "localhost";
$db_user_name = "root";
$db_password = "";
$dbname      = "nethmi";

$con = new mysqli($servername, $db_user_name, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Handle delete request
if (!empty($_POST['form_name']) && $_POST['form_name'] === "delete" && !empty($_POST['id'])) {
    $id = $_POST['id'];
    $stmt = $con->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success text-center'>Record deleted successfully</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Error deleting record: " . $con->error . "</div>";
    }
}

// Search
$search = trim($_POST['search'] ?? '');
if ($search !== '') {
    $stmt = $con->prepare("SELECT * FROM user WHERE user_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $con->query("SELECT * FROM user");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">

    <h2 class="mb-4 text-center">User Management</h2>

    <!-- Search Form -->
    <form class="d-flex mb-4" method="post">
        <input class="form-control me-2" type="text" name="search" placeholder="Search by full name" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-outline-success me-2" type="submit">Search</button>
        <a href="table.php" class="btn btn-secondary">Reset</a>
    </form>

    <!-- User Table -->
    <table class="table table-striped table-bordered table-hover bg-white">
       <thead class="table-dark">
         <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>DOB</th>
            <th>Phone</th>
            <th>Gender</th>
            <th>Title</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Actions</th>
         </tr>
       </thead>
       <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['age']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['phone_number']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['user_title'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['updated_at']) ?></td>
                <td class="d-flex gap-2">
                    
                    <!-- Delete button opens modal -->
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                        Delete
                    </button>

                    <!-- Edit button -->
                    <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    Are you sure you want to delete user <strong><?= htmlspecialchars($row['full_name']) ?></strong>?<br>
                    This action cannot be undone.
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
        <?php else: ?>
            <tr>
               <td colspan="10" class="text-center">No users found.</td>
            </tr>
        <?php endif; ?>
       </tbody>
    </table>

    <a href="login.php" class="btn btn-outline-primary mt-3">Go to Login</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $con->close(); ?>
