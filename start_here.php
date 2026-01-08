<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Quick Start - Monastery Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
        }
        body {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .main-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(245, 124, 0, 0.2);
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid var(--monastery-orange);
        }
        .header h1 {
            color: var(--monastery-saffron);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 1.2rem;
        }
        .action-card {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(245, 124, 0, 0.4);
            color: white;
        }
        .action-card h3 {
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        .action-card p {
            margin: 0;
            opacity: 0.9;
        }
        .action-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .step-number {
            background: white;
            color: var(--monastery-saffron);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .quick-links {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .quick-links h4 {
            color: var(--monastery-saffron);
            margin-bottom: 15px;
        }
        .quick-links a {
            display: inline-block;
            margin: 5px;
            padding: 8px 15px;
            background: white;
            border: 2px solid var(--monastery-orange);
            color: var(--monastery-saffron);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .quick-links a:hover {
            background: var(--monastery-saffron);
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            background: #28a745;
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="main-card">
    <div class="header">
        <h1>🪷 Monastery Healthcare System</h1>
        <p>Giribawa Seela Suva Herath Bhikkhu Hospital</p>
        <div style="margin-top: 15px;">
            <span class="status-badge">✅ 90% Complete</span>
            <span class="status-badge" style="background: #17a2b8;">🚀 Ready to Test</span>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <a href="generate_sample_data.php" class="action-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <div class="icon">🎯</div>
                <h3><span class="step-number">1</span> Generate Sample Data (Start Here!)</h3>
                <p>Create 50+ sample donations, expenses, and appointments for testing reports</p>
                <p style="margin-top: 10px;"><small><i class="bi bi-clock"></i> Takes 5 seconds • Run this FIRST</small></p>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <a href="reports.php" class="action-card">
                <div class="icon">📊</div>
                <h3><span class="step-number">2</span> View Reports & Charts</h3>
                <p>Financial analytics, appointment stats, donor rankings with beautiful charts</p>
            </a>
        </div>
        <div class="col-md-6">
            <a href="dashboard.php" class="action-card" style="background: linear-gradient(135deg, #6610f2 0%, #6f42c1 100%);">
                <div class="icon">🏠</div>
                <h3><span class="step-number">3</span> Dashboard</h3>
                <p>Overview with live statistics, charts, and today's appointments</p>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <a href="donation_management.php" class="action-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                <div class="icon">💰</div>
                <h3>Test Online Payment</h3>
                <p>Try PayHere sandbox with test cards (no real money)</p>
            </a>
        </div>
        <div class="col-md-6">
            <a href="chatbot.php" class="action-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                <div class="icon">🤖</div>
                <h3>AI Chatbot</h3>
                <p>Test bilingual assistant (English & Sinhala)</p>
            </a>
        </div>
    </div>

    <div class="quick-links">
        <h4><i class="bi bi-link-45deg"></i> Quick Access Links</h4>
        <div>
            <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <a href="donation_management.php"><i class="bi bi-cash-coin"></i> Donations</a>
            <a href="bill_management.php"><i class="bi bi-receipt"></i> Expenses</a>
            <a href="patient_appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
            <a href="monk_management.php"><i class="bi bi-person-hearts"></i> Monks</a>
            <a href="doctor_management.php"><i class="bi bi-person-badge"></i> Doctors</a>
            <a href="table.php"><i class="bi bi-people"></i> Users</a>
        </div>
    </div>

    <div class="quick-links" style="background: #fff3cd; border: 2px solid #ffc107;">
        <h4><i class="bi bi-book"></i> Documentation & Guides</h4>
        <div>
            <a href="COMPLETE_TESTING_GUIDE.md" target="_blank"><i class="bi bi-flag"></i> Complete Testing Guide</a>
            <a href="PAYHERE_SETUP_GUIDE.md" target="_blank"><i class="bi bi-credit-card"></i> PayHere Setup</a>
            <a href="LATEST_UPDATES.md" target="_blank"><i class="bi bi-newspaper"></i> Latest Updates</a>
            <a href="PROJECT_STATUS.md" target="_blank"><i class="bi bi-clipboard-check"></i> Project Status</a>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h5><i class="bi bi-info-circle"></i> Testing Checklist (15 Minutes):</h5>
        <ol style="margin-bottom: 0;">
            <li>Click "Generate Sample Data" above (5 seconds)</li>
            <li>Go to "View Reports" and see all 3 report types</li>
            <li>Try CSV Export and Print Report buttons</li>
            <li>Test PayHere payment with card: 4111 1111 1111 1111</li>
            <li>Chat with AI bot in English and Sinhala</li>
        </ol>
    </div>

    <div class="text-center mt-4" style="padding: 20px; background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%); border-radius: 10px; color: white;">
        <h4>🎉 All Features Ready!</h4>
        <p style="margin: 10px 0;">90% Complete • Reports Module • PayHere Integration • AI Chatbot</p>
        <p style="margin: 0; opacity: 0.9;">Default Login: admin@monastery.lk / admin123</p>
    </div>

    <div class="text-center mt-3">
        <p style="color: #666;"><small>🪷 Theruwan Saranai! May the Triple Gem bless this noble work. 🙏</small></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
