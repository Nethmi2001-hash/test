<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include 'navbar.php';

// Database connection
require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

// Get dashboard statistics
$stats = [
    'total_monks' => 0,
    'total_doctors' => 0,
    'total_appointments' => 0,
    'total_donations' => 0,
    'pending_appointments' => 0,
    'completed_appointments' => 0,
    'monthly_donation_amount' => 0
];

// Get monks count
$result = $conn->query("SELECT COUNT(*) as count FROM monks WHERE status = 'active'");
if ($result) $stats['total_monks'] = $result->fetch_assoc()['count'];

// Get doctors count
$result = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'");
if ($result) $stats['total_doctors'] = $result->fetch_assoc()['count'];

// Get appointments count (this month)
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE MONTH(app_date) = MONTH(CURRENT_DATE()) AND YEAR(app_date) = YEAR(CURRENT_DATE())");
if ($result) $stats['total_appointments'] = $result->fetch_assoc()['count'];

// Get pending appointments
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'scheduled'");
if ($result) $stats['pending_appointments'] = $result->fetch_assoc()['count'];

// Get completed appointments
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed' AND MONTH(app_date) = MONTH(CURRENT_DATE())");
if ($result) $stats['completed_appointments'] = $result->fetch_assoc()['count'];

// Get monthly donations amount
$result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
if ($result) $stats['monthly_donation_amount'] = $result->fetch_assoc()['total'];

// Get today's appointments
$today_appointments = [];
$result = $conn->query("
    SELECT a.*, m.full_name as monk_name, d.full_name as doctor_name, a.app_time
    FROM appointments a
    JOIN monks m ON a.monk_id = m.monk_id
    JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.app_date = CURRENT_DATE() AND a.status = 'scheduled'
    ORDER BY a.app_time ASC
    LIMIT 5
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $today_appointments[] = $row;
    }
}

// Get weekly appointment data for chart
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE app_date = '$date'");
    $count = $result ? $result->fetch_assoc()['count'] : 0;
    $weekly_data[] = [
        'date' => date('D', strtotime($date)),
        'count' => $count
    ];
}

// Get monthly donation vs expenses data
$monthly_financial = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $donations = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc()['total'];
    $bills = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM bills WHERE DATE_FORMAT(bill_date, '%Y-%m') = '$month'")->fetch_assoc()['total'];
    $monthly_financial[] = [
        'month' => date('M', strtotime($month)),
        'donations' => $donations,
        'expenses' => $bills
    ];
}

// Get alerts and notifications
$alerts = [];

// Check for pending appointments (scheduled for today or past dates)
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'scheduled' AND app_date <= CURRENT_DATE()");
if ($result) {
    $pending_count = $result->fetch_assoc()['count'];
    if ($pending_count > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'bi-calendar-x',
            'message' => "You have $pending_count pending appointment(s) that need attention",
            'link' => 'patient_appointments.php'
        ];
    }
}

// Check for appointments scheduled for tomorrow
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'scheduled' AND app_date = DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY)");
if ($result) {
    $tomorrow_count = $result->fetch_assoc()['count'];
    if ($tomorrow_count > 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'bi-calendar-event',
            'message' => "$tomorrow_count appointment(s) scheduled for tomorrow",
            'link' => 'patient_appointments.php'
        ];
    }
}

// Check for inactive doctors
$result = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'inactive'");
if ($result) {
    $inactive_doctors = $result->fetch_assoc()['count'];
    if ($inactive_doctors > 0) {
        $alerts[] = [
            'type' => 'info',
            'icon' => 'bi-person-x',
            'message' => "$inactive_doctors doctor(s) currently inactive",
            'link' => 'table.php'
        ];
    }
}

// Positive feedback if no critical issues
if (count($alerts) == 0) {
    $alerts[] = [
        'type' => 'success',
        'icon' => 'bi-check-circle',
        'message' => 'All systems running smoothly. No pending issues.',
        'link' => '#'
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/sacred-care-theme.css">
    <link rel="stylesheet" href="assets/css/monastery-theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --monastery-green: #A67C52;
            --monastery-dark-green: #8D6844;
            --monastery-gold: #7A1E1E;
            --monastery-light-gold: #F5EFE6;
            --monastery-cream: #FAF8F3;
            --monastery-accent: #7A1E1E;
            --text-dark: #333;
            --text-light: #666;
        }

        body {
            background: linear-gradient(135deg, var(--monastery-cream) 0%, #E8DCC4 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-attachment: fixed;
        }

        .page-section {
            margin-top: 40px;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 15px 40px rgba(45, 80, 22, 0.4);
            position: relative;
            overflow: hidden;
            animation: slideInDown 0.6s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-emoji {
            font-size: 60px;
            display: inline-block;
            animation: pulse 2.5s ease-in-out infinite;
            margin-right: 20px;
        }

        .founder-strip {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            margin-top: -14px;
            margin-bottom: 26px;
            border: 1px solid rgba(110, 134, 98, 0.15);
            box-shadow: 0 12px 24px rgba(32, 42, 29, 0.12);
        }

        .founder-photo {
            width: 84px;
            height: 84px;
            object-fit: cover;
            border-radius: 14px;
            border: 3px solid #ECE5D8;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.1);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-top: 4px solid var(--monastery-gold);
            height: 100%;
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(45, 80, 22, 0.2);
            border-top-color: var(--monastery-green);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--monastery-green) 0%, #3d5d2d 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--monastery-light-gold);
            font-size: 32px;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.2);
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: var(--monastery-green);
            margin: 10px 0;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }

        .chart-card, .alert-card, .upcoming-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(45, 80, 22, 0.1);
            margin-bottom: 25px;
            animation: fadeInUp 0.6s ease-out;
            border-top: 4px solid var(--monastery-gold);
        }

        .chart-card h5, .alert-card h5, .upcoming-card h5 {
            color: var(--monastery-accent);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-card h5 i, .alert-card h5 i, .upcoming-card h5 i {
            font-size: 20px;
            color: var(--monastery-gold);
        }

        .alert-item {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid;
            text-decoration: none;
            color: inherit;
        }

        .alert-item:hover {
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-item.warning {
            background: #FFF4DE;
            border-left-color: #B89062;
        }

        .alert-item.info {
            background: #ECF2E8;
            border-left-color: var(--primary);
        }

        .alert-item.success {
            background: #E9F4EA;
            border-left-color: var(--success);
        }

        .alert-item i {
            font-size: 1.5rem;
            margin-right: 15px;
            min-width: 30px;
        }

        .appointment-item {
            padding: 15px;
            border-left: 3px solid var(--monastery-gold);
            background: #faf8f3;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        .appointment-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.1);
            border-left-width: 5px;
        }

        .appointment-time {
            font-weight: 600;
            color: var(--monastery-green);
            font-size: 1rem;
        }

        .appointment-monk {
            margin-top: 8px;
            font-size: 14px;
        }

        .quick-actions {
            text-align: center;
            margin: 40px 0;
            animation: fadeInUp 0.6s ease-out 0.5s both;
        }

        .quick-actions h5 {
            color: var(--monastery-accent);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .btn-quick {
            background: linear-gradient(135deg, var(--monastery-green) 0%, #3d5d2d 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 8px 5px;
            box-shadow: 0 5px 15px rgba(45, 80, 22, 0.2);
        }

        .btn-quick:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(45, 80, 22, 0.35);
            color: white;
        }

        .btn-quick:active {
            transform: translateY(-1px);
        }

        .monastery-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .no-data {
            text-align: center;
            color: var(--text-light);
            padding: 40px 20px;
        }

        .no-data i {
            font-size: 3rem;
            opacity: 0.2;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .welcome-card {
                padding: 25px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .btn-quick {
                font-size: 13px;
                padding: 10px 15px;
            }

            .monastery-image {
                height: 200px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid page-section px-4 pb-5">
    <!-- Monastery Image Hero -->
    <img src="images/img2.jpeg" alt="Seela Suwa Herath Monastery" class="monastery-image">

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="welcome-content">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <span class="welcome-emoji">🤝</span>
                <div>
                    <small style="opacity: 0.9;">
                        <i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?> • 
                        <i class="bi bi-clock"></i> <?= date('g:i A') ?>
                    </small>
                    <h1 style="margin: 10px 0; font-size: 2.2rem;">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                    <p style="margin: 5px 0; font-size: 1rem; opacity: 0.95;">
                        <i class="bi bi-shield-check"></i> Role: <strong><?= htmlspecialchars($_SESSION['role_name']) ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert" style="background: linear-gradient(135deg, rgba(110, 134, 98, 0.08) 0%, rgba(79, 102, 69, 0.05) 100%); border-left: 3px solid var(--primary); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <img src="images/img1.jpeg" alt="Founder" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
        <div style="font-size: 0.875rem; line-height: 1.4;">
            <div style="font-weight: 600; color: var(--primary);">Seela Suwa Herath Bikshu Gilan Arana</div>
            <div style="opacity: 0.75; font-size: 0.8rem;">Founded by Ven. Solewewa Chandrasiri Thero</div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-number"><?= $stats['total_monks'] ?></div>
                <div class="stat-label">Active Monks</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <div class="stat-number"><?= $stats['total_doctors'] ?></div>
                <div class="stat-label">Doctors on Staff</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-number"><?= $stats['total_appointments'] ?></div>
                <div class="stat-label">Appointments (Month)</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-number"><?= $stats['pending_appointments'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="alert-card">
                <h5>
                    <i class="bi bi-bell-fill"></i> Alerts & Notifications
                </h5>
                <?php foreach ($alerts as $alert): ?>
                    <a href="<?= $alert['link'] ?>" class="alert-item <?= $alert['type'] ?>">
                        <i class="bi <?= $alert['icon'] ?>"></i>
                        <span><?= $alert['message'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Charts and Appointments Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <h5>
                    <i class="bi bi-graph-up"></i> Weekly Appointment Trend
                </h5>
                <canvas id="weeklyChart" height="80"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="upcoming-card">
                <h5>
                    <i class="bi bi-calendar-event"></i> Today's Schedule
                </h5>
                <?php if (count($today_appointments) > 0): ?>
                    <?php foreach ($today_appointments as $apt): ?>
                        <div class="appointment-item">
                            <div class="appointment-time">
                                <i class="bi bi-clock"></i> <?= date('g:i A', strtotime($apt['app_time'])) ?>
                                <span style="float: right; background: var(--monastery-gold); color: var(--text-dark); padding: 2px 8px; border-radius: 5px; font-size: 11px;">Scheduled</span>
                            </div>
                            <div class="appointment-monk">
                                <strong><?= htmlspecialchars($apt['monk_name']) ?></strong><br>
                                <small>with Dr. <?= htmlspecialchars($apt['doctor_name']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-calendar-x"></i>
                        <p>No appointments today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Financial Chart -->
    <div class="row g-4 mb-4">
        <div class="col-lg-12">
            <div class="chart-card">
                <h5>
                    <i class="bi bi-graph-up-arrow"></i> Financial Overview (Last 6 Months)
                </h5>
                <canvas id="financialChart" height="50"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h5>⚡ Quick Actions</h5>
        <div class="row g-3 justify-content-center">
            <div class="col-auto">
                <a href="patient_appointments.php" class="btn-quick">
                    <i class="bi bi-calendar-plus"></i> New Appointment
                </a>
            </div>
            <div class="col-auto">
                <a href="donation_management.php" class="btn-quick">
                    <i class="bi bi-person-hearts"></i> Add Donation
                </a>
            </div>
            <div class="col-auto">
                <a href="bill_management.php" class="btn-quick">
                    <i class="bi bi-receipt"></i> Record Expense
                </a>
            </div>
            <div class="col-auto">
                <a href="doctor_availability.php" class="btn-quick">
                    <i class="bi bi-clock-history"></i> Doctor Schedule
                </a>
            </div>
            <div class="col-auto">
                <a href="monk_management.php" class="btn-quick">
                    <i class="bi bi-people"></i> Manage Monks
                </a>
            </div>
            <div class="col-auto">
                <a href="room_management.php" class="btn-quick">
                    <i class="bi bi-door-open"></i> Rooms
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Weekly Appointments Chart
const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($weekly_data, 'date')) ?>,
        datasets: [{
            label: 'Appointments',
            data: <?= json_encode(array_column($weekly_data, 'count')) ?>,
            backgroundColor: 'rgba(45, 80, 22, 0.1)',
            borderColor: '#2d5016',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#7A1E1E',
            pointBorderColor: '#A67C52',
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 },
                grid: { color: 'rgba(45, 80, 22, 0.05)' }
            },
            x: {
                grid: { color: 'rgba(45, 80, 22, 0.05)' }
            }
        }
    }
});

// Financial Chart
const financialCtx = document.getElementById('financialChart').getContext('2d');
new Chart(financialCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthly_financial, 'month')) ?>,
        datasets: [{
            label: 'Donations',
            data: <?= json_encode(array_column($monthly_financial, 'donations')) ?>,
            backgroundColor: '#2E7D32',
            borderRadius: 8,
            borderSkipped: false
        }, {
            label: 'Expenses',
            data: <?= json_encode(array_column($monthly_financial, 'expenses')) ?>,
            backgroundColor: '#7A1E1E',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: { boxWidth: 15, font: { size: 13 } }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(45, 80, 22, 0.05)' }
            },
            x: {
                grid: { color: 'rgba(45, 80, 22, 0.05)' }
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/notifications.js"></script>

</body>
</html>
<?php $conn->close(); ?>
