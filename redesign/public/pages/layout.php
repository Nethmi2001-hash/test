<?php
/**
 * Shared Layout Components for Monastery Healthcare System
 * Include this at the top of dashboard pages
 */

function renderHeader($title = 'Dashboard') {
    $user_name = $_SESSION['user_name'] ?? 'User';
    $user_role = $_SESSION['user_role'] ?? 'user';
    $roleLabels = [
        'admin' => '👑 Administrator',
        'monk' => '🧘 Monk',
        'doctor' => '👨‍⚕️ Doctor',
        'donator' => '💝 Donator'
    ];
    $roleLabel = $roleLabels[$user_role] ?? $user_role;
    $initial = strtoupper(substr($user_name, 0, 1));
    
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title} - Monastery Healthcare System</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f1f5f9; color: #334155; }
            
            /* Sidebar */
            .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; color: white; z-index: 100; transition: transform 0.3s; overflow-y: auto; }
            .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
            .sidebar-header h2 { font-size: 1.1rem; color: #60a5fa; }
            .sidebar-header p { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
            .sidebar-nav { padding: 1rem 0; }
            .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #cbd5e1; text-decoration: none; transition: all 0.2s; font-size: 0.9rem; }
            .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; }
            .sidebar-nav a.active { border-left: 3px solid #60a5fa; }
            .sidebar-section { padding: 0.5rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.1em; margin-top: 0.5rem; }
            
            /* Top Bar */
            .topbar { position: fixed; top: 0; left: 260px; right: 0; height: 64px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; z-index: 90; }
            .topbar-title { font-size: 1.25rem; font-weight: 600; color: #1e293b; }
            .topbar-user { display: flex; align-items: center; gap: 0.75rem; }
            .user-avatar { width: 36px; height: 36px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem; }
            .user-name { font-weight: 500; font-size: 0.9rem; }
            .user-role { font-size: 0.75rem; color: #64748b; }
            .logout-btn { padding: 0.4rem 0.8rem; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-size: 0.8rem; transition: background 0.2s; }
            .logout-btn:hover { background: #dc2626; }
            
            /* Main Content */
            .main-content { margin-left: 260px; margin-top: 64px; padding: 2rem; min-height: calc(100vh - 64px); }
            
            /* Cards */
            .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 1.5rem; }
            .card h3 { font-size: 1.1rem; color: #1e293b; margin-bottom: 1rem; }
            
            /* Stat Cards */
            .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
            .stat-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
            .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
            .stat-icon.blue { background: #dbeafe; color: #2563eb; }
            .stat-icon.green { background: #d1fae5; color: #059669; }
            .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
            .stat-icon.orange { background: #ffedd5; color: #ea580c; }
            .stat-icon.red { background: #fee2e2; color: #dc2626; }
            .stat-icon.teal { background: #ccfbf1; color: #0d9488; }
            .stat-info h4 { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
            .stat-info .number { font-size: 1.75rem; font-weight: 700; color: #1e293b; }
            .stat-info .change { font-size: 0.75rem; color: #059669; margin-top: 0.25rem; }
            
            /* Tables */
            .table-container { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
            th { background: #f8fafc; padding: 0.75rem 1rem; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
            td { padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; }
            tr:hover { background: #f8fafc; }
            
            /* Badges */
            .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500; }
            .badge-green { background: #d1fae5; color: #065f46; }
            .badge-blue { background: #dbeafe; color: #1e40af; }
            .badge-yellow { background: #fef3c7; color: #92400e; }
            .badge-red { background: #fee2e2; color: #991b1b; }
            .badge-purple { background: #ede9fe; color: #5b21b6; }
            
            /* Buttons */
            .btn { display: inline-block; padding: 0.5rem 1rem; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 500; cursor: pointer; text-decoration: none; transition: all 0.2s; }
            .btn-primary { background: #2563eb; color: white; }
            .btn-primary:hover { background: #1d4ed8; }
            .btn-success { background: #059669; color: white; }
            .btn-success:hover { background: #047857; }
            .btn-warning { background: #d97706; color: white; }
            .btn-warning:hover { background: #b45309; }
            .btn-danger { background: #dc2626; color: white; }
            .btn-danger:hover { background: #b91c1c; }
            .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
            
            /* Forms */
            .form-group { margin-bottom: 1rem; }
            .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.875rem; color: #374151; }
            .form-control { width: 100%; padding: 0.6rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem; transition: border-color 0.2s; }
            .form-control:focus { outline: none; border-color: #2563eb; }
            select.form-control { appearance: none; background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='m8 11-5-5h10z'/%3E%3C/svg%3E") no-repeat right 0.7rem center; }
            textarea.form-control { min-height: 100px; resize: vertical; }
            
            /* Grid */
            .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
            .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            
            /* Alerts */
            .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid; font-size: 0.9rem; }
            .alert-success { background: #f0fdf4; border-color: #22c55e; color: #15803d; }
            .alert-error { background: #fef2f2; border-color: #ef4444; color: #b91c1c; }
            .alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
            .alert-info { background: #f0f9ff; border-color: #3b82f6; color: #1e40af; }
            
            /* Progress */
            .progress-bar { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
            .progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s; }
            .progress-fill.green { background: #22c55e; }
            .progress-fill.blue { background: #3b82f6; }
            .progress-fill.orange { background: #f97316; }
            .progress-fill.red { background: #ef4444; }
            
            /* Modal */
            .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 200; justify-content: center; align-items: center; }
            .modal-overlay.active { display: flex; }
            .modal { background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 600px; width: 95%; max-height: 90vh; overflow-y: auto; }
            .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
            .modal-header h3 { font-size: 1.1rem; }
            .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; }
            .modal-body { padding: 1.5rem; }
            .modal-footer { padding: 1rem 1.5rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.5rem; }
            
            /* Mobile responsive */
            .mobile-toggle { display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; }
            @media (max-width: 768px) {
                .sidebar { transform: translateX(-100%); }
                .sidebar.open { transform: translateX(0); }
                .topbar { left: 0; }
                .main-content { margin-left: 0; }
                .mobile-toggle { display: block; }
                .grid-2, .grid-3 { grid-template-columns: 1fr; }
                .stats-row { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 480px) {
                .stats-row { grid-template-columns: 1fr; }
                .main-content { padding: 1rem; }
            }
        </style>
    </head>
    <body>
    HTML;
}

function renderSidebar($role, $activePage = '') {
    $initial = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
    $name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
    $roleLabels = [
        'admin' => '👑 Admin Panel',
        'monk' => '🧘 Monk Portal',
        'doctor' => '👨‍⚕️ Doctor Portal',
        'donator' => '💝 Donor Portal'
    ];
    $panelLabel = $roleLabels[$role] ?? 'User Portal';
    
    echo '<div class="sidebar" id="sidebar">';
    echo '<div class="sidebar-header">';
    echo '<h2>🏥 MHS v2.0</h2>';
    echo '<p>' . $panelLabel . '</p>';
    echo '</div>';
    echo '<nav class="sidebar-nav">';
    
    // Common for all
    $active = ($activePage === 'dashboard') ? ' active' : '';
    echo "<a href='dashboard' class='{$active}'>📊 Dashboard</a>";
    
    switch ($role) {
        case 'admin':
            echo '<div class="sidebar-section">Healthcare</div>';
            echo "<a href='dashboard?page=monks'" . ($activePage === 'monks' ? " class='active'" : "") . ">🧘 Monk Management</a>";
            echo "<a href='dashboard?page=doctors'" . ($activePage === 'doctors' ? " class='active'" : "") . ">👨‍⚕️ Doctor Management</a>";
            echo "<a href='dashboard?page=rooms'" . ($activePage === 'rooms' ? " class='active'" : "") . ">🏠 Room Management</a>";
            echo "<a href='dashboard?page=appointments'" . ($activePage === 'appointments' ? " class='active'" : "") . ">📅 Appointments</a>";
            echo "<a href='dashboard?page=medical'" . ($activePage === 'medical' ? " class='active'" : "") . ">📋 Medical Records</a>";
            
            echo '<div class="sidebar-section">Donations</div>';
            echo "<a href='dashboard?page=categories'" . ($activePage === 'categories' ? " class='active'" : "") . ">📂 Categories</a>";
            echo "<a href='dashboard?page=donations'" . ($activePage === 'donations' ? " class='active'" : "") . ">💰 Donations</a>";
            echo "<a href='dashboard?page=expenses'" . ($activePage === 'expenses' ? " class='active'" : "") . ">💸 Expenses</a>";
            
            echo '<div class="sidebar-section">Administration</div>';
            echo "<a href='dashboard?page=users'" . ($activePage === 'users' ? " class='active'" : "") . ">👥 Users</a>";
            echo "<a href='dashboard?page=reports'" . ($activePage === 'reports' ? " class='active'" : "") . ">📈 Reports</a>";
            echo "<a href='dashboard?page=transparency'" . ($activePage === 'transparency' ? " class='active'" : "") . ">🔍 Transparency</a>";
            break;
            
        case 'monk':
            echo '<div class="sidebar-section">My Health</div>';
            echo "<a href='dashboard?page=my-records'" . ($activePage === 'my-records' ? " class='active'" : "") . ">📋 My Medical Records</a>";
            echo "<a href='dashboard?page=book-appointment'" . ($activePage === 'book-appointment' ? " class='active'" : "") . ">📅 Book Appointment</a>";
            echo "<a href='dashboard?page=my-appointments'" . ($activePage === 'my-appointments' ? " class='active'" : "") . ">⏰ My Appointments</a>";
            echo "<a href='dashboard?page=my-room'" . ($activePage === 'my-room' ? " class='active'" : "") . ">🏠 My Room</a>";
            break;
            
        case 'doctor':
            echo '<div class="sidebar-section">Healthcare</div>';
            echo "<a href='dashboard?page=my-schedule'" . ($activePage === 'my-schedule' ? " class='active'" : "") . ">📅 My Schedule</a>";
            echo "<a href='dashboard?page=patient-list'" . ($activePage === 'patient-list' ? " class='active'" : "") . ">🧘 Patient List</a>";
            echo "<a href='dashboard?page=add-record'" . ($activePage === 'add-record' ? " class='active'" : "") . ">📋 Add Medical Record</a>";
            echo "<a href='dashboard?page=my-availability'" . ($activePage === 'my-availability' ? " class='active'" : "") . ">⏰ My Availability</a>";
            break;
            
        case 'donator':
            echo '<div class="sidebar-section">Donations</div>';
            echo "<a href='dashboard?page=make-donation'" . ($activePage === 'make-donation' ? " class='active'" : "") . ">💰 Make Donation</a>";
            echo "<a href='dashboard?page=my-donations'" . ($activePage === 'my-donations' ? " class='active'" : "") . ">📜 My Donations</a>";
            echo "<a href='dashboard?page=categories'" . ($activePage === 'categories' ? " class='active'" : "") . ">📂 Donation Categories</a>";
            echo "<a href='dashboard?page=transparency'" . ($activePage === 'transparency' ? " class='active'" : "") . ">🔍 Transparency</a>";
            break;
    }
    
    echo '<div class="sidebar-section">System</div>';
    echo '<a href="./">🏠 Homepage</a>';
    echo '<a href="logout">🚪 Logout</a>';
    
    echo '</nav></div>';
}

function renderTopbar($title = 'Dashboard') {
    $name = htmlspecialchars($_SESSION['user_name'] ?? 'User');
    $role = ucfirst($_SESSION['user_role'] ?? 'user');
    $initial = strtoupper(substr($name, 0, 1));
    
    echo <<<HTML
    <div class="topbar">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
            <span class="topbar-title">{$title}</span>
        </div>
        <div class="topbar-user">
            <div class="user-avatar">{$initial}</div>
            <div>
                <div class="user-name">{$name}</div>
                <div class="user-role">{$role}</div>
            </div>
            <a href="logout" class="logout-btn">Logout</a>
        </div>
    </div>
    HTML;
}

function renderFooter() {
    echo '</body></html>';
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['message']) . '</div>';
    }
}
