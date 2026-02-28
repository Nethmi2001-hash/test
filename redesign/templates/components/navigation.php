<?php
$user = $_SESSION['user'] ?? null;
$current_route = $_SERVER['REQUEST_URI'] ?? '/';
?>

<?php if ($user): ?>
    <!-- Authenticated Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2 class="text-xl font-bold text-primary">MHS v2.0</h2>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($user['role_name'] ?? 'User') ?></p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="/dashboard" class="sidebar-nav-item <?= $current_route === '/dashboard' ? 'active' : '' ?>">
                <i class="icon-dashboard"></i>
                Dashboard
            </a>
            
            <?php if (in_array($user['role_name'], ['admin', 'doctor', 'helper'])): ?>
                <div class="nav-section">
                    <h4 class="nav-section-title text-xs uppercase font-semibold text-gray-400 px-4 py-2">Healthcare</h4>
                    
                    <a href="/healthcare/appointments" class="sidebar-nav-item <?= strpos($current_route, '/healthcare/appointments') === 0 ? 'active' : '' ?>">
                        <i class="icon-calendar"></i>
                        Appointments
                    </a>
                    
                    <a href="/healthcare/monks" class="sidebar-nav-item <?= strpos($current_route, '/healthcare/monks') === 0 ? 'active' : '' ?>">
                        <i class="icon-users"></i>
                        Monks
                    </a>
                    
                    <?php if (in_array($user['role_name'], ['doctor'])): ?>
                        <a href="/healthcare/records" class="sidebar-nav-item <?= strpos($current_route, '/healthcare/records') === 0 ? 'active' : '' ?>">
                            <i class="icon-file-medical"></i>
                            Medical Records
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($user['role_name'], ['admin'])): ?>
                        <a href="/healthcare/providers" class="sidebar-nav-item <?= strpos($current_route, '/healthcare/providers') === 0 ? 'active' : '' ?>">
                            <i class="icon-user-md"></i>
                            Healthcare Providers
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (in_array($user['role_name'], ['admin', 'helper', 'donor'])): ?>
                <div class="nav-section">
                    <h4 class="nav-section-title text-xs uppercase font-semibold text-gray-400 px-4 py-2">Donations</h4>
                    
                    <a href="/donations" class="sidebar-nav-item <?= strpos($current_route, '/donations') === 0 ? 'active' : '' ?>">
                        <i class="icon-heart"></i>
                        Donations
                    </a>
                    
                    <?php if (in_array($user['role_name'], ['admin'])): ?>
                        <a href="/donations/campaigns" class="sidebar-nav-item <?= strpos($current_route, '/donations/campaigns') === 0 ? 'active' : '' ?>">
                            <i class="icon-bullhorn"></i>
                            Campaigns
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="nav-section">
                <h4 class="nav-section-title text-xs uppercase font-semibold text-gray-400 px-4 py-2">Reports</h4>
                
                <a href="/reports" class="sidebar-nav-item <?= $current_route === '/reports' ? 'active' : '' ?>">
                    <i class="icon-chart-bar"></i>
                    Overview
                </a>
                
                <?php if (in_array($user['role_name'], ['admin', 'doctor'])): ?>
                    <a href="/reports/healthcare" class="sidebar-nav-item <?= strpos($current_route, '/reports/healthcare') === 0 ? 'active' : '' ?>">
                        <i class="icon-chart-line"></i>
                        Healthcare Reports
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($user['role_name'], ['admin', 'helper'])): ?>
                    <a href="/reports/donations" class="sidebar-nav-item <?= strpos($current_route, '/reports/donations') === 0 ? 'active' : '' ?>">
                        <i class="icon-chart-pie"></i>
                        Donation Reports
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($user['role_name'], ['admin'])): ?>
                    <a href="/reports/financial" class="sidebar-nav-item <?= strpos($current_route, '/reports/financial') === 0 ? 'active' : '' ?>">
                        <i class="icon-dollar-sign"></i>
                        Financial Reports
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if (in_array($user['role_name'], ['admin'])): ?>
                <div class="nav-section">
                    <h4 class="nav-section-title text-xs uppercase font-semibold text-gray-400 px-4 py-2">Administration</h4>
                    
                    <a href="/admin/users" class="sidebar-nav-item <?= strpos($current_route, '/admin/users') === 0 ? 'active' : '' ?>">
                        <i class="icon-users-cog"></i>
                        User Management
                    </a>
                    
                    <a href="/admin/settings" class="sidebar-nav-item <?= strpos($current_route, '/admin/settings') === 0 ? 'active' : '' ?>">
                        <i class="icon-cog"></i>
                        System Settings
                    </a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- Mobile overlay -->
    <div class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-30 hidden" id="sidebar-overlay"></div>
    
    <!-- Top navigation bar -->
    <nav class="navbar fixed top-0 right-0 left-0 md:left-280 z-20">
        <div class="navbar-content container">
            <!-- Mobile sidebar toggle -->
            <button class="sidebar-toggle md:hidden btn btn-secondary btn-sm" type="button">
                <i class="icon-menu"></i>
            </button>
            
            <!-- Page title -->
            <div class="page-title hidden md:block">
                <h1 class="text-lg font-semibold text-gray-900"><?= $page_title ?? 'Dashboard' ?></h1>
            </div>
            
            <!-- Right side navigation -->
            <div class="navbar-nav">
                <!-- Profile dropdown -->
                <div class="dropdown relative">
                    <button class="dropdown-toggle flex items-center gap-2 btn btn-secondary btn-sm" type="button">
                        <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-xs font-semibold">
                            <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="hidden sm:inline"><?= htmlspecialchars($user['first_name'] ?? 'User') ?></span>
                        <i class="icon-chevron-down"></i>
                    </button>
                    
                    <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 hidden">
                        <a href="/profile" class="dropdown-item block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="icon-user"></i>
                            Profile
                        </a>
                        <div class="dropdown-divider border-t border-gray-200 my-1"></div>
                        <form method="POST" action="/logout" class="dropdown-item">
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="icon-log-out"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main content area spacing -->
    <div class="pt-16"></div>
    
<?php else: ?>
    <!-- Public Navigation -->
    <nav class="navbar">
        <div class="navbar-content container">
            <a href="/" class="navbar-brand">Monastery Healthcare System</a>
            
            <div class="navbar-nav">
                <a href="/" class="nav-link <?= $current_route === '/' ? 'active' : '' ?>">Home</a>
                <a href="/donate" class="nav-link <?= $current_route === '/donate' ? 'active' : '' ?>">Donate</a>
                <a href="/transparency" class="nav-link <?= $current_route === '/transparency' ? 'active' : '' ?>">Transparency</a>
                <a href="/login" class="nav-link <?= $current_route === '/login' ? 'active' : '' ?>">Login</a>
            </div>
        </div>
    </nav>
<?php endif; ?>