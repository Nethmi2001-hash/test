<?php
session_start();

$servername = "localhost";
$dbusername = "root";
$db_password = "";
$dbname = "nethmi";

$con = new mysqli($servername, $dbusername, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Both username and password are required.";
    } else {
        $stmt = $con->prepare("SELECT * FROM user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            header("Location: table.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function myfunction() {
            var username = document.getElementById("username").value.trim();
            var password = document.getElementById("password").value;

            var show_err_username = false;
            var show_err_password = false;

            if (username === "") {
                document.getElementById("err_username").style.display = "block";
                show_err_username = true;
            }
            if (password === "") {
                document.getElementById("err_password").style.display = "block";
                show_err_password = true;
            }

            return !(show_err_username || show_err_password);
        }

        function hidediv() {
            document.getElementById("err_username").style.display = "none";
            document.getElementById("err_password").style.display = "none";
        }

        function errHide() {
            var username = document.getElementById("username").value;
            var password = document.getElementById("password").value;

            if (username !== "") document.getElementById("err_username").style.display = "none";
            if (password !== "") document.getElementById("err_password").style.display = "none";
        }
    </script>
</head>
<body onload="hidediv();" class="bg-light">
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow" style="max-width: 400px; width: 100%;">
        <div class="card-body">
            <h1 class="text-center mb-3">LOGIN</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="post" onsubmit="return myfunction()" autocomplete="on">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" id="username" name="username" class="form-control" onkeyup="errHide()" autocomplete="username">
                    <div id="err_username" class="text-danger small">Username cannot be empty</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" id="password" name="password" class="form-control" onkeyup="errHide()" autocomplete="current-password">
                    <div id="err_password" class="text-danger small">Password cannot be empty</div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <p class="mt-3 text-center">
                <a href="register.php">Go to Register</a>
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
