<?php
/**
 * Default Dashboard - Monastery Healthcare System
 * Shown when user role doesn't match any specific dashboard
 */

require_once __DIR__ . '/../layout.php';

renderHeader('Dashboard');
renderSidebar($_SESSION['user_role'] ?? 'admin', 'dashboard');
renderTopbar('Dashboard');
?>

<div class="main-content">
    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🏛️</div>
        <h2>Welcome to Monastery Healthcare System</h2>
        <p style="color: #64748b; margin: 1rem 0;">You are logged in as <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong></p>
        <p style="color: #64748b;">Role: <span class="badge badge-blue"><?= ucfirst($_SESSION['user_role'] ?? 'Unknown') ?></span></p>
        <p style="color: #94a3b8; margin-top: 1.5rem;">If you believe this is an error, please contact the system administrator.</p>
    </div>
</div>

<?php renderFooter(); ?>
