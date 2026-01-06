<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "nethmi";

$con = new mysqli($servername, $db_username, $db_password, $dbname);
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$success = "";
$error = "";

// Fetch user titles for dropdown
$titles_res = $con->query("SELECT id, user_title FROM user_titles");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $dob = $_POST['dob'] ?? '';
    $phone_prefix = $_POST['phone_number_prefix'] ?? '';
	$phone_number_part = trim($_POST['phone_number'] ?? '');

// Combine full number if valid
if ($phone_prefix && $phone_number_part) {
    $phone_number = $phone_prefix . $phone_number_part;
} else {
    $phone_number = '';
}

    $gender = $_POST['gender'] ?? '';
    $user_title_id = $_POST['user_title_id'] ?: NULL;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (!$full_name) {
        $error = "Full name is required.";
	} elseif ($age < 0 || $age > 150) {
        $error = "Age must be between 0 and 150.";
    } elseif (!$dob) {
        $error = "Date of birth is required.";
		 } else {
        $dob_date = new DateTime($dob);
        $today = new DateTime();

        // Calculate 150 years ago from today
        $min_date = (clone $today)->modify('-150 years');

        if ($dob_date > $today) {
            $error = "Date of birth cannot be in the future.";
        } elseif ($dob_date < $min_date) {
            $error = "Date of birth cannot be more than 150 years ago.";
		} elseif (!$phone_prefix || !$phone_number_part) {
		$error = "Please select a phone prefix and enter the rest of your number.";
		} elseif (!preg_match('/^0(71|72|75|76|77|78)[0-9]{7}$/', $phone_number)) {
			$error = "Invalid Sri Lankan phone number format.";

		} elseif (!$gender) {
        $error = "Gender is required.";
		} elseif (!$email) {
        $error = "Email is required.";
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
		} elseif (!$password || !$confirm_password) {
        $error = "Password and Confirm Password are required.";
		} elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
		} else {
        // Check if username already exists
        $stmt_check = $con->prepare("SELECT id FROM user WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

		} if ($result_check->num_rows > 0) {
            $error = "Username '$username' is already taken. Please choose another.";
        } else {
            // Insert user if username is unique
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("INSERT INTO user (full_name, username, age, dob, phone_number, gender, user_title_id, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisssiss", $full_name, $username, $age, $dob, $phone_number, $gender, $user_title_id, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful. <a href='login.php'>Go to Login</a>";
            } else {
                $error = "Error: " . $con->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow" style="max-width: 600px; width: 100%;">
        <div class="card-body">
            <h1 class="text-center mb-3">Register</h1>
            <p class="text-center text-muted mb-4">Create your account to get started</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form action="register.php" method="post" autocomplete="on">
                <div class="mb-3">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="username">User Name:</label>
                    <input type="text" id="username" name="username" class="form-control">
                </div>
               <div class="mb-3">
				<label for="age">Age:</label>
				<input type="number" id="age" name="age" class="form-control" min="1" max="150" placeholder="Enter your age" required>
				</div>

                <div class="mb-3">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" class="form-control"
					max="<?= date('Y-m-d') ?>"
					min="<?= date('Y-m-d', strtotime('-150 years')) ?>">
                </div>
                <div class="mb-3">
			<label for="phone_number_prefix">Phone Number:</label>
			<div class="input-group">
			<select name="phone_number_prefix" id="phone_number_prefix" class="form-select" required>
            <option value="">-- Select Prefix --</option>
            <option value="071">Dialog (071)</option>
            <option value="077">Dialog (077)</option>
            <option value="078">Dialog (078)</option>
            <option value="072">Mobitel (072)</option>
            <option value="076">Mobitel/Etisalat (076)</option>
            <option value="075">Airtel (075)</option>
            <option value="074">Hutch (074)</option>
			</select>
				<input type="tel" id="phone_number" name="phone_number" class="form-control" 
               placeholder="Enter 7 digits" pattern="[0-9]{7}" maxlength="7" required>
		</div>
		</div>
                <div class="mb-3">
                    <label>Gender:</label><br>
                    <input type="radio" name="gender" id="gender_m" value="male"> Male
                    <input type="radio" name="gender" id="gender_f" value="female"> Female
                </div>
                <div class="mb-3">
                    <label for="user_title_id">User Title:</label>
                    <select name="user_title_id" id="user_title_id" class="form-control">
                        <option value="">-- Select Title --</option>
                        <?php while ($t = $titles_res->fetch_assoc()): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['user_title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>

            <p class="mt-3 text-center">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
