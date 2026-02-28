<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monastery Healthcare System v2.0</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #333; background: #f8fafc; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 1rem 0; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: #2563eb; }
        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-links a { color: #64748b; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; transition: all 0.2s; }
        .nav-links a:hover { color: #2563eb; background: #f1f5f9; }
        .hero { padding: 4rem 0; text-align: center; }
        .hero h1 { font-size: 3rem; margin-bottom: 1rem; color: #1e293b; }
        .hero p { font-size: 1.25rem; color: #64748b; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.2s; }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-outline { background: white; color: #2563eb; border: 2px solid #2563eb; }
        .btn-outline:hover { background: #2563eb; color: white; }
        .features { padding: 4rem 0; background: white; }
        .features h2 { text-align: center; font-size: 2rem; margin-bottom: 3rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .feature { padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .feature h3 { color: #2563eb; margin-bottom: 1rem; }
        .stats { padding: 3rem 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center; }
        .stat { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2563eb; }
        .footer { background: #1e293b; color: white; padding: 2rem 0; text-align: center; }
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .nav-links { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <div class="logo">🏥 MHS v2.0</div>
                <div class="nav-links">
                    <a href="./">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard">Dashboard</a>
                        <a href="logout">Logout</a>
                    <?php else: ?>
                        <a href="register">Register</a>
                        <a href="login" class="btn">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <h1>Monastery Healthcare System</h1>
                <p>Modern healthcare and donation management designed specifically for monastery communities. Built with care, security, and simplicity in mind.</p>
                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="login" class="btn">Get Started</a>
                        <a href="register" class="btn btn-outline">Register as Donator</a>
                    <?php else: ?>
                        <a href="dashboard" class="btn">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>System Features</h2>
                <div class="feature-grid">
                    <div class="feature">
                        <h3>🩺 Healthcare Management</h3>
                        <p>Comprehensive monk health profiles, appointment scheduling, medical records, and provider management with role-based access control.</p>
                    </div>
                    <div class="feature">
                        <h3>💝 Donation Management</h3>
                        <p>Multi-channel donation processing, verification workflows, campaign management, and complete donor relationship tracking.</p>
                    </div>
                    <div class="feature">
                        <h3>📊 Analytics & Reports</h3>
                        <p>Real-time dashboards, comprehensive financial reporting, healthcare analytics, and donation transparency features.</p>
                    </div>
                    <div class="feature">
                        <h3>🔒 Security & Privacy</h3>
                        <p>Enterprise-grade security with CSRF protection, encrypted data storage, secure authentication, and audit logging.</p>
                    </div>
                    <div class="feature">
                        <h3>📱 Modern Interface</h3>
                        <p>Clean, responsive design that works perfectly on desktop, tablet, and mobile devices with intuitive navigation.</p>
                    </div>
                    <div class="feature">
                        <h3>🚀 Performance</h3>
                        <p>Optimized for speed with modern architecture, efficient database queries, and fast loading times.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat">
                        <div class="stat-number">100%</div>
                        <div>Secure & Private</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">5</div>
                        <div>User Roles</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">24/7</div>
                        <div>System Availability</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">Modern</div>
                        <div>Technology Stack</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Monastery Healthcare System v2.0 | Built with ❤️ for monastery communities</p>
        </div>
    </footer>
</body>
</html>