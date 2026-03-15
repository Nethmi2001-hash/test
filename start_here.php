<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Start - Monastery Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/modern-design.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700&family=Newsreader:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-orange: #d98967;
        --primary-orange-dark: #b86a48;
        --primary-orange-light: #f7eee7;
        --calm-sage: #9ab3aa;
        --calm-blue: #7fa4ad;
        --surface: #ffffff;
        --surface-soft: #f6f4f1;
        --green-500: #6aa095;
        --green-600: #557b74;
        --slate-50: #f8fafc;
        --slate-100: #f1f5f9;
        --slate-200: #e2e8f0;
        --slate-400: #94a3b8;
        --slate-500: #64748b;
        --slate-600: #475569;
        --slate-700: #334155;
        --slate-900: #0f172a;
        --border-radius: 12px;
        --border-radius-sm: 8px;
        --shadow-xs: 0 1px 3px rgba(15,23,42,0.08);
        --shadow-lg: 0 24px 40px -28px rgba(15,23,42,0.45);
    }
    body {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        background:
            radial-gradient(1200px 600px at 10% -10%, #f6efe8 0%, rgba(246,239,232,0) 60%),
            radial-gradient(900px 500px at 90% 0%, #eef3f2 0%, rgba(238,243,242,0) 55%),
            linear-gradient(180deg, #f8fafb 0%, #f2f6f4 100%);
        color: var(--slate-700);
        min-height: 100vh;
    }
    .landing-navbar {
        background: rgba(255,255,255,0.92);
        border-bottom: 1px solid var(--slate-200);
        padding: 0 0;
        box-shadow: 0 2px 8px rgba(15,23,42,0.04);
        backdrop-filter: blur(10px);
    }
    .landing-navbar .navbar-brand {
        font-family: 'Newsreader', 'Manrope', serif;
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--primary-orange-dark);
        letter-spacing: -0.3px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .brand-icon {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg, #d9b7a0 0%, #a8c4bf 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        box-shadow: 0 10px 18px -14px rgba(15,23,42,0.5);
    }
    .hero-section {
        padding: 60px 0 36px;
        text-align: center;
        background:
            linear-gradient(180deg, rgba(255,255,255,0.94) 0%, rgba(247,244,241,0.9) 100%),
            url('images/monks2.jpg') center center / cover no-repeat;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-xs);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--slate-100);
    }
    .hero-section::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -80px;
        top: -90px;
        background: radial-gradient(circle, rgba(217,183,160,0.4) 0%, rgba(217,183,160,0) 70%);
        pointer-events: none;
    }
    .hero-section h1 {
        font-family: 'Newsreader', 'Manrope', serif;
        font-weight: 700;
        font-size: 2.35rem;
        letter-spacing: -0.6px;
        color: var(--slate-900);
        margin-bottom: 12px;
    }
    .hero-section .subtitle {
        font-size: 1.1rem;
        color: var(--slate-500);
        margin-bottom: 24px;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        padding: 6px 14px;
        border-radius: 9999px;
        background: var(--primary-orange-light);
        color: var(--primary-orange-dark);
        border: 1px solid rgba(184,106,72,0.25);
    }
    .badge-status.badge-calm {
        background: #e7f1f2;
        color: #4c6c73;
        border-color: rgba(127,164,173,0.35);
    }
    .action-card {
        background: var(--surface);
        color: var(--slate-900);
        border: 1px solid var(--slate-100);
        border-radius: var(--border-radius);
        padding: 28px 24px;
        box-shadow: var(--shadow-xs);
        transition: transform 0.22s cubic-bezier(.4,0,.2,1), box-shadow 0.22s cubic-bezier(.4,0,.2,1);
        height: 100%;
        overflow: hidden;
        text-decoration: none;
        position: relative;
    }
    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
    }
    .action-card .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: #f0f4f2;
        color: var(--calm-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 18px;
        border: 1px solid #e1ece9;
    }
    .action-card h3 {
        font-family: 'Newsreader', 'Manrope', serif;
        font-weight: 600;
        font-size: 1.15rem;
        margin-bottom: 6px;
    }
    .action-card p {
        opacity: 0.88;
        font-size: 0.9rem;
        margin-bottom: 0;
        line-height: 1.5;
    }
    .action-card .step-pill {
        display: inline-block;
        background: #eef3f2;
        color: #4f6f68;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 3px 10px;
        border-radius: 9999px;
        margin-bottom: 12px;
        border: 1px solid rgba(154,179,170,0.35);
    }
    .bg-green, .bg-emerald, .bg-violet, .bg-amber, .bg-cyan {
        background: #fff !important;
        color: var(--slate-900) !important;
        border: 1px solid var(--slate-200);
    }
    .section-label {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--slate-400);
        margin-bottom: 16px;
    }
    .quick-link-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 10px;
    }
    .quick-link-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: var(--surface);
        border: 1px solid var(--slate-100);
        border-radius: var(--border-radius-sm);
        color: var(--slate-700);
        font-weight: 600;
        font-size: 0.82rem;
        box-shadow: var(--shadow-xs);
        transition: background 0.18s, transform 0.18s, box-shadow 0.18s, color 0.18s;
    }
    .quick-link-item:hover {
        background: #f5f1ec;
        color: var(--primary-orange-dark);
        border-color: #e7d7cc;
        transform: translateY(-1px);
        box-shadow: var(--shadow-lg);
    }
    .quick-link-item i {
        font-size: 1.1rem;
        color: var(--calm-blue);
        width: 20px;
        text-align: center;
    }
    .doc-card {
        background: var(--surface);
        border: 1px solid var(--slate-100);
        border-radius: var(--border-radius);
        padding: 24px;
        box-shadow: var(--shadow-xs);
    }
    .doc-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #fff;
        border: 1px solid var(--slate-200);
        border-radius: var(--border-radius-sm);
        color: var(--slate-700);
        font-weight: 500;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.18s ease;
    }
    .doc-link:hover {
        background: #f5efe9;
        border-color: #e6d7cc;
        color: var(--primary-orange-dark);
    }
    .checklist-card {
        background: var(--surface);
        border: 1px solid var(--slate-100);
        border-radius: var(--border-radius);
        padding: 24px 28px;
        box-shadow: var(--shadow-xs);
    }
    .checklist-card h5 {
        font-family: 'Newsreader', 'Manrope', serif;
        font-weight: 600;
        font-size: 1rem;
        color: var(--slate-800, #1e293b);
    }
    .checklist-card ol li {
        padding: 4px 0;
        color: var(--slate-600);
        font-size: 0.92rem;
    }
    .cta-banner {
        background: linear-gradient(135deg, #e7d4c6 0%, #c9d9d6 100%);
        border-radius: var(--border-radius);
        padding: 28px 32px;
        color: #1f2937;
        text-align: center;
        box-shadow: var(--shadow-xs);
        border: 1px solid #e4d6cb;
    }
    .cta-banner h4 {
        font-family: 'Newsreader', 'Manrope', serif;
        font-weight: 600;
        font-size: 1.2rem;
        margin-bottom: 8px;
    }
    .cta-banner p {
        margin-bottom: 0;
        opacity: 0.8;
        font-size: 0.92rem;
    }
    .landing-footer {
        text-align: center;
        padding: 24px 0 32px;
        color: var(--slate-500);
        font-size: 0.85rem;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .reveal {
        opacity: 0;
        animation: fadeUp 700ms ease forwards;
    }
    .reveal.delay-1 { animation-delay: 80ms; }
    .reveal.delay-2 { animation-delay: 160ms; }
    .reveal.delay-3 { animation-delay: 240ms; }
    .reveal.delay-4 { animation-delay: 320ms; }
    .reveal.delay-5 { animation-delay: 400ms; }
    .reveal.delay-6 { animation-delay: 480ms; }
    @media (prefers-reduced-motion: reduce) {
        .reveal { animation: none; opacity: 1; }
    }
    @media (max-width: 768px) {
        .hero-section h1 { font-size: 1.8rem; }
        .hero-section { padding: 40px 0 24px; }
    }
    @media (max-width: 992px) {
    }
</style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar landing-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="start_here.php">
            <span class="brand-icon"><i class="bi bi-heart-pulse"></i></span>
            Monastery Healthcare
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="login.php" class="btn btn-sm" style="border:1px solid var(--green-500); color:var(--green-600); font-weight:600; border-radius:8px; padding:6px 18px;">Log In</a>
            <a href="register.php" class="btn btn-sm text-white" style="background:var(--green-500); font-weight:600; border-radius:8px; padding:6px 18px;">Register</a>
        </div>
    </div>
</nav>

<div class="container" style="max-width: 1080px;">

    <!-- Hero -->
    <div class="hero-section reveal delay-1">
        <h1>Monastery Healthcare System</h1>
        <p class="subtitle">Giribawa Seela Suva Herath Bhikkhu Hospital</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <span class="badge-status">
                <i class="bi bi-check-circle-fill me-1"></i>90% Complete
            </span>
            <span class="badge-status badge-calm">
                <i class="bi bi-rocket-takeoff-fill me-1"></i>Ready to Test
            </span>
        </div>
    </div>

    <!-- Step 1: Primary CTA -->
    <div class="row g-3 mb-3 reveal delay-2">
        <div class="col-12">
            <a href="generate_sample_data.php" class="action-card bg-emerald">
                <div>
                    <span class="step-pill">Step 1 - Start Here</span>
                    <div class="card-icon"><i class="bi bi-database-fill-gear"></i></div>
                    <h3>Generate Sample Data</h3>
                    <p>Create 50+ sample donations, expenses, and appointments for testing reports</p>
                    <p style="margin-top: 10px;"><small><i class="bi bi-clock me-1"></i>Takes 5 seconds &bull; Run this FIRST</small></p>
                </div>
            </a>
        </div>
    </div>

    <!-- Steps 2 & 3 -->
    <div class="row g-3 mb-3 reveal delay-3">
        <div class="col-md-6">
            <a href="reports.php" class="action-card bg-green">
                <div>
                    <span class="step-pill">Step 2</span>
                    <div class="card-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <h3>View Reports &amp; Charts</h3>
                    <p>Financial analytics, appointment stats, donor rankings with beautiful charts</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="dashboard.php" class="action-card bg-violet">
                <div>
                    <span class="step-pill">Step 3</span>
                    <div class="card-icon"><i class="bi bi-speedometer2"></i></div>
                    <h3>Dashboard</h3>
                    <p>Overview with live statistics, charts, and today's appointments</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Extra Features -->
    <div class="row g-3 mb-4 reveal delay-4">
        <div class="col-md-6">
            <a href="donation_management.php" class="action-card bg-amber">
                <div>
                    <div class="card-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <h3>Test Online Payment</h3>
                    <p>Try PayHere sandbox with test cards (no real money)</p>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="chatbot.php" class="action-card bg-cyan">
                <div>
                    <div class="card-icon"><i class="bi bi-robot"></i></div>
                    <h3>AI Chatbot</h3>
                    <p>Test bilingual assistant (English &amp; Sinhala)</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="mb-4 reveal delay-5">
        <div class="section-label"><i class="bi bi-grid-3x3-gap-fill me-1"></i> Quick Access</div>
        <div class="quick-link-grid">
            <a href="login.php" class="quick-link-item"><i class="bi bi-box-arrow-in-right"></i> Login</a>
            <a href="donation_management.php" class="quick-link-item"><i class="bi bi-cash-coin"></i> Donations</a>
            <a href="bill_management.php" class="quick-link-item"><i class="bi bi-receipt"></i> Expenses</a>
            <a href="patient_appointments.php" class="quick-link-item"><i class="bi bi-calendar-check"></i> Appointments</a>
            <a href="monk_management.php" class="quick-link-item"><i class="bi bi-person-hearts"></i> Monks</a>
            <a href="doctor_management.php" class="quick-link-item"><i class="bi bi-person-badge"></i> Doctors</a>
            <a href="table.php" class="quick-link-item"><i class="bi bi-people"></i> Users</a>
        </div>
    </div>

    <!-- Documentation -->
    <div class="doc-card mb-4 reveal delay-6">
        <div class="section-label" style="color:var(--primary-orange-dark);"><i class="bi bi-book-half me-1"></i> Documentation &amp; Guides</div>
        <div class="d-flex flex-wrap gap-2">
            <a href="COMPLETE_TESTING_GUIDE.md" target="_blank" class="doc-link"><i class="bi bi-flag-fill"></i> Complete Testing Guide</a>
            <a href="PAYHERE_SETUP_GUIDE.md" target="_blank" class="doc-link"><i class="bi bi-credit-card"></i> PayHere Setup</a>
            <a href="LATEST_UPDATES.md" target="_blank" class="doc-link"><i class="bi bi-newspaper"></i> Latest Updates</a>
            <a href="PROJECT_STATUS.md" target="_blank" class="doc-link"><i class="bi bi-clipboard-check"></i> Project Status</a>
        </div>
    </div>

    <!-- Checklist -->
    <div class="checklist-card mb-4 reveal delay-5">
        <h5><i class="bi bi-list-check me-2" style="color:var(--green-500);"></i>Testing Checklist (15 Minutes)</h5>
        <ol style="margin-bottom: 0; padding-left: 20px; margin-top: 12px;">
            <li>Click "Generate Sample Data" above (5 seconds)</li>
            <li>Go to "View Reports" and see all 3 report types</li>
            <li>Try CSV Export and Print Report buttons</li>
            <li>Test PayHere payment with card: 4111 1111 1111 1111</li>
            <li>Chat with AI bot in English and Sinhala</li>
        </ol>
    </div>

    <!-- CTA Banner -->
    <div class="cta-banner mb-3 reveal delay-6">
        <h4><i class="bi bi-stars me-1"></i> All Features Ready!</h4>
        <p>90% Complete &bull; Reports Module &bull; PayHere Integration &bull; AI Chatbot</p>
        <p style="opacity:0.75; margin-top:6px; font-size:0.85rem;">Default Login: admin@monastery.lk / admin123</p>
    </div>

    <!-- Footer -->
    <div class="landing-footer reveal delay-4">
        Theruwan Saranai! May this helping-hand service bless all beings. 🙏
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
