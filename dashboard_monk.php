<?php
if (!isset($_SESSION['logged_in']) || (basename($_SERVER['PHP_SELF']) === 'dashboard_monk.php')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: login.php");
        exit();
    }
}
require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['username'] ?? 'Monk';
$userEmail = $_SESSION['email'] ?? '';

// Try to find linked monk profile by name match
$monk = null;
$monk_id = null;
$r = $conn->query("SELECT * FROM monks WHERE full_name LIKE '%" . $conn->real_escape_string($userName) . "%' AND status = 'active' LIMIT 1");
if ($r && $r->num_rows > 0) {
    $monk = $r->fetch_assoc();
    $monk_id = $monk['monk_id'];
}

// Stats
$stats = [
    'upcoming_appointments' => 0,
    'completed_appointments' => 0,
    'total_records' => 0,
    'assigned_doctors' => 0
];

if ($monk_id) {
    $r = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE monk_id = $monk_id AND status = 'scheduled' AND app_date >= CURRENT_DATE()");
    if ($r) $stats['upcoming_appointments'] = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE monk_id = $monk_id AND status = 'completed'");
    if ($r) $stats['completed_appointments'] = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(*) as c FROM medical_records WHERE monk_id = $monk_id");
    if ($r) $stats['total_records'] = $r->fetch_assoc()['c'];

    $r = $conn->query("SELECT COUNT(DISTINCT doctor_id) as c FROM appointments WHERE monk_id = $monk_id");
    if ($r) $stats['assigned_doctors'] = $r->fetch_assoc()['c'];

    // Upcoming appointments list
    $upcoming = [];
    $r = $conn->query("
        SELECT a.*, d.full_name as doctor_name, d.specialization,
               rm.name as room_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN room_slots rs ON a.room_slot_id = rs.room_slot_id
        LEFT JOIN rooms rm ON rs.room_id = rm.room_id
        WHERE a.monk_id = $monk_id AND a.status = 'scheduled' AND a.app_date >= CURRENT_DATE()
        ORDER BY a.app_date ASC, a.app_time ASC
        LIMIT 10
    ");
    if ($r) while ($row = $r->fetch_assoc()) $upcoming[] = $row;

    // Recent medical records
    $records = [];
    $r = $conn->query("
        SELECT mr.*, d.full_name as doctor_name
        FROM medical_records mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        WHERE mr.monk_id = $monk_id
        ORDER BY mr.visit_date DESC
        LIMIT 5
    ");
    if ($r) while ($row = $r->fetch_assoc()) $records[] = $row;

    // Past appointments
    $past = [];
    $r = $conn->query("
        SELECT a.*, d.full_name as doctor_name, d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.monk_id = $monk_id AND a.status = 'completed'
        ORDER BY a.app_date DESC
        LIMIT 5
    ");
    if ($r) while ($row = $r->fetch_assoc()) $past[] = $row;

    // Monthly appointment data for chart
    $monthly_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $r = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE monk_id = $monk_id AND DATE_FORMAT(app_date, '%Y-%m') = '$month'");
        $monthly_data[] = [
            'month' => date('M', strtotime($month)),
            'count' => $r ? $r->fetch_assoc()['c'] : 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monk Dashboard - Seela Suwa Herath</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<?php if (!$monk): ?>
    <!-- No Monk Profile Linked -->
    <div class="welcome-card animate-fade-in" style="border-left: 4px solid var(--accent-500);">
        <h2><i class="bi bi-exclamation-triangle me-2"></i>Profile Not Linked</h2>
        <p>Your account (<strong><?= htmlspecialchars($userName) ?></strong>) hasn't been linked to a monk profile yet. Please contact the administrator to link your account.</p>
    </div>

    <!-- General Monastery Info -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="modern-card animate-fade-in">
                <div class="card-header-modern"><h6><i class="bi bi-info-circle me-2"></i>About the System</h6></div>
                <div class="card-body-modern" style="padding:24px;">
                    <p style="font-size:14px;line-height:1.8;color:var(--text-secondary);">
                        This system manages your healthcare records, doctor appointments, and medical history. 
                        Once your profile is linked, you'll be able to:
                    </p>
                    <ul style="font-size:13px;color:var(--text-secondary);line-height:2;">
                        <li>View upcoming doctor appointments</li>
                        <li>Access your medical records and history</li>
                        <li>See your health summary (blood group, allergies, etc.)</li>
                        <li>Track your appointment history</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <a href="chatbot.php" class="quick-action-card" style="height:100%;min-height:200px;">
                <div class="quick-action-icon" style="background:#cffafe;color:#0891b2;width:56px;height:56px;font-size:24px;"><i class="bi bi-robot"></i></div>
                <span class="quick-action-label" style="font-size:15px;">Ask AI Assistant</span>
                <span style="font-size:12px;color:var(--text-secondary);">Get help with health queries</span>
            </a>
        </div>
    </div>
<?php else: ?>

    <!-- Welcome -->
    <div class="welcome-card animate-fade-in">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div>
                <h2><i class="bi bi-heart-pulse me-2"></i>Ayubowan, <?= htmlspecialchars($monk['full_name']) ?>!</h2>
                <p style="margin:0;">Your health dashboard and appointment overview.</p>
            </div>
            <div class="welcome-date">
                <i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?>
            </div>
        </div>
    </div>

    <!-- Health Summary Card -->
    <div class="modern-card mb-4 animate-fade-in">
        <div class="card-header-modern">
            <h6><i class="bi bi-clipboard2-pulse me-2"></i>Health Summary</h6>
        </div>
        <div class="card-body-modern" style="padding:24px;">
            <div class="row g-4">
                <div class="col-md-3">
                    <div style="text-align:center;padding:16px;background:var(--bg-secondary);border-radius:var(--border-radius-sm);">
                        <i class="bi bi-droplet-fill" style="font-size:28px;color:#dc2626;"></i>
                        <div style="font-size:24px;font-weight:800;margin-top:8px;"><?= htmlspecialchars($monk['blood_group'] ?? 'N/A') ?></div>
                        <div style="font-size:12px;color:var(--text-secondary);">Blood Group</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="text-align:center;padding:16px;background:var(--bg-secondary);border-radius:var(--border-radius-sm);">
                        <i class="bi bi-calendar-heart" style="font-size:28px;color:#7c3aed;"></i>
                        <div style="font-size:24px;font-weight:800;margin-top:8px;">
                            <?php 
                            if ($monk['dob']) {
                                $age = date_diff(date_create($monk['dob']), date_create('now'))->y;
                                echo $age;
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                        <div style="font-size:12px;color:var(--text-secondary);">Age</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="padding:16px;background:#fef2f2;border-radius:var(--border-radius-sm);height:100%;">
                        <div style="font-weight:700;font-size:13px;color:#dc2626;margin-bottom:6px;">
                            <i class="bi bi-exclamation-triangle me-1"></i>Allergies
                        </div>
                        <div style="font-size:13px;color:var(--text-secondary);">
                            <?= htmlspecialchars($monk['allergies'] ?? 'None reported') ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div style="padding:16px;background:#fffbeb;border-radius:var(--border-radius-sm);height:100%;">
                        <div style="font-weight:700;font-size:13px;color:#d97706;margin-bottom:6px;">
                            <i class="bi bi-clipboard2-pulse me-1"></i>Chronic Conditions
                        </div>
                        <div style="font-size:13px;color:var(--text-secondary);">
                            <?= htmlspecialchars($monk['chronic_conditions'] ?? 'None reported') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4 stagger-children">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #0284c7;">
                <div class="stat-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-calendar-event"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Upcoming Appointments</div>
                    <div class="stat-value"><?= $stats['upcoming_appointments'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #f97316;">
                <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Completed Visits</div>
                    <div class="stat-value"><?= $stats['completed_appointments'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #7c3aed;">
                <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-file-medical"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Medical Records</div>
                    <div class="stat-value"><?= $stats['total_records'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card" style="--stat-color: #d97706;">
                <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="bi bi-person-badge"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Assigned Doctors</div>
                    <div class="stat-value"><?= $stats['assigned_doctors'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments + Chart -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="modern-card animate-fade-in" style="height:100%;">
                <div class="card-header-modern">
                    <h6><i class="bi bi-calendar2-check me-2"></i>Upcoming Appointments</h6>
                    <span class="badge-modern badge-primary"><?= count($upcoming) ?></span>
                </div>
                <div class="card-body-modern" style="padding:0;">
                    <?php if (count($upcoming) > 0): ?>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr><th>Date & Time</th><th>Doctor</th><th>Type</th><th>Room</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $apt): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700;color:var(--primary-600);">
                                            <?= date('M j, Y', strtotime($apt['app_date'])) ?>
                                        </div>
                                        <div style="font-size:12px;color:var(--text-secondary);">
                                            <i class="bi bi-clock me-1"></i><?= date('g:i A', strtotime($apt['app_time'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;"><?= htmlspecialchars($apt['doctor_name']) ?></div>
                                        <div style="font-size:12px;color:var(--text-secondary);"><?= htmlspecialchars($apt['specialization']) ?></div>
                                    </td>
                                    <td><span class="badge-modern badge-info"><?= htmlspecialchars($apt['specialization']) ?></span></td>
                                    <td><span class="badge-modern badge-neutral"><?= htmlspecialchars($apt['room_name'] ?? 'TBD') ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding:48px;">
                            <i class="bi bi-calendar-check" style="font-size:48px;color:var(--primary-400);"></i>
                            <h5 style="font-size:16px;margin-top:16px;">No upcoming appointments</h5>
                            <p style="font-size:13px;color:var(--text-secondary);">You're all clear for now</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="chart-card animate-fade-in" style="height:100%;">
                <div class="chart-header">
                    <h6><i class="bi bi-bar-chart me-2"></i>Visit History</h6>
                    <span class="badge-modern badge-neutral">6 months</span>
                </div>
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Medical Records -->
    <?php if (count($records) > 0): ?>
    <div class="modern-card mb-4 animate-fade-in">
        <div class="card-header-modern">
            <h6><i class="bi bi-file-medical me-2"></i>Recent Medical Records</h6>
        </div>
        <div class="card-body-modern" style="padding:16px;">
            <?php foreach ($records as $rec): ?>
            <div style="padding:16px;border:1px solid var(--border-color);border-radius:var(--border-radius-sm);margin-bottom:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <div>
                        <span style="font-weight:700;font-size:14px;"><?= date('M j, Y', strtotime($rec['visit_date'])) ?></span>
                        <span style="color:var(--text-secondary);font-size:13px;margin-left:8px;">by Dr. <?= htmlspecialchars($rec['doctor_name']) ?></span>
                    </div>
                    <?php if ($rec['follow_up_date']): ?>
                        <span class="badge-modern badge-warning" style="font-size:11px;">
                            <i class="bi bi-calendar-event me-1"></i>Follow-up: <?= date('M j', strtotime($rec['follow_up_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="row g-3" style="font-size:13px;">
                    <?php if ($rec['diagnosis']): ?>
                    <div class="col-md-4">
                        <div style="font-weight:600;color:var(--primary-600);margin-bottom:4px;">Diagnosis</div>
                        <div style="color:var(--text-secondary);"><?= htmlspecialchars($rec['diagnosis']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($rec['symptoms']): ?>
                    <div class="col-md-4">
                        <div style="font-weight:600;color:#d97706;margin-bottom:4px;">Symptoms</div>
                        <div style="color:var(--text-secondary);"><?= htmlspecialchars($rec['symptoms']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($rec['medication']): ?>
                    <div class="col-md-4">
                        <div style="font-weight:600;color:#f97316;margin-bottom:4px;">Medication</div>
                        <div style="color:var(--text-secondary);"><?= htmlspecialchars($rec['medication']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Past Appointments -->
    <?php if (count($past) > 0): ?>
    <div class="modern-card mb-4 animate-fade-in">
        <div class="card-header-modern">
            <h6><i class="bi bi-clock-history me-2"></i>Recent Visit History</h6>
        </div>
        <div class="card-body-modern" style="padding:16px;">
            <?php foreach ($past as $apt): ?>
            <div style="padding:12px 16px;border:1px solid var(--border-color);border-radius:var(--border-radius-sm);margin-bottom:10px;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-weight:600;font-size:13.5px;">Dr. <?= htmlspecialchars($apt['doctor_name']) ?></div>
                    <div style="font-size:12px;color:var(--text-secondary);">
                        <?= date('M j, Y', strtotime($apt['app_date'])) ?> at <?= date('g:i A', strtotime($apt['app_time'])) ?>
                        &bull; <?= htmlspecialchars($apt['specialization']) ?>
                    </div>
                </div>
                <span class="badge-modern badge-success badge-dot">Completed</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4 stagger-children">
        <div class="col-xl-4 col-md-6">
            <a href="patient_appointments.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-calendar2-check"></i></div>
                <span class="quick-action-label">My Appointments</span>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="doctor_management.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-person-badge"></i></div>
                <span class="quick-action-label">View Doctors</span>
            </a>
        </div>
        <div class="col-xl-4 col-md-6">
            <a href="chatbot.php" class="quick-action-card">
                <div class="quick-action-icon" style="background:#cffafe;color:#0891b2;"><i class="bi bi-robot"></i></div>
                <span class="quick-action-label">AI Health Assistant</span>
            </a>
        </div>
    </div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<script>
<?php if ($monk_id): ?>
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthly_data, 'month')) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode(array_column($monthly_data, 'count')) ?>,
            backgroundColor: 'rgba(124, 58, 237, 0.7)',
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 28
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
