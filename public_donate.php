<?php
/**
 * Public Donor Portal - No Login Required
 * Allows anyone to make donations online
 */
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/payhere_config.php';

$con = getDBConnection();

// Fetch recent verified donations (public view)
$recent_donations_query = "SELECT donor_name, amount, created_at, c.name AS category_name 
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
    <script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
    <style>
        :root {
            --monastery-saffron: #f57c00;
            --monastery-orange: #ff9800;
            --monastery-pale: #fff3e0;
            --monastery-dark: #e65100;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--monastery-pale) 0%, #ffe0b2 100%);
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(245, 124, 0, 0.95) 0%, rgba(255, 152, 0, 0.95) 100%),
                        url('https://images.unsplash.com/photo-1604002260721-2e61e5e2ffc8?w=1920') center/cover;
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '🪷';
            position: absolute;
            font-size: 200px;
            opacity: 0.1;
            top: -50px;
            right: -50px;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--monastery-saffron);
        }

        .donation-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .btn-donate {
            background: linear-gradient(135deg, var(--monastery-saffron) 0%, var(--monastery-orange) 100%);
            border: none;
            color: white;
            padding: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-donate:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 124, 0, 0.4);
            color: white;
        }

        .recent-donation-item {
            padding: 15px;
            background: var(--monastery-pale);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--monastery-saffron);
        }

        .category-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-card:hover, .category-card.selected {
            border-color: var(--monastery-saffron);
            background: var(--monastery-pale);
        }

        .category-card.selected {
            background: var(--monastery-pale);
            box-shadow: 0 3px 10px rgba(245, 124, 0, 0.2);
        }

        .navbar-custom {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav-link {
            color: #333 !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--monastery-saffron) !important;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#" style="color: var(--monastery-saffron); font-weight: bold;">
            🪷 Seela Suwa Herath
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#donate">Donate</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#recent">Recent Donations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        <i class="bi bi-box-arrow-in-right"></i> Staff Login
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="mb-4">🙏 Support Our Mission</h1>
        <p class="lead mb-4">Your donations help us provide healthcare and support to monks in need</p>
        <a href="#donate" class="btn btn-light btn-lg px-5">
            <i class="bi bi-heart-fill"></i> Donate Now
        </a>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5" style="background: white;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-people-fill" style="font-size: 3rem; color: var(--monastery-saffron);"></i>
                    <div class="stats-number mt-3"><?= number_format($stats['unique_donors'] ?? 0) ?></div>
                    <div class="text-muted">Generous Donors</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-cash-coin" style="font-size: 3rem; color: var(--monastery-saffron);"></i>
                    <div class="stats-number mt-3">Rs. <?= number_format($stats['total_amount'] ?? 0, 0) ?></div>
                    <div class="text-muted">Total Donations</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="bi bi-heart-fill" style="font-size: 3rem; color: var(--monastery-saffron);"></i>
                    <div class="stats-number mt-3"><?= number_format($stats['total_donations'] ?? 0) ?></div>
                    <div class="text-muted">Total Contributions</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Donation Form Section -->
<section id="donate" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="donation-form">
                    <h2 class="text-center mb-4" style="color: var(--monastery-saffron);">
                        <i class="bi bi-gift"></i> Make Your Donation
                    </h2>

                    <form id="donationForm">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-person"></i> Your Name</label>
                                <input type="text" id="donor_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-envelope"></i> Email</label>
                                <input type="email" id="donor_email" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-telephone"></i> Phone Number</label>
                            <input type="text" id="donor_phone" class="form-control" placeholder="07XXXXXXXX" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-tag"></i> Donation Category</label>
                            <div class="row g-3">
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <div class="category-card" onclick="selectCategory(<?= $category['category_id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                        <input type="radio" name="category" value="<?= $category['category_id'] ?>" id="cat_<?= $category['category_id'] ?>" hidden>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        <p class="mb-0 text-muted small"><?= htmlspecialchars($category['description']) ?></p>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-cash"></i> Donation Amount (Rs.)</label>
                            <input type="number" id="amount" class="form-control form-control-lg" min="100" step="0.01" placeholder="Enter amount" required>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="setAmount(500)">Rs. 500</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="setAmount(1000)">Rs. 1,000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="setAmount(5000)">Rs. 5,000</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setAmount(10000)">Rs. 10,000</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="bi bi-chat-left-text"></i> Message (Optional)</label>
                            <textarea id="notes" class="form-control" rows="3" placeholder="Your message or dedication..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <strong><i class="bi bi-shield-check"></i> Sandbox Test Mode:</strong><br>
                                Use test cards: <strong>4111 1111 1111 1111</strong> (Visa) or <strong>5555 5555 5555 4444</strong> (MasterCard)<br>
                                CVV: Any 3 digits | Expiry: Any future date
                            </small>
                        </div>

                        <button type="button" class="btn btn-donate w-100" onclick="payWithPayHere()">
                            <i class="bi bi-credit-card"></i> Proceed to Secure Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Donations Section -->
<section id="recent" class="py-5" style="background: white;">
    <div class="container">
        <h3 class="text-center mb-4" style="color: var(--monastery-saffron);">
            <i class="bi bi-star-fill"></i> Recent Donations
        </h3>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php if ($recent_donations->num_rows > 0): ?>
                    <?php while ($donation = $recent_donations->fetch_assoc()): ?>
                    <div class="recent-donation-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($donation['donor_name']) ?></strong>
                                <small class="text-muted d-block"><?= htmlspecialchars($donation['category_name']) ?></small>
                            </div>
                            <div class="text-end">
                                <strong style="color: var(--monastery-saffron);">Rs. <?= number_format($donation['amount'], 2) ?></strong>
                                <small class="text-muted d-block"><?= date('M d, Y', strtotime($donation['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No donations yet. Be the first to contribute!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5" style="background: var(--monastery-pale);">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mx-auto text-center">
                <h3 style="color: var(--monastery-saffron);">
                    <i class="bi bi-envelope"></i> Get in Touch
                </h3>
                <p class="mt-3">
                    <i class="bi bi-geo-alt"></i> Giribawa, Sri Lanka<br>
                    <i class="bi bi-telephone"></i> +94 XX XXX XXXX<br>
                    <i class="bi bi-envelope"></i> admin@monastery.lk
                </p>
                <div class="mt-4">
                    <a href="login.php" class="btn btn-outline-secondary">
                        <i class="bi bi-box-arrow-in-right"></i> Staff Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 text-center" style="background: #333; color: white;">
    <p class="mb-0">&copy; 2026 Seela Suwa Herath Bikshu Gilan Arana. All rights reserved.</p>
    <p class="mb-0 small">Powered by PayHere Secure Payment Gateway</p>
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
<div style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
    <a href="chatbot.php" target="_blank" class="btn btn-donate rounded-circle" style="width: 60px; height: 60px; padding: 0; display: flex; align-items: center; justify-content: center;">
        <i class="bi bi-chat-dots" style="font-size: 1.5rem;"></i>
    </a>
</div>

</body>
</html>
<?php $con->close(); ?>
