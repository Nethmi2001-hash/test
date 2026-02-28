<?php
/**
 * Public Donor Portal - No Login Required
 * Allows anyone to make donations online
 */
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/payhere_config.php';

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
    <script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
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
            background: var(--primary-600);
            color: #fff;
            padding: 8px 20px;
            border-radius: var(--border-radius-full);
            font-weight: 600;
        }
        .topbar-links .btn-login:hover {
            background: var(--primary-700);
            color: #fff;
        }

        /* ---- Hero Section ---- */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-900) 60%, #022c22 100%);
            color: #fff;
            padding: 80px 24px 90px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 80%, rgba(52, 211, 153, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(251, 191, 36, 0.10) 0%, transparent 50%);
            pointer-events: none;
        }
        .hero-section .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: var(--border-radius-full);
            padding: 8px 20px;
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 20px;
            backdrop-filter: blur(6px);
        }
        .hero-section h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.75rem;
            font-weight: 800;
            max-width: 700px;
            margin: 0 auto 16px;
            line-height: 1.15;
        }
        .hero-section .hero-desc {
            font-size: 1.1rem;
            font-weight: 400;
            color: rgba(255,255,255,0.82);
            max-width: 600px;
            margin: 0 auto 32px;
            line-height: 1.6;
        }
        .hero-section .btn-hero {
            background: #fff;
            color: var(--primary-700);
            font-weight: 700;
            padding: 14px 36px;
            border-radius: var(--border-radius-full);
            font-size: 1.05rem;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hero-section .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        /* ---- Founder Highlight ---- */
        .founder-card {
            background: var(--bg-card);
            border-radius: var(--border-radius-xl);
            padding: 24px 28px;
            margin-top: -48px;
            position: relative;
            z-index: 5;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--slate-200);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
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
        .donate-section { padding: 64px 24px; }
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
        .donation-item {
            padding: 16px 20px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            border-radius: var(--border-radius);
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-500);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .donation-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        .donation-item .donor-name { font-weight: 600; color: var(--slate-700); }
        .donation-item .donor-cat  { font-size: 0.82rem; color: var(--slate-500); }
        .donation-item .don-amount { font-weight: 700; color: var(--primary-600); }
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

        /* ---- Responsive ---- */
        @media (max-width: 768px) {
            .hero-section { padding: 60px 16px 70px; }
            .hero-section h1 { font-size: 2rem; }
            .donation-form-card { padding: 28px 20px; }
            .founder-card { margin-top: -36px; padding: 20px; }
            .topbar-links a:not(.btn-login) { display: none; }
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<header class="public-topbar">
    <div class="topbar-inner">
        <a href="#" class="topbar-brand">
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
    <div class="badge-pill"><i class="bi bi-heart-pulse"></i> Monastery Healthcare &amp; Donation Platform</div>
    <h1><i class="bi bi-person-hearts"></i> Helping Hands for Monastic Care</h1>
    <p class="hero-desc">This platform funds medical treatment, medicines, and wellness support for monks at Seela Suwa Herath Bikshu Gilan Arana.</p>
    <a href="#donate" class="btn-hero">
        <i class="bi bi-hand-thumbs-up"></i> Offer Support
    </a>
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

<!-- Donation Form Section -->
<section id="donate" class="donate-section">
    <div class="container">
        <div class="donation-form-card">
            <h2 class="form-section-title">
                <i class="bi bi-person-hearts" style="color: var(--primary-500);"></i> Offer a Helping Hand
            </h2>
            <p class="form-section-desc">Your generosity directly supports monastic healthcare.</p>

            <form id="donationForm">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label-modern"><i class="bi bi-person"></i> Your Name</label>
                        <input type="text" id="donor_name" class="form-control form-control-modern" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label-modern"><i class="bi bi-envelope"></i> Email</label>
                        <input type="email" id="donor_email" class="form-control form-control-modern" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label-modern"><i class="bi bi-telephone"></i> Phone Number</label>
                    <input type="text" id="donor_phone" class="form-control form-control-modern" placeholder="07XXXXXXXX" required>
                </div>

                <div class="mb-4">
                    <label class="form-label-modern"><i class="bi bi-tag"></i> Donation Category</label>
                    <div class="row g-3 mt-1">
                        <?php while ($category = $categories->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="category-card" onclick="selectCategory(<?= $category['category_id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                <input type="radio" name="category" value="<?= $category['category_id'] ?>" id="cat_<?= $category['category_id'] ?>" hidden>
                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                <p class="mb-0"><?= htmlspecialchars($category['description']) ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-modern"><i class="bi bi-cash"></i> Donation Amount (Rs.)</label>
                    <input type="number" id="amount" class="form-control form-control-modern" style="font-size:1.1rem; font-weight:600;" min="100" step="0.01" placeholder="Enter amount" required>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <button type="button" class="btn-amount" onclick="setAmount(500)">Rs. 500</button>
                        <button type="button" class="btn-amount" onclick="setAmount(1000)">Rs. 1,000</button>
                        <button type="button" class="btn-amount" onclick="setAmount(5000)">Rs. 5,000</button>
                        <button type="button" class="btn-amount" onclick="setAmount(10000)">Rs. 10,000</button>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-modern"><i class="bi bi-chat-left-text"></i> Message (Optional)</label>
                    <textarea id="notes" class="form-control form-control-modern" rows="3" placeholder="Your message or dedication..."></textarea>
                </div>

                <div class="alert-modern mb-4">
                    <strong><i class="bi bi-shield-check"></i> Sandbox Test Mode:</strong><br>
                    Use test cards: <strong>4111 1111 1111 1111</strong> (Visa) or <strong>5555 5555 5555 4444</strong> (MasterCard)<br>
                    CVV: Any 3 digits | Expiry: Any future date
                </div>

                <button type="button" class="btn-primary-modern w-100" onclick="payWithPayHere()">
                    <i class="bi bi-credit-card"></i> Proceed to Secure Payment
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Recent Donations Section -->
<section id="recent" class="recent-section">
    <div class="container">
        <h3 class="section-heading">
            <i class="bi bi-stars"></i> Recent Helping-Hand Donations
        </h3>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($recent_donations->num_rows > 0): ?>
                    <?php while ($donation = $recent_donations->fetch_assoc()): ?>
                    <div class="donation-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="donor-name"><?= htmlspecialchars($donation['donor_name']) ?></span>
                                <span class="donor-cat d-block"><?= htmlspecialchars($donation['category_name']) ?></span>
                            </div>
                            <div class="text-end">
                                <span class="don-amount">Rs. <?= number_format($donation['amount'], 2) ?></span>
                                <span class="don-date d-block"><?= date('M d, Y', strtotime($donation['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center" style="color: var(--slate-500);">No donations yet. Be the first to contribute!</p>
                <?php endif; ?>
            </div>
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
    <p class="mb-1">&copy; 2026 <strong>Seela Suwa Herath Bikshu Gilan Arana</strong>. All rights reserved.</p>
    <p class="mb-0" style="font-size: 0.82rem;">Powered by PayHere Secure Payment Gateway</p>
</footer>

<script>
let selectedCategoryId = null;

function selectCategory(categoryId, categoryName) {
    selectedCategoryId = categoryId;
    document.querySelectorAll('.category-card').forEach(card => card.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    document.getElementById('cat_' + categoryId).checked = true;
}

function setAmount(amount) {
    document.getElementById('amount').value = amount;
}

function payWithPayHere() {
    // Validation
    const name = document.getElementById('donor_name').value.trim();
    const email = document.getElementById('donor_email').value.trim();
    const phone = document.getElementById('donor_phone').value.trim();
    const amount = document.getElementById('amount').value;
    const notes = document.getElementById('notes').value.trim();

    if (!name || !email || !phone || !amount) {
        alert('Please fill all required fields');
        return;
    }

    if (!selectedCategoryId) {
        alert('Please select a donation category');
        return;
    }

    if (parseFloat(amount) < 100) {
        alert('Minimum donation amount is Rs. 100');
        return;
    }

    // Generate unique order ID
    const orderId = 'DON-' + Date.now();

    // PayHere Payment Object
    const payment = {
        sandbox: <?= PAYHERE_SANDBOX_MODE ? 'true' : 'false' ?>,
        merchant_id: "<?= PAYHERE_MERCHANT_ID ?>",
        return_url: "<?= PAYHERE_RETURN_URL ?>",
        cancel_url: "<?= PAYHERE_CANCEL_URL ?>",
        notify_url: "<?= PAYHERE_NOTIFY_URL ?>",
        order_id: orderId,
        items: "Donation",
        amount: parseFloat(amount).toFixed(2),
        currency: "LKR",
        first_name: name,
        last_name: "",
        email: email,
        phone: phone,
        address: "Sri Lanka",
        city: "Giribawa",
        country: "Sri Lanka",
        custom_1: selectedCategoryId,
        custom_2: notes
    };

    // Show payment modal
    payhere.startPayment(payment);

    // Payment callbacks
    payhere.onCompleted = function onCompleted(orderId) {
        alert("Payment completed. Order ID: " + orderId);
        window.location.href = "<?= PAYHERE_RETURN_URL ?>?order_id=" + orderId;
    };

    payhere.onDismissed = function onDismissed() {
        alert("Payment dismissed");
    };

    payhere.onError = function onError(error) {
        alert("Payment error: " + error);
    };
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chatbot Widget -->
<a href="chatbot.php" target="_blank" class="chatbot-fab" title="Chat with us">
    <i class="bi bi-chat-dots"></i>
</a>

</body>
</html>
<?php $con->close(); ?>
