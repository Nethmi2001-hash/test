<?php
session_start();
include 'navbar.php';

// Access control
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
require_once 'includes/db_config.php';
$conn = getDBConnection();

$success = "";
$error = "";
$preview_data = [];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file
    $allowed_extensions = ['xlsx', 'xls', 'csv'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_extensions)) {
        $error = "Please upload a valid Excel file (.xlsx, .xls, or .csv)";
    } elseif ($file['size'] > 5000000) { // 5MB limit
        $error = "File size must be less than 5MB";
    } else {
        // Process CSV file (easiest for PHP)
        if ($file_ext == 'csv' || isset($_POST['confirm_import'])) {
            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle); // Get header row
            
            $imported = 0;
            $errors_found = [];
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                // Skip empty rows
                if (empty(array_filter($row))) continue;
                
                // Map columns (adjust based on your template)
                $data = array_combine($headers, $row);
                
                // Validate required fields
                if (empty($data['Full Name'])) {
                    $errors_found[] = "Row skipped: Missing full name";
                    continue;
                }
                
                // Prepare data
                $full_name = trim($data['Full Name']);
                $title_id = !empty($data['Title ID']) ? intval($data['Title ID']) : 1;
                $ordination_date = !empty($data['Ordination Date']) ? $data['Ordination Date'] : null;
                $birth_date = !empty($data['Birth Date']) ? $data['Birth Date'] : null;
                $phone = trim($data['Phone'] ?? '');
                $emergency_contact = trim($data['Emergency Contact'] ?? '');
                $blood_group = trim($data['Blood Group'] ?? '');
                $allergies = trim($data['Allergies'] ?? '');
                $chronic_conditions = trim($data['Chronic Conditions'] ?? '');
                $current_medications = trim($data['Current Medications'] ?? '');
                $status = 'active';
                
                // Insert into database
                $stmt = $conn->prepare("
                    INSERT INTO monks (
                        full_name, title_id, ordination_date, birth_date, phone, 
                        emergency_contact, blood_group, allergies, chronic_conditions, 
                        current_medications, status, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $created_by = $_SESSION['user_id'];
                $stmt->bind_param(
                    "sisssssssssi",
                    $full_name, $title_id, $ordination_date, $birth_date, $phone,
                    $emergency_contact, $blood_group, $allergies, $chronic_conditions,
                    $current_medications, $status, $created_by
                );
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors_found[] = "Failed to import: $full_name - " . $stmt->error;
                }
                $stmt->close();
            }
            
            fclose($handle);
            
            $success = "Successfully imported $imported monk(s)!";
            if (count($errors_found) > 0) {
                $error = "Some errors occurred: " . implode(', ', array_slice($errors_found, 0, 5));
            }
        } else {
            // Preview mode
            $handle = fopen($file['tmp_name'], 'r');
            $headers = fgetcsv($handle);
            
            $count = 0;
            while (($row = fgetcsv($handle)) !== FALSE && $count < 5) {
                if (!empty(array_filter($row))) {
                    $preview_data[] = array_combine($headers, $row);
                    $count++;
                }
            }
            fclose($handle);
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Monk Data - Excel Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/premium-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
</head>
<body>

<div class="container-fluid mt-4 mb-5 px-4">
    <!-- Page Header -->
    <div class="page-header">
        <h2 class="mb-0"><i class="bi bi-file-earmark-excel"></i> Import Monk Data from Excel</h2>
        <p class="mb-0 mt-1 opacity-75">Bulk upload monk information from Excel spreadsheet</p>
    </div>

    <!-- Founder Identity Strip -->
    <div class="alert" style="background: linear-gradient(135deg, rgba(110, 134, 98, 0.08) 0%, rgba(79, 102, 69, 0.05) 100%); border-left: 3px solid var(--primary); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <img src="images/img1.jpeg" alt="Founder" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
        <div style="font-size: 0.875rem; line-height: 1.4;">
            <div style="font-weight: 600; color: var(--primary);">Seela Suwa Herath Bikshu Gilan Arana</div>
            <div style="opacity: 0.75; font-size: 0.8rem;">Founded by Ven. Solewewa Chandrasiri Thero</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <a href="monk_management.php" class="btn btn-success btn-sm mt-2">View Monks</a>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Upload Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border-bottom: 3px solid var(--accent);">
                    <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload Excel File</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <div class="upload-area" onclick="document.getElementById('excel_file').click()">
                            <i class="bi bi-file-earmark-excel"></i>
                            <h4>Click to Select Excel File</h4>
                            <p class="text-muted">or drag and drop here</p>
                            <p><small>Supported: .xlsx, .xls, .csv (Max 5MB)</small></p>
                        </div>
                        <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv" style="display: none;" onchange="this.form.submit()">
                        
                        <?php if (!empty($preview_data)): ?>
                            <input type="hidden" name="confirm_import" value="1">
                            <div class="mt-4">
                                <h5><i class="bi bi-eye"></i> Preview (First 5 rows):</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <?php foreach (array_keys($preview_data[0]) as $header): ?>
                                                    <th><?= htmlspecialchars($header) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($preview_data as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $cell): ?>
                                                        <td><?= htmlspecialchars($cell) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Confirm & Import Data
                                </button>
                                <a href="import_monks.php" class="btn btn-secondary btn-lg">Cancel</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Instructions -->
            <div class="step-card">
                <h5><i class="bi bi-info-circle"></i> How to Import</h5>
                <div class="mt-3">
                    <div class="d-flex align-items-start mb-3">
                        <span class="step-number">1</span>
                        <div>
                            <strong>Download Template</strong>
                            <p class="text-muted mb-0 small">Get the Excel template with correct columns</p>
                            <a href="monk_import_template.csv" class="btn btn-sm btn-outline-primary mt-2" download>
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <span class="step-number">2</span>
                        <div>
                            <strong>Fill Your Data</strong>
                            <p class="text-muted mb-0 small">Enter monk information in the template</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3">
                        <span class="step-number">3</span>
                        <div>
                            <strong>Upload File</strong>
                            <p class="text-muted mb-0 small">Click upload area and select your file</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <span class="step-number">4</span>
                        <div>
                            <strong>Review & Import</strong>
                            <p class="text-muted mb-0 small">Preview data and confirm import</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Required Columns -->
            <div class="step-card">
                <h6><i class="bi bi-list-check"></i> Required Columns</h6>
                <ul class="small text-muted">
                    <li><strong>Full Name</strong> (Required)</li>
                    <li>Title ID</li>
                    <li>Ordination Date</li>
                    <li>Birth Date</li>
                    <li>Phone</li>
                    <li>Emergency Contact</li>
                    <li>Blood Group</li>
                    <li>Allergies</li>
                    <li>Chronic Conditions</li>
                    <li>Current Medications</li>
                </ul>
            </div>

            <!-- Tips -->
            <div class="step-card">
                <h6><i class="bi bi-lightbulb"></i> Tips</h6>
                <ul class="small text-muted">
                    <li>Use the template for correct format</li>
                    <li>Date format: YYYY-MM-DD</li>
                    <li>Remove sample data before filling</li>
                    <li>Check for duplicates</li>
                    <li>Preview before importing</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Drag and drop support
const uploadArea = document.querySelector('.upload-area');
const fileInput = document.getElementById('excel_file');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    uploadArea.classList.add('border-primary', 'bg-light');
}

function unhighlight(e) {
    uploadArea.classList.remove('border-primary', 'bg-light');
}

uploadArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    document.getElementById('uploadForm').submit();
}
</script>

</body>
</html>
