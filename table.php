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
$dbname      = "monastery_healthcare";

$con = new mysqli($servername, $db_user_name, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Search
$search = trim($_POST['search'] ?? '');
if ($search !== '') {
    $stmt = $con->prepare("SELECT u.*, r.role_name FROM users u 
                          JOIN roles r ON u.role_id = r.role_id 
                          WHERE u.name LIKE CONCAT('%', ?, '%') OR u.email LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $con->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management - Seela Suwa Herath Bikshu Gilan Arana</title>
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
        .user-card {
            transition: transform 0.2s;
            border-left: 4px solid var(--monastery-saffron);
            background: white;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 124, 0, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0"><i class="bi bi-people"></i> User Management</h2>
                <p class="mb-0 mt-1 opacity-75">Manage system users and roles</p>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3" method="post">
                <div class="col-md-10">
                    <input class="form-control" type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Search</button>
                </div>
                <?php if($search): ?>
                <div class="col-12">
                    <a href="table.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i> Clear Search</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- User Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-table"></i> All Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                   <thead class="table-light">
                     <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                     </tr>
                   </thead>
                   <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($row['user_id']) ?></strong></td>
                            <td><i class="bi bi-person-circle text-primary"></i> <?= htmlspecialchars($row['name']) ?></td>
                            <td><i class="bi bi-envelope text-muted"></i> <?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
                            <td><span class="badge bg-info"><?= htmlspecialchars($row['role_name']) ?></span></td>
                            <td>
                                <?php if ($row['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> No users found
                            </td>
                        </tr>
                    <?php endif; ?>
                   </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php $con->close(); ?>
