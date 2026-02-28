<?php
/**
 * Public Donor Portal - No Login Required
 * Allows anyone to make donations online
 */
require_once __DIR__ . '/includes/db_config.php';
session_start();

$con = getDBConnection();

// Fetch recent verified donations (public view)
$recent_donations_query = "SELECT d.donor_name, d.amount, d.created_at, c.name AS category_name 
                           FROM donations d
                           LEFT JOIN categories c ON d.category_id = c.category_id
                           WHERE d.status IN ('paid', 'verified')
                           ORDER BY d.created_at DESC 
                           LIMIT 10";
$recent_donations = $con->query($recent_donations_query);

// Fetch donation categories
$categories_query = "SELECT category_id, name, description 
                     FROM categories 
                     WHERE type = 'donation' 
                     ORDER BY name";
$categories = $con->query($categories_query);

// Get total donations stats
$stats_query = "SELECT 
                    COUNT(*) as total_donations,
                    SUM(amount) as total_amount,
                    COUNT(DISTINCT donor_email) as unique_donors
                FROM donations 
                WHERE status IN ('paid', 'verified')";
$stats_result = $con->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate - Seela Suwa Herath Bikshu Gilan Arana</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/modern-design.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background: var(--slate-50);
            color: var(--slate-800);
            margin: 0;
        }

        /* ---- Top Navigation ---- */
        .public-topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--slate-200);
            box-shadow: var(--shadow-xs);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .public-topbar .topbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary-700);
            font-weight: 700;
            font-size: 1.15rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .topbar-brand i {
            font-size: 1.5rem;
            color: var(--primary-500);
        }
        .topbar-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .topbar-links a {
            text-decoration: none;
            color: var(--slate-600);
            font-weight: 500;
            font-size: 0.9rem;
            padding: 8px 16px;
            border-radius: var(--border-radius-full);
            transition: all 0.2s ease;
        }
        .topbar-links a:hover {
            background: var(--primary-50);
            color: var(--primary-700);
        }
        .topbar-links .btn-login {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1a3a2a;
            padding: 8px 20px;
            border-radius: var(--border-radius-full);
            font-weight: 700;
        }
        .topbar-links .btn-login:hover {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #1a3a2a;
        }

        /* ---- Hero Section ---- */
        .hero-section {
            background: linear-gradient(160deg, #1a3a2a 0%, #0f3d1e 30%, #1a4a2a 60%, #0d2818 100%);
            color: #fff;
            padding: 100px 24px 110px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 25% 0%, rgba(251, 191, 36, 0.14) 0%, transparent 55%),
                radial-gradient(ellipse at 75% 100%, rgba(52, 211, 153, 0.10) 0%, transparent 55%),
                radial-gradient(circle at 50% 50%, rgba(255,255,255,0.02) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-section::after {
            content: '\1FAB7';
            position: absolute;
            font-size: 14rem;
            opacity: 0.035;
            top: -30px;
            right: -40px;
            pointer-events: none;
            transform: rotate(-15deg);
        }
        .hero-section .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(251, 191, 36, 0.14);
            border: 1px solid rgba(251, 191, 36, 0.30);
            border-radius: var(--border-radius-full);
            padding: 8px 22px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 24px;
            backdrop-filter: blur(6px);
            color: #fcd34d;
        }
        .hero-section h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 3.1rem;
            font-weight: 800;
            max-width: 750px;
            margin: 0 auto 18px;
            line-height: 1.18;
            text-shadow: 0 2px 24px rgba(0,0,0,0.18);
        }
        .hero-section h1 .hero-highlight {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-section .hero-desc {
            font-size: 1.12rem;
            font-weight: 400;
            color: rgba(255,255,255,0.78);
            max-width: 620px;
            margin: 0 auto 36px;
            line-height: 1.65;
        }
        .hero-section .btn-hero {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #1a3a2a;
            font-weight: 800;
            padding: 16px 40px;
            border-radius: var(--border-radius-full);
            font-size: 1.08rem;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 20px rgba(251, 191, 36, 0.30);
        }
        .hero-section .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(251, 191, 36, 0.40);
            color: #1a3a2a;
        }
        .hero-trust {
            display: flex;
            justify-content: center;
            gap: 28px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .hero-trust span {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.85rem;
            font-weight: 500;
            color: rgba(255,255,255,0.60);
        }
        .hero-trust span i { color: rgba(251, 191, 36, 0.70); font-size: 0.92rem; }

        /* ---- Founder Highlight ---- */
        .founder-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-xl);
            padding: 28px 32px;
            margin-top: -52px;
            position: relative;
            z-index: 5;
            box-shadow: 0 12px 40px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--slate-200);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            border-left: 4px solid #fbbf24;
        }
        .founder-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
            border: 3px solid var(--primary-100);
        }

        /* ---- Stats Section ---- */
        .stats-section {
            padding: 60px 24px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--slate-100);
        }
        .stats-section-label {
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--slate-800);
            margin-bottom: 28px;
        }
        .stats-section-label i { color: var(--accent-500, #f59e0b); }
        .stat-card {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--border-radius-lg);
            padding: 28px 20px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .stat-icon.green  { background: var(--primary-100); color: var(--primary-600); }
        .stat-icon.amber  { background: var(--accent-100);  color: var(--accent-600);  }
        .stat-icon.blue   { background: var(--info-light);   color: var(--info);        }
        .stat-number {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--slate-800);
        }
        .stat-label {
            color: var(--slate-500);
            font-size: 0.88rem;
            font-weight: 500;
            margin-top: 4px;
        }

        /* ---- Donation Form ---- */
        .donation-form-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--slate-200);
            padding: 40px 36px;
            max-width: 780px;
            margin: 0 auto;
        }
        .form-section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--slate-800);
            text-align: center;
            margin-bottom: 8px;
        }
        .form-section-desc {
            text-align: center;
            color: var(--slate-500);
            margin-bottom: 32px;
            font-size: 0.95rem;
        }
        .form-control-modern {
            border: 1.5px solid var(--slate-200);
            border-radius: var(--border-radius-sm);
            padding: 12px 16px;
            font-size: 0.95rem;
            color: var(--slate-800);
            background: var(--slate-50);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-control-modern:focus {
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
            background: #fff;
            outline: none;
        }
        .form-control-modern::placeholder { color: var(--slate-400); }
        .form-label-modern {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--slate-700);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-label-modern i { color: var(--primary-500); font-size: 0.95rem; }

        /* Category Cards */
        .category-card {
            border: 2px solid var(--slate-200);
            border-radius: var(--border-radius);
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--slate-50);
        }
        .category-card:hover {
            border-color: var(--primary-300);
            background: var(--primary-50);
        }
        .category-card.selected {
            border-color: var(--primary-500);
            background: var(--primary-50);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
        }
        .category-card strong { color: var(--slate-700); font-size: 0.92rem; }
        .category-card p { color: var(--slate-500); font-size: 0.82rem; }

        /* Quick Amount Buttons */
        .btn-amount {
            border: 1.5px solid var(--slate-200);
            border-radius: var(--border-radius-full);
            padding: 6px 18px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--slate-600);
            background: var(--slate-50);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-amount:hover {
            border-color: var(--primary-400);
            color: var(--primary-700);
            background: var(--primary-50);
        }

        /* Primary Button */
        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-700) 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            padding: 14px 24px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(5, 150, 105, 0.3);
            color: #fff;
        }

        /* Info Alert */
        .alert-modern {
            background: var(--info-light);
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: var(--border-radius);
            padding: 16px 20px;
            font-size: 0.88rem;
            color: var(--slate-700);
        }
        .alert-modern strong { color: var(--info); }

        /* ---- Recent Donations ---- */
        .recent-section {
            padding: 64px 24px;
            background: var(--bg-card);
            border-top: 1px solid var(--slate-100);
            overflow: hidden;
        }
        .section-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--slate-800);
            text-align: center;
            margin-bottom: 32px;
        }
        .section-heading i { color: var(--accent-500); }
        .donor-slider-wrapper {
            overflow: hidden;
            position: relative;
            width: 100%;
            mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 8%, black 92%, transparent 100%);
        }
        .donor-slider-track {
            display: flex;
            gap: 16px;
            width: max-content;
            animation: donorSlide var(--slide-duration, 20s) linear infinite;
        }
        .donor-slider-track:hover {
            animation-play-state: paused;
        }
        @keyframes donorSlide {
            0% { transform: translateX(0); }
            100% { transform: translateX(calc(-50% - 8px)); }
        }
        .donation-item {
            flex-shrink: 0;
            padding: 16px 24px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-500);
            min-width: 220px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .donation-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .donation-item .donor-name { font-weight: 600; color: var(--slate-700); display: block; margin-bottom: 2px; }
        .donation-item .donor-cat  { font-size: 0.82rem; color: var(--slate-500); }
        .donation-item .don-date   { font-size: 0.82rem; color: var(--slate-400); }

        /* ---- Contact Section ---- */
        .contact-section {
            padding: 64px 24px;
            background: var(--slate-100);
            border-top: 1px solid var(--slate-200);
        }
        .contact-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary-700);
            margin-bottom: 16px;
        }
        .contact-info { color: var(--slate-600); line-height: 2; }
        .contact-info i { color: var(--primary-500); margin-right: 6px; }
        .btn-outline-modern {
            border: 1.5px solid var(--slate-300);
            color: var(--slate-600);
            font-weight: 600;
            padding: 10px 24px;
            border-radius: var(--border-radius-full);
            background: transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-outline-modern:hover {
            border-color: var(--primary-500);
            color: var(--primary-700);
            background: var(--primary-50);
        }

        /* ---- Footer ---- */
        .public-footer {
            background: var(--slate-900);
            color: rgba(255,255,255,0.7);
            padding: 28px 24px;
            text-align: center;
            font-size: 0.88rem;
        }
        .public-footer strong { color: rgba(255,255,255,0.9); }

        /* ---- Chatbot Widget ---- */
        .chatbot-fab {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-700) 100%);
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.35);
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .chatbot-fab:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 28px rgba(5, 150, 105, 0.45);
            color: #fff;
        }

        /* ---- Bank Details Card ---- */
        .bank-details-card {
            border: 2px solid var(--primary-200, #bbf7d0);
            border-radius: var(--border-radius-lg, 16px);
            overflow: hidden;
            background: #fff;
        }
        .bank-details-header {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%);
            color: #fff;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bank-details-body {
            padding: 20px;
        }
        .bank-detail-item {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 12px 16px;
        }
        .bank-detail-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--slate-400);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .bank-detail-value {
            display: block;
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--slate-800);
        }

        /* ---- Form Steps Indicator ---- */
        .form-steps-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--slate-400);
            padding: 8px 16px;
            border-radius: 99px;
            transition: all 0.3s;
        }
        .step-item span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--slate-200);
            color: var(--slate-500);
            font-size: 0.75rem;
            font-weight: 700;
        }
        .step-item.active {
            color: var(--primary-700);
        }
        .step-item.active span {
            background: var(--primary-500);
            color: #fff;
        }
        .step-item.completed span {
            background: var(--primary-500);
            color: #fff;
        }
        .step-item.completed span::after {
            content: '✓';
        }
        .step-divider {
            width: 32px;
            height: 2px;
            background: var(--slate-200);
            margin: 0 4px;
        }

        /* ---- Slip Upload Area ---- */
        .slip-upload-area {
            border: 2px dashed var(--slate-300);
            border-radius: var(--border-radius, 12px);
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--slate-50);
        }
        .slip-upload-area:hover {
            border-color: var(--primary-400);
            background: var(--primary-50, #f0fdf4);
        }

        /* ---- Donation Summary ---- */
        .donation-summary {
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--border-radius, 12px);
            padding: 20px;
        }
        .donation-summary-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--slate-700);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .donation-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.9rem;
            color: var(--slate-600);
        }

        /* ---- Impact Section ---- */
        .impact-section {
            padding: 60px 24px;
            background: linear-gradient(180deg, #fff 0%, var(--slate-50) 100%);
        }
        .impact-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.55rem;
            color: var(--slate-800);
            text-align: center;
            margin-bottom: 8px;
        }
        .impact-desc {
            text-align: center;
            color: var(--slate-500);
            font-size: 0.95rem;
            margin-bottom: 40px;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .impact-card {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: var(--border-radius-lg, 16px);
            padding: 32px 24px;
            text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }
        .impact-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
        }
        .impact-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            margin-bottom: 18px;
        }
        .impact-card h5 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--slate-800);
            margin-bottom: 8px;
        }
        .impact-card p {
            color: var(--slate-500);
            font-size: 0.88rem;
            margin-bottom: 0;
            line-height: 1.55;
        }

        /* ---- Donate Section Enhancement ---- */
        .donate-section {
            padding: 64px 24px;
            background: linear-gradient(180deg, var(--slate-50) 0%, #f0fdf4 50%, var(--slate-50) 100%);
        }

        /* ---- Responsive ---- */
        @media (max-width: 768px) {
            .hero-section { padding: 70px 16px 80px; }
            .hero-section h1 { font-size: 2.1rem; }
            .hero-trust { gap: 16px; }
            .hero-trust span { font-size: 0.78rem; }
            .donation-form-card { padding: 28px 20px; }
            .founder-card { margin-top: -36px; padding: 20px; }
            .topbar-links a:not(.btn-login) { display: none; }
            .impact-section { padding: 48px 16px; }
            .impact-card { padding: 24px 18px; }
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<header class="public-topbar">
    <div class="topbar-inner">
        <a href="index.php" class="topbar-brand">
            <i class="bi bi-heart-pulse-fill"></i> Seela Suwa Herath
        </a>
        <nav class="topbar-links">
            <a href="#donate">Donate</a>
            <a href="#recent">Recent</a>
            <a href="#contact">Contact</a>
            <a href="login.php" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Back to Login</a>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero-section">
    <div class="badge-pill"><i class="bi bi-shield-check"></i> Trusted Monastery Healthcare Platform</div>
    <h1>Every Gift Brings<br><span class="hero-highlight">Hope &amp; Healing</span></h1>
    <p class="hero-desc">Your generosity funds medical treatment, essential medicines, and wellness programs for Buddhist monks at Seela Suwa Herath Bikshu Gilan Arana.</p>
    <a href="#donate" class="btn-hero">
        <i class="bi bi-heart-fill"></i> Donate Now
    </a>
    <div class="hero-trust">
        <span><i class="bi bi-shield-lock-fill"></i> Secure Donations</span>
        <span><i class="bi bi-check-circle-fill"></i> Verified &amp; Transparent</span>
        <span><i class="bi bi-clock-fill"></i> 24-48hr Verification</span>
    </div>
</section>

<!-- Founder Highlight -->
<section class="container" style="position: relative;">
    <div class="founder-card">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <img src="images/img1.jpeg" alt="Solewewa Chandrasiri Thero - Founder" class="founder-photo">
            </div>
            <div class="col">
                <h5 style="margin-bottom: 4px; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; color: var(--primary-700);">
                    <i class="bi bi-award-fill"></i> Founder: Ven. Solewewa Chandrasiri Thero
                </h5>
                <p style="margin-bottom: 0; color: var(--slate-500); font-size: 0.92rem;">Seela Suwa Herath Bikshu Gilan Arana was founded to provide compassionate healthcare for monks, supported by transparent public donations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-section-label"><i class="bi bi-bar-chart-fill"></i> Our Collective Impact</div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-number"><?= number_format($stats['unique_donors'] ?? 0) ?></div>
                    <div class="stat-label">Generous Donors</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-cash-coin"></i></div>
                    <div class="stat-number">Rs. <?= number_format($stats['total_amount'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Donations</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-person-hearts"></i></div>
                    <div class="stat-number"><?= number_format($stats['total_donations'] ?? 0) ?></div>
                    <div class="stat-label">Total Contributions</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Impact Section -->
<section class="impact-section">
    <div class="container">
        <h3 class="impact-heading"><i class="bi bi-diagram-3" style="color: var(--primary-500);"></i> Where Your Donation Goes</h3>
        <p class="impact-desc">Every rupee is directed towards improving the health and wellbeing of our monastic community</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="impact-card">
                    <div class="impact-icon" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a;"><i class="bi bi-hospital"></i></div>
                    <h5>Medical Treatment</h5>
                    <p>Consultations, surgeries, and ongoing medical care for monks in need of specialist attention</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="impact-card">
                    <div class="impact-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;"><i class="bi bi-capsule"></i></div>
                    <h5>Medicine &amp; Supplies</h5>
                    <p>Essential medications, medical equipment, and healthcare supplies for daily monastic care</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="impact-card">
                    <div class="impact-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb;"><i class="bi bi-heart-pulse"></i></div>
                    <h5>Wellness Programs</h5>
                    <p>Preventive care, nutrition, and holistic wellbeing programs for the monastic community</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Donation Form Section -->
<section id="donate" class="donate-section">
    <div class="container">
        <div class="donation-form-card">
            <h2 class="form-section-title">
                <i class="bi bi-gift" style="color: var(--primary-500);"></i> Make Your Donation
            </h2>
            <p class="form-section-desc">Complete the steps below — every contribution makes a real difference.</p>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:var(--border-radius);font-size:0.95rem;">
                <i class="bi bi-check-circle-fill fs-4"></i>
                <div>
                    <strong>Thank you for your donation!</strong><br>
                    Your bank slip has been uploaded successfully. Our team will verify your payment within 24-48 hours.
                    <?php if (isset($_GET['ref'])): ?>
                    <br>Reference: <strong>#<?= htmlspecialchars($_GET['ref']) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:var(--border-radius);font-size:0.95rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($_GET['error']) ?></span>
            </div>
            <?php endif; ?>

            <!-- Bank Details Card -->
            <div class="bank-details-card mb-4">
                <div class="bank-details-header">
                    <i class="bi bi-bank2"></i> Bank Transfer Details
                </div>
                <div class="bank-details-body">
                    <p style="margin-bottom:16px;color:var(--slate-600);font-size:0.9rem;">Please transfer your donation to the following bank account and upload the payment slip below.</p>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="bank-detail-item">
                                <span class="bank-detail-label">Bank Name</span>
                                <select id="bankNameField" style="width:100%;border:none;background:transparent;font-weight:700;font-size:0.95rem;color:var(--slate-800);padding:2px 0;cursor:pointer;outline:none;">
                                    <option value="Bank of Ceylon">Bank of Ceylon (BOC)</option>
                                    <option value="People's Bank">People's Bank</option>
                                    <option value="Commercial Bank">Commercial Bank of Ceylon</option>
                                    <option value="Hatton National Bank">Hatton National Bank (HNB)</option>
                                    <option value="Sampath Bank">Sampath Bank</option>
                                    <option value="Seylan Bank">Seylan Bank</option>
                                    <option value="Nations Trust Bank">Nations Trust Bank (NTB)</option>
                                    <option value="DFCC Bank">DFCC Bank</option>
                                    <option value="National Savings Bank">National Savings Bank (NSB)</option>
                                    <option value="Pan Asia Banking Corporation">Pan Asia Banking Corporation</option>
                                    <option value="Union Bank">Union Bank of Colombo</option>
                                    <option value="Cargills Bank">Cargills Bank</option>
                                    <option value="Amana Bank">Amana Bank</option>
                                    <option value="National Development Bank">National Development Bank (NDB)</option>
                                    <option value="Regional Development Bank">Regional Development Bank (RDB)</option>
                                    <option value="Sanasa Development Bank">Sanasa Development Bank</option>
                                    <option value="Housing Development Finance Corporation">HDFC Bank</option>
                                    <option value="Lanka Puthra Development Bank">Lanka Puthra Development Bank</option>
                                    <option value="State Mortgage & Investment Bank">State Mortgage & Investment Bank</option>
                                    <option value="Citibank">Citibank N.A.</option>
                                    <option value="Standard Chartered Bank">Standard Chartered Bank</option>
                                    <option value="HSBC">HSBC Sri Lanka</option>
                                    <option value="Deutsche Bank">Deutsche Bank</option>
                                    <option value="Indian Bank">Indian Bank</option>
                                    <option value="Indian Overseas Bank">Indian Overseas Bank</option>
                                    <option value="State Bank of India">State Bank of India</option>
                                    <option value="MCB Bank">MCB Bank</option>
                                    <option value="Public Bank Berhad">Public Bank Berhad</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="bank-detail-item">
                                <span class="bank-detail-label">Branch</span>
                                <select id="branchField" style="width:100%;border:none;background:transparent;font-weight:700;font-size:0.95rem;color:var(--slate-800);padding:2px 0;cursor:pointer;outline:none;">
                                    <option value="Giribawa">Giribawa</option>
                                    <option value="Colombo">Colombo</option>
                                    <option value="Colombo Fort">Colombo Fort</option>
                                    <option value="Kandy">Kandy</option>
                                    <option value="Galle">Galle</option>
                                    <option value="Jaffna">Jaffna</option>
                                    <option value="Matara">Matara</option>
                                    <option value="Negombo">Negombo</option>
                                    <option value="Kurunegala">Kurunegala</option>
                                    <option value="Anuradhapura">Anuradhapura</option>
                                    <option value="Ratnapura">Ratnapura</option>
                                    <option value="Badulla">Badulla</option>
                                    <option value="Trincomalee">Trincomalee</option>
                                    <option value="Batticaloa">Batticaloa</option>
                                    <option value="Ampara">Ampara</option>
                                    <option value="Polonnaruwa">Polonnaruwa</option>
                                    <option value="Hambantota">Hambantota</option>
                                    <option value="Monaragala">Monaragala</option>
                                    <option value="Kegalle">Kegalle</option>
                                    <option value="Nuwara Eliya">Nuwara Eliya</option>
                                    <option value="Matale">Matale</option>
                                    <option value="Kalutara">Kalutara</option>
                                    <option value="Gampaha">Gampaha</option>
                                    <option value="Puttalam">Puttalam</option>
                                    <option value="Chilaw">Chilaw</option>
                                    <option value="Dambulla">Dambulla</option>
                                    <option value="Embilipitiya">Embilipitiya</option>
                                    <option value="Kiribathgoda">Kiribathgoda</option>
                                    <option value="Kaduwela">Kaduwela</option>
                                    <option value="Maharagama">Maharagama</option>
                                    <option value="Nugegoda">Nugegoda</option>
                                    <option value="Dehiwala">Dehiwala</option>
                                    <option value="Moratuwa">Moratuwa</option>
                                    <option value="Panadura">Panadura</option>
                                    <option value="Horana">Horana</option>
                                    <option value="Avissawella">Avissawella</option>
                                    <option value="Wennappuwa">Wennappuwa</option>
                                    <option value="Kuliyapitiya">Kuliyapitiya</option>
                                    <option value="Wariyapola">Wariyapola</option>
                                    <option value="Nikaweratiya">Nikaweratiya</option>
                                    <option value="Vavuniya">Vavuniya</option>
                                    <option value="Mannar">Mannar</option>
                                    <option value="Kilinochchi">Kilinochchi</option>
                                    <option value="Mullaitivu">Mullaitivu</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 d-flex align-items-center gap-2" style="background:var(--accent-100);padding:10px 14px;border-radius:8px;font-size:0.82rem;color:var(--accent-700,#92400e);">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Online card payment is coming soon. Currently only bank transfers are accepted.</span>
                    </div>
                </div>
            </div>

            <!-- Donation Form -->
            <form action="process_public_donation.php" method="POST" enctype="multipart/form-data" id="donationForm">
                <div class="form-steps-indicator mb-4">
                    <div class="step-item active" id="step1Ind"><span>1</span> Your Info</div>
                    <div class="step-divider"></div>
                    <div class="step-item" id="step2Ind"><span>2</span> Donation</div>
                    <div class="step-divider"></div>
                    <div class="step-item" id="step3Ind"><span>3</span> Bank Slip</div>
                </div>

                <!-- Step 1: Personal Info -->
                <div id="formStep1">
                    <h6 style="font-weight:700;color:var(--slate-700);margin-bottom:16px;"><i class="bi bi-person-circle" style="color:var(--primary-500);"></i> Step 1: Your Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label-modern"><i class="bi bi-person"></i> Your Name <span style="color:#dc2626;">*</span></label>
                            <input type="text" name="donor_name" id="donor_name" class="form-control form-control-modern" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label-modern"><i class="bi bi-envelope"></i> Email <span style="color:#dc2626;">*</span></label>
                            <input type="email" name="donor_email" id="donor_email" class="form-control form-control-modern" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-modern"><i class="bi bi-telephone"></i> Phone Number <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="donor_phone" id="donor_phone" class="form-control form-control-modern" placeholder="07XXXXXXXX" required>
                    </div>
                    <button type="button" class="btn-primary-modern w-100" onclick="goToStep(2)">
                        Next: Donation Details <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2: Donation Details -->
                <div id="formStep2" style="display:none;">
                    <h6 style="font-weight:700;color:var(--slate-700);margin-bottom:16px;"><i class="bi bi-cash-coin" style="color:var(--primary-500);"></i> Step 2: Donation Details</h6>
                    <div class="mb-4">
                        <label class="form-label-modern"><i class="bi bi-tag"></i> Donation Category <span style="color:#dc2626;">*</span></label>
                        <div class="row g-3 mt-1">
                            <?php while ($category = $categories->fetch_assoc()): ?>
                            <div class="col-md-6">
                                <div class="category-card" onclick="selectCategory(<?= $category['category_id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                    <input type="radio" name="category_id" value="<?= $category['category_id'] ?>" id="cat_<?= $category['category_id'] ?>" hidden required>
                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    <p class="mb-0"><?= htmlspecialchars($category['description']) ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-modern"><i class="bi bi-cash"></i> Donation Amount (Rs.) <span style="color:#dc2626;">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control form-control-modern" style="font-size:1.1rem; font-weight:600;" min="100" step="0.01" placeholder="Enter amount" required>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <button type="button" class="btn-amount" onclick="setAmount(500)">Rs. 500</button>
                            <button type="button" class="btn-amount" onclick="setAmount(1000)">Rs. 1,000</button>
                            <button type="button" class="btn-amount" onclick="setAmount(5000)">Rs. 5,000</button>
                            <button type="button" class="btn-amount" onclick="setAmount(10000)">Rs. 10,000</button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-modern"><i class="bi bi-chat-left-text"></i> Message (Optional)</label>
                        <textarea name="notes" id="notes" class="form-control form-control-modern" rows="2" placeholder="Your message or dedication..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-outline-modern flex-fill" onclick="goToStep(1)">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-primary-modern flex-fill" onclick="goToStep(3)">
                            Next: Upload Slip <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Bank Slip Upload -->
                <div id="formStep3" style="display:none;">
                    <h6 style="font-weight:700;color:var(--slate-700);margin-bottom:16px;"><i class="bi bi-cloud-upload" style="color:var(--primary-500);"></i> Step 3: Upload Bank Slip</h6>

                    <div class="mb-3">
                        <label class="form-label-modern"><i class="bi bi-hash"></i> Bank Reference / Transaction Number</label>
                        <input type="text" name="bank_reference" id="bank_reference" class="form-control form-control-modern" placeholder="e.g., TXN-12345 or deposit slip number">
                    </div>

                    <div class="mb-4">
                        <label class="form-label-modern"><i class="bi bi-image"></i> Bank Slip / Receipt Photo <span style="color:#dc2626;">*</span></label>
                        <div class="slip-upload-area" id="slipUploadArea" onclick="document.getElementById('bank_slip').click()">
                            <input type="file" name="bank_slip" id="bank_slip" accept="image/*,.pdf" hidden onchange="previewSlip(this)">
                            <div id="slipPlaceholder">
                                <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem;color:var(--primary-400);"></i>
                                <p style="margin:8px 0 4px;font-weight:600;color:var(--slate-700);">Click to upload bank slip</p>
                                <p style="font-size:0.82rem;color:var(--slate-400);margin:0;">JPG, PNG or PDF (max 5MB)</p>
                            </div>
                            <div id="slipPreview" style="display:none;">
                                <img id="slipPreviewImg" style="max-width:100%;max-height:200px;border-radius:8px;">
                                <p id="slipFileName" style="margin-top:8px;font-size:0.85rem;color:var(--slate-600);font-weight:500;"></p>
                                <button type="button" class="btn btn-sm" style="color:var(--danger,#dc2626);font-size:0.82rem;" onclick="event.stopPropagation();clearSlip()">
                                    <i class="bi bi-x-circle"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="donation-summary mb-4">
                        <div class="donation-summary-title"><i class="bi bi-receipt"></i> Donation Summary</div>
                        <div class="donation-summary-row">
                            <span>Donor</span>
                            <span id="sumName">-</span>
                        </div>
                        <div class="donation-summary-row">
                            <span>Category</span>
                            <span id="sumCategory">-</span>
                        </div>
                        <div class="donation-summary-row" style="font-size:1.05rem;font-weight:700;color:var(--primary-700);border-top:2px solid var(--slate-200);padding-top:12px;margin-top:4px;">
                            <span>Amount</span>
                            <span id="sumAmount">Rs. 0.00</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn-outline-modern flex-fill" onclick="goToStep(2)">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-primary-modern flex-fill" id="submitBtn">
                            <i class="bi bi-check-circle"></i> Submit Donation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Recent Donations Section -->
<section id="recent" class="recent-section">
    <div class="container">
        <h3 class="section-heading">
            <i class="bi bi-heart-fill" style="color: var(--primary-500);"></i> Wall of Generosity
        </h3>
    </div>
    <div class="donor-slider-wrapper">
        <div class="donor-slider-track" id="donorSlider">
            <?php
            $donor_items = [];
            if ($recent_donations->num_rows > 0) {
                while ($donation = $recent_donations->fetch_assoc()) {
                    $donor_items[] = $donation;
                }
            }
            ?>
            <?php if (count($donor_items) > 0): ?>
                <?php
                // Repeat items enough times to fill the screen for smooth circular slide
                $repeat = max(2, ceil(12 / count($donor_items)));
                for ($r = 0; $r < $repeat * 2; $r++):
                    foreach ($donor_items as $donation):
                ?>
                <div class="donation-item">
                    <span class="donor-name"><?= htmlspecialchars($donation['donor_name']) ?></span>
                    <span class="donor-cat"><?= htmlspecialchars($donation['category_name']) ?></span>
                </div>
                <?php endforeach; endfor; ?>
            <?php else: ?>
                <p class="text-center w-100" style="color: var(--slate-500);">No donations yet. Be the first to contribute!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="contact-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto text-center">
                <h3 class="contact-heading">
                    <i class="bi bi-envelope-fill"></i> Get in Touch
                </h3>
                <p class="contact-info">
                    <i class="bi bi-geo-alt-fill"></i> Giribawa, Sri Lanka<br>
                    <i class="bi bi-telephone-fill"></i> +94 XX XXX XXXX<br>
                    <i class="bi bi-envelope-fill"></i> admin@monastery.lk
                </p>
                <div class="mt-4">
                    <a href="login.php" class="btn-outline-modern">
                        <i class="bi bi-box-arrow-in-right"></i> Staff Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="public-footer">
    <p class="mb-1">&copy; <?= date('Y') ?> <strong>Seela Suwa Herath Bikshu Gilan Arana</strong>. All rights reserved.</p>
    <p class="mb-0" style="font-size: 0.82rem;"><i class="bi bi-shield-check" style="color: #fbbf24;"></i> All donations are transparently managed &amp; verified within 24-48 hours</p>
</footer>

<script>
let selectedCategoryId = null;
let selectedCategoryName = '';

function selectCategory(categoryId, categoryName) {
    selectedCategoryId = categoryId;
    selectedCategoryName = categoryName;
    document.querySelectorAll('.category-card').forEach(card => card.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('cat_' + categoryId).checked = true;
}

function setAmount(amount) {
    document.getElementById('amount').value = amount;
}

function goToStep(step) {
    // Validate before moving forward
    if (step === 2) {
        const name = document.getElementById('donor_name').value.trim();
        const email = document.getElementById('donor_email').value.trim();
        const phone = document.getElementById('donor_phone').value.trim();
        if (!name || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }
    }
    if (step === 3) {
        if (!selectedCategoryId) {
            alert('Please select a donation category.');
            return;
        }
        const amount = parseFloat(document.getElementById('amount').value);
        if (!amount || amount < 100) {
            alert('Please enter a valid amount (minimum Rs. 100).');
            return;
        }
        // Update summary
        document.getElementById('sumName').textContent = document.getElementById('donor_name').value;
        document.getElementById('sumCategory').textContent = selectedCategoryName;
        document.getElementById('sumAmount').textContent = 'Rs. ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    document.getElementById('formStep1').style.display = step === 1 ? 'block' : 'none';
    document.getElementById('formStep2').style.display = step === 2 ? 'block' : 'none';
    document.getElementById('formStep3').style.display = step === 3 ? 'block' : 'none';

    // Update step indicators
    document.getElementById('step1Ind').classList.toggle('active', step >= 1);
    document.getElementById('step1Ind').classList.toggle('completed', step > 1);
    document.getElementById('step2Ind').classList.toggle('active', step >= 2);
    document.getElementById('step2Ind').classList.toggle('completed', step > 2);
    document.getElementById('step3Ind').classList.toggle('active', step >= 3);
}

function previewSlip(input) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit.');
        input.value = '';
        return;
    }

    document.getElementById('slipPlaceholder').style.display = 'none';
    document.getElementById('slipPreview').style.display = 'block';
    document.getElementById('slipFileName').textContent = file.name;

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('slipPreviewImg').src = e.target.result;
            document.getElementById('slipPreviewImg').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('slipPreviewImg').style.display = 'none';
        document.getElementById('slipFileName').textContent = '📄 ' + file.name + ' (PDF)';
    }
}

function clearSlip() {
    document.getElementById('bank_slip').value = '';
    document.getElementById('slipPlaceholder').style.display = 'block';
    document.getElementById('slipPreview').style.display = 'none';
}

// Form submit validation
document.getElementById('donationForm').addEventListener('submit', function(e) {
    const slip = document.getElementById('bank_slip').files[0];
    if (!slip) {
        e.preventDefault();
        alert('Please upload your bank slip / receipt.');
        return;
    }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<span class=\"spinner-border spinner-border-sm me-2\"></span> Submitting...';
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chatbot FAB Button -->
<button class="chatbot-fab" id="chatFabBtn" title="Chat with us" onclick="toggleChatWidget()">
    <i class="bi bi-chat-dots" id="chatFabIcon"></i>
</button>

<!-- Chat Widget Panel -->
<div class="chat-widget" id="chatWidget">
    <div class="chat-widget-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <div class="chat-widget-avatar"><i class="bi bi-robot"></i></div>
            <div>
                <div style="font-weight:700;font-size:14px;">AI Assistant</div>
                <div style="font-size:11px;opacity:0.8;"><span style="display:inline-block;width:7px;height:7px;background:#4ade80;border-radius:50;margin-right:4px;"></span> Online &middot; English &amp; සිංහල</div>
            </div>
        </div>
        <button class="chat-widget-close" onclick="toggleChatWidget()"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="chat-widget-messages" id="widgetChatBox">
        <div class="cw-msg cw-bot">
            <div class="cw-avatar">🪷</div>
            <div class="cw-bubble">
                <strong>Welcome! ආයුබෝවන්!</strong><br><br>
                I'm the AI assistant for Seela Suwa Herath. I can help with:<br>
                • Donation information<br>
                • Payment methods<br>
                • Healthcare services<br>
                • Monastery info<br><br>
                Ask in <strong>English</strong> or <strong>සිංහල</strong>!
            </div>
        </div>
    </div>

    <div class="chat-widget-quick">
        <button onclick="widgetQuickQ('How can I make a donation?')">💰 How to donate?</button>
        <button onclick="widgetQuickQ('What payment methods are available?')">💳 Payment methods</button>
        <button onclick="widgetQuickQ('පරිත්‍යාග කරන්නේ කෙසේද?')">🇱🇰 සිංහල</button>
    </div>

    <div class="chat-widget-input">
        <input type="text" id="widgetInput" placeholder="Type your question..." onkeypress="if(event.key==='Enter')widgetSend()">
        <button onclick="widgetSend()"><i class="bi bi-send-fill"></i></button>
    </div>

    <div class="chat-widget-typing" id="widgetTyping">
        <div class="cw-avatar" style="width:24px;height:24px;font-size:11px;">🪷</div>
        <div class="cw-typing-dots"><span></span><span></span><span></span></div>
    </div>
</div>

<style>
/* Chat Widget Panel */
.chat-widget {
    position: fixed;
    bottom: 96px;
    right: 24px;
    width: 380px;
    max-height: 540px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 48px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.08);
    z-index: 1001;
    display: none;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.06);
    animation: chatSlideUp 0.3s cubic-bezier(.4,0,.2,1);
}
.chat-widget.open { display: flex; }

@keyframes chatSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.chat-widget-header {
    background: linear-gradient(135deg, #4A6040 0%, #3D5035 100%);
    color: #fff;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.chat-widget-avatar {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.chat-widget-close {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 14px;
}
.chat-widget-close:hover { background: rgba(255,255,255,0.25); }

.chat-widget-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    max-height: 320px;
    min-height: 200px;
    background: #f8faf7;
}

.cw-msg {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
    align-items: flex-start;
}
.cw-msg.cw-user { flex-direction: row-reverse; }
.cw-avatar {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(135deg, #6E8662, #4A6040);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.cw-msg.cw-user .cw-avatar { background: linear-gradient(135deg, #64748b, #475569); }
.cw-bubble {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.55;
}
.cw-msg.cw-bot .cw-bubble {
    background: #fff;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-top-left-radius: 4px;
}
.cw-msg.cw-user .cw-bubble {
    background: linear-gradient(135deg, #6E8662, #4A6040);
    color: #fff;
    border-top-right-radius: 4px;
}

.chat-widget-quick {
    padding: 8px 16px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    border-top: 1px solid #f1f5f9;
    background: #fff;
}
.chat-widget-quick button {
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    border-radius: 99px;
    padding: 5px 12px;
    font-size: 11.5px;
    color: #3D5035;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
}
.chat-widget-quick button:hover {
    background: #6E8662;
    color: #fff;
    border-color: #6E8662;
}

.chat-widget-input {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    background: #fff;
}
.chat-widget-input input {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 9px 14px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
}
.chat-widget-input input:focus { border-color: #6E8662; box-shadow: 0 0 0 3px rgba(110,134,98,0.1); }
.chat-widget-input button {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #6E8662, #4A6040);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    transition: transform 0.15s;
    flex-shrink: 0;
}
.chat-widget-input button:hover { transform: scale(1.05); }

.chat-widget-typing {
    display: none;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #f8faf7;
}
.chat-widget-typing.active { display: flex; }
.cw-typing-dots { display: flex; gap: 3px; }
.cw-typing-dots span {
    width: 6px; height: 6px; border-radius: 50%;
    background: #94a3b8;
    animation: cwDot 1.4s ease-in-out infinite;
}
.cw-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.cw-typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes cwDot {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

@media (max-width: 480px) {
    .chat-widget {
        right: 0;
        bottom: 0;
        left: 0;
        width: 100%;
        max-height: 100vh;
        border-radius: 20px 20px 0 0;
    }
}
</style>

<script>
// Toggle chat widget
function toggleChatWidget() {
    const widget = document.getElementById('chatWidget');
    const icon = document.getElementById('chatFabIcon');
    const isOpen = widget.classList.contains('open');
    
    if (isOpen) {
        widget.classList.remove('open');
        icon.className = 'bi bi-chat-dots';
    } else {
        widget.classList.add('open');
        icon.className = 'bi bi-x-lg';
        document.getElementById('widgetInput').focus();
    }
}

// Send message
function widgetSend() {
    const input = document.getElementById('widgetInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    widgetAddMsg(msg, 'user');
    input.value = '';
    
    // Show typing
    document.getElementById('widgetTyping').classList.add('active');
    
    fetch('chatbot_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message: msg,
            language: 'auto',
            context: {
                monastery_name: "Seela Suwa Herath Bikshu Gilan Arana",
                payment_methods: ["Bank Transfer", "Cash"],
                website: "http://localhost/test/"
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('widgetTyping').classList.remove('active');
        if (data.success) {
            widgetAddMsg(data.response, 'bot');
        } else {
            widgetAddMsg('Sorry, I encountered an error. Please try again.', 'bot');
        }
        // Hide quick questions after first real exchange
        const quick = document.querySelector('.chat-widget-quick');
        if (quick) quick.style.display = 'none';
    })
    .catch(() => {
        document.getElementById('widgetTyping').classList.remove('active');
        widgetAddMsg('Connection error. Please try again.', 'bot');
    });
}

function widgetQuickQ(q) {
    document.getElementById('widgetInput').value = q;
    widgetSend();
}

function widgetAddMsg(content, type) {
    const box = document.getElementById('widgetChatBox');
    const div = document.createElement('div');
    div.className = 'cw-msg cw-' + type;
    
    const avatar = document.createElement('div');
    avatar.className = 'cw-avatar';
    avatar.innerHTML = type === 'bot' ? '🪷' : '👤';
    
    const bubble = document.createElement('div');
    bubble.className = 'cw-bubble';
    bubble.innerHTML = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
    
    div.appendChild(avatar);
    div.appendChild(bubble);
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}
</script>

<script>
// Seamless circular slider - adjust speed based on content width
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('donorSlider');
    if (slider && slider.children.length > 0) {
        const totalWidth = slider.scrollWidth / 2;
        const speed = 50; // pixels per second
        const duration = totalWidth / speed;
        slider.style.setProperty('--slide-duration', duration + 's');
    }
});
</script>

</body>
</html>
<?php $con->close(); ?>
