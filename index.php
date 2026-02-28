<?php
/**
 * Homepage - Seela Suwa Herath Bikshu Gilan Arana
 * Modern landing page for the Monastery Healthcare & Donation Management System
 */
require_once __DIR__ . '/includes/db_config.php';
$conn = getDBConnection();

// Quick public stats
$totalMonks = 0; $totalDoctors = 0; $totalDonations = 0; $totalAppointments = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM monks WHERE status='active'");
if ($r) $totalMonks = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE status='active'");
if ($r) $totalDoctors = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM donations WHERE status IN ('paid','verified')");
if ($r) $totalDonations = $r->fetch_assoc()['t'];
$r = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='completed'");
if ($r) $totalAppointments = $r->fetch_assoc()['c'];

$conn->close();

// Check if user is logged in
session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seela Suwa Herath Bikshu Gilan Arana - Monastery Healthcare System</title>
    <meta name="description" content="Comprehensive healthcare coordination and donation management for monastic communities. Seela Suwa Herath Bikshu Gilan Arana.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-50: #f0fdf4;
            --green-100: #dcfce7;
            --green-200: #bbf7d0;
            --green-400: #4ade80;
            --green-500: #6E8662;
            --green-600: #5C7350;
            --green-700: #4A6040;
            --green-800: #3D5035;
            --green-900: #2D3B27;
            --amber-400: #fbbf24;
            --amber-500: #f59e0b;
            --amber-600: #d97706;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--slate-800);
            background: #fff;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== NAVBAR ========== */
        .hp-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 16px 0;
            transition: all 0.35s cubic-bezier(.4,0,.2,1);
        }
        .hp-nav.scrolled {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 8px 24px rgba(0,0,0,0.04);
            padding: 10px 0;
        }
        .hp-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .hp-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
            transition: color 0.3s;
        }
        .hp-nav.scrolled .hp-brand { color: var(--green-700); }
        .hp-brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--green-500), var(--green-800));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(110,134,98,0.3);
        }
        .hp-brand-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 17px;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .hp-brand-text small {
            display: block;
            font-weight: 500;
            font-size: 11px;
            opacity: 0.7;
            letter-spacing: 0;
        }
        .hp-nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hp-nav-links a {
            font-size: 13.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .hp-nav.scrolled .hp-nav-links a { color: var(--slate-600); }
        .hp-nav-links a:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .hp-nav.scrolled .hp-nav-links a:hover { background: var(--slate-100); color: var(--slate-800); }
        .hp-nav-cta {
            background: rgba(255,255,255,0.18) !important;
            border: 1.5px solid rgba(255,255,255,0.35) !important;
            color: #fff !important;
        }
        .hp-nav.scrolled .hp-nav-cta {
            background: var(--green-600) !important;
            border-color: var(--green-600) !important;
            color: #fff !important;
        }
        .hp-nav-cta:hover { background: rgba(255,255,255,0.28) !important; }
        .hp-nav.scrolled .hp-nav-cta:hover { background: var(--green-700) !important; }

        /* Mobile nav */
        .hp-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
        }
        .hp-nav.scrolled .hp-menu-btn { color: var(--slate-700); }

        /* ========== HERO ========== */
        .hp-hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(165deg, var(--green-900) 0%, var(--green-700) 35%, #5C4033 70%, #3D2B1F 100%);
            overflow: hidden;
        }
        .hp-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse at 20% 20%, rgba(245,158,11,0.12) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(110,134,98,0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 50%, rgba(0,0,0,0.2) 0%, transparent 70%);
        }
        .hp-hero-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hp-hero-glow {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245,158,11,0.08), transparent 70%);
            top: -100px;
            right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }
        .hp-hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 24px 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 60px;
        }
        .hp-hero-left {}
        .hp-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(245,158,11,0.12);
            border: 1px solid rgba(245,158,11,0.25);
            color: var(--amber-400);
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 24px;
            letter-spacing: 0.3px;
        }
        .hp-hero-badge i { font-size: 14px; }
        .hp-hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 900;
            font-size: clamp(36px, 5vw, 56px);
            color: #fff;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 20px;
        }
        .hp-hero-title .highlight {
            background: linear-gradient(135deg, var(--amber-400), #fcd34d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hp-hero-desc {
            font-size: 17px;
            line-height: 1.7;
            color: rgba(255,255,255,0.7);
            margin-bottom: 36px;
            max-width: 520px;
        }
        .hp-hero-actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .hp-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 14.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(.4,0,.2,1);
            border: none;
            cursor: pointer;
        }
        .hp-btn-primary {
            background: linear-gradient(135deg, var(--green-500), var(--green-700));
            color: #fff;
            box-shadow: 0 4px 14px rgba(110,134,98,0.4), 0 0 0 0 rgba(110,134,98,0);
        }
        .hp-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(110,134,98,0.5), 0 0 0 4px rgba(110,134,98,0.1);
            color: #fff;
        }
        .hp-btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: 1.5px solid rgba(255,255,255,0.2);
        }
        .hp-btn-secondary:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.4);
            transform: translateY(-2px);
            color: #fff;
        }
        .hp-btn-accent {
            background: linear-gradient(135deg, var(--amber-500), var(--amber-600));
            color: #fff;
            box-shadow: 0 4px 14px rgba(245,158,11,0.35);
        }
        .hp-btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245,158,11,0.45);
            color: #fff;
        }
        .hp-btn-outline {
            background: transparent;
            color: var(--green-600);
            border: 2px solid var(--green-500);
        }
        .hp-btn-outline:hover {
            background: var(--green-600);
            color: #fff;
            transform: translateY(-2px);
        }

        /* Hero image collage */
        .hp-hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
        }
        .hp-hero-card-stack {
            position: relative;
            width: 420px;
            height: 480px;
        }
        .hp-float-card {
            position: absolute;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 20px;
            padding: 24px;
            color: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            animation: cardFloat 6s ease-in-out infinite;
        }
        .hp-float-card:nth-child(1) {
            top: 0;
            left: 0;
            width: 280px;
            z-index: 3;
        }
        .hp-float-card:nth-child(2) {
            top: 120px;
            right: 0;
            width: 260px;
            z-index: 2;
            animation-delay: -2s;
        }
        .hp-float-card:nth-child(3) {
            bottom: 0;
            left: 30px;
            width: 300px;
            z-index: 1;
            animation-delay: -4s;
        }
        @keyframes cardFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .hp-fc-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .hp-fc-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 6px;
        }
        .hp-fc-desc {
            font-size: 12.5px;
            opacity: 0.75;
            line-height: 1.5;
        }
        .hp-fc-stat {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 32px;
            letter-spacing: -1px;
        }
        .hp-fc-label {
            font-size: 12px;
            opacity: 0.65;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* ========== STATS BAR ========== */
        .hp-stats-bar {
            position: relative;
            z-index: 10;
            margin-top: -50px;
        }
        .hp-stats-inner {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .hp-stats-card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 40px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.03), 0 20px 40px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .hp-stat-item {
            text-align: center;
        }
        .hp-stat-number {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 900;
            font-size: 36px;
            color: var(--green-700);
            letter-spacing: -1px;
            line-height: 1;
            margin-bottom: 6px;
        }
        .hp-stat-label {
            font-size: 13px;
            color: var(--slate-500);
            font-weight: 600;
        }

        /* ========== SECTION COMMON ========== */
        .hp-section {
            padding: 100px 24px;
        }
        .hp-section-inner {
            max-width: 1200px;
            margin: 0 auto;
        }
        .hp-section-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--green-50);
            color: var(--green-600);
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        .hp-section-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: clamp(28px, 3.5vw, 42px);
            color: var(--slate-900);
            letter-spacing: -1px;
            line-height: 1.15;
            margin-bottom: 16px;
        }
        .hp-section-desc {
            font-size: 16px;
            line-height: 1.7;
            color: var(--slate-500);
            max-width: 600px;
        }

        /* ========== FEATURES ========== */
        .hp-features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 56px;
        }
        .hp-feature-card {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 20px;
            padding: 32px 28px;
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
        }
        .hp-feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--green-500), var(--amber-500));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .hp-feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
            border-color: transparent;
        }
        .hp-feature-card:hover::before { opacity: 1; }
        .hp-feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .hp-feature-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 17px;
            color: var(--slate-800);
            margin-bottom: 10px;
        }
        .hp-feature-desc {
            font-size: 14px;
            line-height: 1.65;
            color: var(--slate-500);
        }

        /* ========== ABOUT / MISSION ========== */
        .hp-about {
            background: var(--slate-50);
        }
        .hp-about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
            margin-top: 48px;
        }
        .hp-about-images {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .hp-about-img {
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 4/3;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .hp-about-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .hp-about-img:hover img { transform: scale(1.05); }
        .hp-about-img:first-child {
            grid-row: span 2;
            aspect-ratio: auto;
        }
        .hp-value-item {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .hp-value-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .hp-value-title {
            font-weight: 700;
            font-size: 15px;
            color: var(--slate-800);
            margin-bottom: 4px;
        }
        .hp-value-desc {
            font-size: 13.5px;
            color: var(--slate-500);
            line-height: 1.6;
        }

        /* ========== DONATE CTA ========== */
        .hp-donate-section {
            background: linear-gradient(165deg, var(--green-800) 0%, var(--green-700) 40%, #5C4033 100%);
            position: relative;
            overflow: hidden;
        }
        .hp-donate-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(245,158,11,0.1), transparent 60%);
        }
        .hp-donate-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
        }
        .hp-donate-inner .hp-section-badge {
            background: rgba(245,158,11,0.15);
            color: var(--amber-400);
            border: 1px solid rgba(245,158,11,0.25);
        }
        .hp-donate-inner .hp-section-title {
            color: #fff;
        }
        .hp-donate-inner .hp-section-desc {
            color: rgba(255,255,255,0.7);
            margin: 0 auto 36px;
        }

        /* ========== ROLES ========== */
        .hp-roles-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 48px;
        }
        .hp-role-card {
            text-align: center;
            padding: 36px 24px;
            border-radius: 20px;
            border: 2px solid var(--slate-200);
            background: #fff;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .hp-role-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            border-color: var(--green-400);
            color: inherit;
        }
        .hp-role-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 18px;
        }
        .hp-role-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 8px;
        }
        .hp-role-desc {
            font-size: 13px;
            color: var(--slate-500);
            line-height: 1.6;
        }

        /* ========== FOUNDER ========== */
        .hp-founder {
            background: var(--slate-50);
        }
        .hp-founder-card {
            display: flex;
            align-items: center;
            gap: 48px;
            background: #fff;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02), 0 16px 40px rgba(0,0,0,0.04);
            border: 1px solid var(--slate-200);
            margin-top: 48px;
        }
        .hp-founder-img {
            width: 200px;
            height: 200px;
            border-radius: 24px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .hp-founder-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 24px;
            color: var(--slate-900);
            margin-bottom: 4px;
        }
        .hp-founder-role {
            font-size: 14px;
            color: var(--green-600);
            font-weight: 600;
            margin-bottom: 16px;
        }
        .hp-founder-quote {
            font-size: 16px;
            line-height: 1.7;
            color: var(--slate-600);
            font-style: italic;
            position: relative;
            padding-left: 20px;
            border-left: 3px solid var(--amber-400);
        }

        /* ========== FOOTER ========== */
        .hp-footer {
            background: var(--slate-900);
            color: rgba(255,255,255,0.7);
            padding: 64px 24px 32px;
        }
        .hp-footer-inner {
            max-width: 1200px;
            margin: 0 auto;
        }
        .hp-footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }
        .hp-footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .hp-footer-brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--green-500), var(--green-700));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }
        .hp-footer-brand-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: #fff;
        }
        .hp-footer-desc {
            font-size: 13.5px;
            line-height: 1.7;
            max-width: 340px;
        }
        .hp-footer-title {
            font-weight: 700;
            font-size: 13px;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .hp-footer-links {
            list-style: none;
            padding: 0;
        }
        .hp-footer-links li { margin-bottom: 10px; }
        .hp-footer-links a {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13.5px;
            transition: color 0.2s;
        }
        .hp-footer-links a:hover { color: var(--amber-400); }
        .hp-footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.08);
            padding-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12.5px;
        }

        /* ========== SCROLL ANIMATIONS ========== */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.7s cubic-bezier(.4,0,.2,1), transform 0.7s cubic-bezier(.4,0,.2,1);
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-delay-1 { transition-delay: 0.1s; }
        .reveal-delay-2 { transition-delay: 0.2s; }
        .reveal-delay-3 { transition-delay: 0.3s; }
        .reveal-delay-4 { transition-delay: 0.35s; }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .hp-hero-content { grid-template-columns: 1fr; text-align: center; }
            .hp-hero-desc { margin-left: auto; margin-right: auto; }
            .hp-hero-actions { justify-content: center; }
            .hp-hero-visual { display: none; }
            .hp-features-grid { grid-template-columns: repeat(2, 1fr); }
            .hp-about-grid { grid-template-columns: 1fr; }
            .hp-about-images { order: -1; }
            .hp-roles-grid { grid-template-columns: repeat(2, 1fr); }
            .hp-footer-grid { grid-template-columns: 1fr 1fr; }
            .hp-founder-card { flex-direction: column; text-align: center; }
            .hp-founder-quote { border-left: none; padding-left: 0; border-top: 3px solid var(--amber-400); padding-top: 16px; }
        }
        @media (max-width: 768px) {
            .hp-nav-links { display: none; }
            .hp-menu-btn { display: block; }
            .hp-features-grid { grid-template-columns: 1fr; }
            .hp-stats-card { grid-template-columns: repeat(2, 1fr); gap: 24px; padding: 28px 24px; }
            .hp-roles-grid { grid-template-columns: 1fr; max-width: 360px; margin-left: auto; margin-right: auto; }
            .hp-section { padding: 64px 20px; }
            .hp-hero-badge { margin-left: auto; margin-right: auto; }
            .hp-footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .hp-footer-bottom { flex-direction: column; gap: 12px; text-align: center; }
        }

        /* Mobile Menu */
        .hp-mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        .hp-mobile-menu.active { display: block; }
        .hp-mobile-panel {
            position: absolute;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background: #fff;
            padding: 24px;
            box-shadow: -8px 0 24px rgba(0,0,0,0.15);
        }
        .hp-mobile-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--slate-600);
            cursor: pointer;
            float: right;
        }
        .hp-mobile-nav {
            list-style: none;
            padding: 48px 0 0;
            margin: 0;
        }
        .hp-mobile-nav li { margin-bottom: 8px; }
        .hp-mobile-nav a {
            display: block;
            padding: 12px 16px;
            color: var(--slate-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border-radius: 10px;
            transition: background 0.2s;
        }
        .hp-mobile-nav a:hover { background: var(--slate-100); }
        .hp-mobile-nav .mob-cta {
            background: var(--green-600);
            color: #fff;
            text-align: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<!-- ======== NAVBAR ======== -->
<nav class="hp-nav" id="hpNav">
    <div class="hp-nav-inner">
        <a href="index.php" class="hp-brand">
            <div class="hp-brand-icon"><i class="bi bi-heart-pulse"></i></div>
            <div class="hp-brand-text">
                Seela Suwa Herath
                <small>Bikshu Gilan Arana</small>
            </div>
        </a>

        <div class="hp-nav-links">
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="public_donate.php">Donate</a>
            <a href="public_transparency.php">Transparency</a>
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="hp-nav-cta">Dashboard</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="hp-nav-cta">Register</a>
            <?php endif; ?>
        </div>

        <button class="hp-menu-btn" onclick="document.getElementById('mobileMenu').classList.add('active')">
            <i class="bi bi-list"></i>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="hp-mobile-menu" id="mobileMenu" onclick="if(event.target===this)this.classList.remove('active')">
    <div class="hp-mobile-panel">
        <button class="hp-mobile-close" onclick="document.getElementById('mobileMenu').classList.remove('active')">
            <i class="bi bi-x-lg"></i>
        </button>
        <ul class="hp-mobile-nav">
            <li><a href="#features" onclick="document.getElementById('mobileMenu').classList.remove('active')">Features</a></li>
            <li><a href="#about" onclick="document.getElementById('mobileMenu').classList.remove('active')">About</a></li>
            <li><a href="public_donate.php">Donate</a></li>
            <li><a href="public_transparency.php">Transparency</a></li>
            <?php if ($isLoggedIn): ?>
                <li><a href="dashboard.php" class="mob-cta">Dashboard</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="mob-cta">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- ======== HERO ======== -->
<section class="hp-hero">
    <div class="hp-hero-grid"></div>
    <div class="hp-hero-glow"></div>
    
    <div class="hp-hero-content">
        <div class="hp-hero-left">
            <div class="hp-hero-badge">
                <i class="bi bi-heart-pulse-fill"></i>
                Sacred Healthcare for Monastic Communities
            </div>
            <h1 class="hp-hero-title">
                Caring for Those<br>Who <span class="highlight">Guide Us</span>
            </h1>
            <p class="hp-hero-desc">
                A comprehensive digital platform for healthcare coordination, appointment management, 
                and transparent donation tracking — serving the monastic community of Sri Lanka.
            </p>
            <div class="hp-hero-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="hp-btn hp-btn-primary">
                        <i class="bi bi-grid-1x2"></i> Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="hp-btn hp-btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Get Started
                    </a>
                <?php endif; ?>
                <a href="public_donate.php" class="hp-btn hp-btn-secondary">
                    <i class="bi bi-suit-heart"></i> Make a Donation
                </a>
            </div>
        </div>

        <div class="hp-hero-visual">
            <div class="hp-hero-card-stack">
                <!-- Floating Card 1 -->
                <div class="hp-float-card">
                    <div class="hp-fc-icon" style="background:rgba(245,158,11,0.2);color:var(--amber-400);">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div class="hp-fc-title">Smart Scheduling</div>
                    <div class="hp-fc-desc">AI-powered appointment coordination with doctor availability tracking</div>
                </div>
                <!-- Floating Card 2 -->
                <div class="hp-float-card">
                    <div class="hp-fc-icon" style="background:rgba(74,222,128,0.2);color:var(--green-400);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="hp-fc-title">Full Transparency</div>
                    <div class="hp-fc-desc">Every donation tracked and verified publicly</div>
                </div>
                <!-- Floating Card 3 -->
                <div class="hp-float-card">
                    <div style="display:flex;align-items:baseline;gap:8px;margin-bottom:8px;">
                        <div class="hp-fc-stat"><?= number_format($totalMonks) ?></div>
                        <div class="hp-fc-label">Monks Cared For</div>
                    </div>
                    <div style="display:flex;align-items:baseline;gap:8px;">
                        <div class="hp-fc-stat" style="font-size:24px;"><?= number_format($totalDoctors) ?></div>
                        <div class="hp-fc-label">Active Doctors</div>
                    </div>
                    <div style="margin-top:12px;height:4px;background:rgba(255,255,255,0.1);border-radius:99px;">
                        <div style="height:100%;width:75%;background:linear-gradient(90deg,var(--amber-400),var(--green-400));border-radius:99px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======== STATS BAR ======== -->
<div class="hp-stats-bar reveal">
    <div class="hp-stats-inner">
        <div class="hp-stats-card">
            <div class="hp-stat-item">
                <div class="hp-stat-number" data-count="<?= $totalMonks ?>">0</div>
                <div class="hp-stat-label">Monks Under Care</div>
            </div>
            <div class="hp-stat-item">
                <div class="hp-stat-number" data-count="<?= $totalDoctors ?>">0</div>
                <div class="hp-stat-label">Active Doctors</div>
            </div>
            <div class="hp-stat-item">
                <div class="hp-stat-number" data-count="<?= $totalAppointments ?>">0</div>
                <div class="hp-stat-label">Appointments Completed</div>
            </div>
            <div class="hp-stat-item">
                <div class="hp-stat-number" data-prefix="Rs." data-count="<?= round($totalDonations) ?>">0</div>
                <div class="hp-stat-label">Donations Received</div>
            </div>
        </div>
    </div>
</div>

<!-- ======== FEATURES ======== -->
<section class="hp-section" id="features">
    <div class="hp-section-inner">
        <div class="reveal">
            <div class="hp-section-badge"><i class="bi bi-lightning-fill"></i> Features</div>
            <h2 class="hp-section-title">Everything You Need<br>In One Platform</h2>
            <p class="hp-section-desc">Built specifically for monastic healthcare management, combining modern technology with compassionate care.</p>
        </div>

        <div class="hp-features-grid">
            <div class="hp-feature-card reveal reveal-delay-1">
                <div class="hp-feature-icon" style="background:#ecfdf5;color:#059669;">
                    <i class="bi bi-calendar2-check"></i>
                </div>
                <div class="hp-feature-title">Appointment Management</div>
                <div class="hp-feature-desc">Schedule, track, and manage doctor appointments with smart conflict detection and automated reminders.</div>
            </div>

            <div class="hp-feature-card reveal reveal-delay-2">
                <div class="hp-feature-icon" style="background:#fef3c7;color:#d97706;">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="hp-feature-title">Donation Tracking</div>
                <div class="hp-feature-desc">Accept cash, bank transfers, and online card payments with full receipt generation and donor management.</div>
            </div>

            <div class="hp-feature-card reveal reveal-delay-3">
                <div class="hp-feature-icon" style="background:#e0f2fe;color:#0284c7;">
                    <i class="bi bi-file-medical"></i>
                </div>
                <div class="hp-feature-title">Medical Records</div>
                <div class="hp-feature-desc">Complete health profiles including blood groups, allergies, chronic conditions, and visit history for each monk.</div>
            </div>

            <div class="hp-feature-card reveal reveal-delay-1">
                <div class="hp-feature-icon" style="background:#f5f3ff;color:#7c3aed;">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="hp-feature-title">AI Assistant</div>
                <div class="hp-feature-desc">Bilingual AI chatbot (English & Sinhala) for health queries, system help, and intelligent data insights.</div>
            </div>

            <div class="hp-feature-card reveal reveal-delay-2">
                <div class="hp-feature-icon" style="background:#fef2f2;color:#dc2626;">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <div class="hp-feature-title">Reports & Analytics</div>
                <div class="hp-feature-desc">Visual dashboards, exportable reports, and real-time analytics for informed decision-making.</div>
            </div>

            <div class="hp-feature-card reveal reveal-delay-3">
                <div class="hp-feature-icon" style="background:#ecfdf5;color:#059669;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="hp-feature-title">Public Transparency</div>
                <div class="hp-feature-desc">Public donation transparency portal allowing anyone to verify how funds are being used.</div>
            </div>
        </div>
    </div>
</section>

<!-- ======== ABOUT ======== -->
<section class="hp-section hp-about" id="about">
    <div class="hp-section-inner">
        <div class="hp-about-grid">
            <div class="hp-about-images">
                <div class="hp-about-img reveal">
                    <img src="images/img1.jpeg" alt="Monastery">
                </div>
                <div class="hp-about-img reveal reveal-delay-1">
                    <img src="images/img2.jpeg" alt="Healthcare">
                </div>
                <div class="hp-about-img reveal reveal-delay-2">
                    <img src="images/img3.jpeg" alt="Community">
                </div>
            </div>

            <div class="reveal">
                <div class="hp-section-badge"><i class="bi bi-info-circle"></i> About Us</div>
                <h2 class="hp-section-title">Compassionate Care for the Sangha</h2>
                <p class="hp-section-desc" style="margin-bottom:36px;">
                    Seela Suwa Herath Bikshu Gilan Arana is dedicated to providing comprehensive healthcare 
                    coordination for Buddhist monks across monasteries, combining local healthcare 
                    traditions with modern medical services.
                </p>

                <div class="hp-value-item">
                    <div class="hp-value-icon" style="background:#ecfdf5;color:#059669;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div>
                        <div class="hp-value-title">Holistic Healthcare</div>
                        <div class="hp-value-desc">Both Ayurvedic and Western medicine approaches, tailored to monastic needs.</div>
                    </div>
                </div>

                <div class="hp-value-item">
                    <div class="hp-value-icon" style="background:#fef3c7;color:#d97706;">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="hp-value-title">Community Driven</div>
                        <div class="hp-value-desc">Supported by generous donations from the community, with full financial transparency.</div>
                    </div>
                </div>

                <div class="hp-value-item">
                    <div class="hp-value-icon" style="background:#e0f2fe;color:#0284c7;">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div>
                        <div class="hp-value-title">Technology Enabled</div>
                        <div class="hp-value-desc">Modern digital platform for efficient management, reporting, and communication.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======== ROLES ======== -->
<section class="hp-section">
    <div class="hp-section-inner" style="text-align:center;">
        <div class="reveal">
            <div class="hp-section-badge" style="margin-left:auto;margin-right:auto;"><i class="bi bi-people-fill"></i> User Roles</div>
            <h2 class="hp-section-title">Designed for<br>Every Stakeholder</h2>
            <p class="hp-section-desc" style="margin:0 auto;">Each role gets a personalized dashboard and features tailored to their needs.</p>
        </div>

        <div class="hp-roles-grid">
            <a href="register.php" class="hp-role-card reveal reveal-delay-1">
                <div class="hp-role-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-shield-lock"></i></div>
                <div class="hp-role-title">Admin</div>
                <div class="hp-role-desc">Full system control — manage monks, doctors, appointments, donations, users, and reports.</div>
            </a>

            <a href="register.php" class="hp-role-card reveal reveal-delay-2">
                <div class="hp-role-icon" style="background:#e0f2fe;color:#0284c7;"><i class="bi bi-bandaid"></i></div>
                <div class="hp-role-title">Doctor</div>
                <div class="hp-role-desc">View your schedule, manage appointments, access patient records and set availability.</div>
            </a>

            <a href="register.php" class="hp-role-card reveal reveal-delay-3">
                <div class="hp-role-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-suit-heart"></i></div>
                <div class="hp-role-title">Donor</div>
                <div class="hp-role-desc">Track your donations, download receipts, view transparency reports, and contribute online.</div>
            </a>

            <a href="register.php" class="hp-role-card reveal reveal-delay-4">
                <div class="hp-role-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="bi bi-person-hearts"></i></div>
                <div class="hp-role-title">Monk</div>
                <div class="hp-role-desc">View appointments, health summary, medical records, and connect with assigned doctors.</div>
            </a>
        </div>
    </div>
</section>

<!-- ======== DONATE CTA ======== -->
<section class="hp-section hp-donate-section">
    <div class="hp-section-inner">
        <div class="hp-donate-inner reveal">
            <div class="hp-section-badge"><i class="bi bi-suit-heart-fill"></i> Support Our Mission</div>
            <h2 class="hp-section-title">Every Contribution<br>Makes a Difference</h2>
            <p class="hp-section-desc">
                Your donations directly fund medical treatments, from consultations to essential medications, 
                ensuring every monk receives the healthcare they deserve.
            </p>
            <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
                <a href="public_donate.php" class="hp-btn hp-btn-accent">
                    <i class="bi bi-heart-fill"></i> Donate Now
                </a>
                <a href="public_transparency.php" class="hp-btn hp-btn-secondary">
                    <i class="bi bi-shield-check"></i> View Transparency Report
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ======== FOUNDER ======== -->
<section class="hp-section hp-founder" id="founder">
    <div class="hp-section-inner">
        <div class="reveal">
            <div class="hp-section-badge"><i class="bi bi-person-check"></i> Our Founder</div>
            <h2 class="hp-section-title">Spiritual Guidance</h2>
        </div>

        <div class="hp-founder-card reveal">
            <img src="images/img1.jpeg" alt="Ven. Solewewa Chandrasiri Thero" class="hp-founder-img">
            <div>
                <div class="hp-founder-name">Ven. Solewewa Chandrasiri Thero</div>
                <div class="hp-founder-role">Founder & Spiritual Guide</div>
                <p class="hp-founder-quote">
                    "The health of the Sangha is the health of the Dhamma. When we care for those who have 
                    dedicated their lives to spiritual service, we nurture the very foundation of our community. 
                    This system ensures that every monk receives timely, compassionate healthcare while maintaining 
                    the transparency that our generous donors deserve."
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ======== FOOTER ======== -->
<footer class="hp-footer">
    <div class="hp-footer-inner">
        <div class="hp-footer-grid">
            <div>
                <div class="hp-footer-brand">
                    <div class="hp-footer-brand-icon"><i class="bi bi-heart-pulse"></i></div>
                    <div class="hp-footer-brand-text">Seela Suwa Herath</div>
                </div>
                <p class="hp-footer-desc">
                    Comprehensive healthcare coordination and donation management system for monastic communities across Sri Lanka.
                </p>
            </div>

            <div>
                <div class="hp-footer-title">Quick Links</div>
                <ul class="hp-footer-links">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="public_donate.php">Donate</a></li>
                    <li><a href="public_transparency.php">Transparency</a></li>
                </ul>
            </div>

            <div>
                <div class="hp-footer-title">Features</div>
                <ul class="hp-footer-links">
                    <li><a href="#features">Appointments</a></li>
                    <li><a href="#features">Medical Records</a></li>
                    <li><a href="#features">Donation Tracking</a></li>
                    <li><a href="#features">AI Assistant</a></li>
                </ul>
            </div>

            <div>
                <div class="hp-footer-title">Contact</div>
                <ul class="hp-footer-links">
                    <li><a href="mailto:info@seelasuwherath.lk"><i class="bi bi-envelope me-2"></i>info@seelasuwherath.lk</a></li>
                    <li><a href="tel:+94112345678"><i class="bi bi-telephone me-2"></i>+94 11 234 5678</a></li>
                    <li><a href="#"><i class="bi bi-geo-alt me-2"></i>Colombo, Sri Lanka</a></li>
                </ul>
            </div>
        </div>

        <div class="hp-footer-bottom">
            <span>&copy; <?= date('Y') ?> Seela Suwa Herath Bikshu Gilan Arana. All rights reserved.</span>
            <span>
                Built with <i class="bi bi-heart-fill" style="color:#dc2626;font-size:11px;"></i> for the Sangha
            </span>
        </div>
    </div>
</footer>

<!-- ======== SCRIPTS ======== -->
<script>
// Navbar scroll effect
const nav = document.getElementById('hpNav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
});

// Scroll reveal animations
const reveals = document.querySelectorAll('.reveal');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
reveals.forEach(el => observer.observe(el));

// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('.hp-stat-number[data-count]');
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.count);
        const prefix = counter.dataset.prefix || '';
        const duration = 2000;
        const start = performance.now();
        
        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(eased * target);
            
            if (target > 10000) {
                counter.textContent = prefix + current.toLocaleString();
            } else {
                counter.textContent = prefix + current.toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                counter.textContent = prefix + target.toLocaleString();
            }
        }
        requestAnimationFrame(update);
    });
}

// Trigger counter when stats bar is visible
const statsBar = document.querySelector('.hp-stats-bar');
const statsObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
        animateCounters();
        statsObserver.unobserve(statsBar);
    }
}, { threshold: 0.3 });
statsObserver.observe(statsBar);

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

</body>
</html>
