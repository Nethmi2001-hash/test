<?php
require_once __DIR__ . '/includes/language.php';
$currentPage = basename($_SERVER['PHP_SELF']);

function isActivePage($pages, $currentPage) {
  if (!is_array($pages)) {
    $pages = [$pages];
  }
  return in_array($currentPage, $pages) ? 'active' : '';
}
?>

<!-- Premium Dark Mode Toggle -->
<link rel="stylesheet" href="assets/css/premium-theme.css">
<link rel="stylesheet" href="assets/css/sacred-care-theme.css">
<link rel="stylesheet" href="assets/css/monastery-theme.css">
<script src="assets/js/theme-toggle.js"></script>
<script src="assets/js/ui-interactions.js"></script>

<style>
  :root {
    --monastery-primary: #6E8662;
    --monastery-primary-dark: #4F6645;
    --monastery-accent: #8A5A3B;
    --monastery-secondary: #ECE5D8;
    --monastery-bg: #F7F4EE;
  }

  .navbar-monastery {
    background: linear-gradient(135deg, var(--monastery-primary) 0%, var(--monastery-primary-dark) 100%) !important;
    box-shadow: 0 2px 14px rgba(86, 67, 49, 0.30);
    border-bottom: 1px solid rgba(255,255,255,0.2);
  }

  .navbar-monastery .navbar-brand,
  .navbar-monastery .nav-link {
    color: #fff !important;
    font-weight: 500;
  }

  .navbar-monastery .nav-link {
    border-radius: 8px;
    padding: 8px 10px !important;
    transition: all 0.25s ease;
  }

  .navbar-monastery .nav-link:hover,
  .navbar-monastery .nav-link:focus {
    color: #fff !important;
    background: rgba(122, 30, 30, 0.45);
  }

  .navbar-monastery .nav-link.active {
    background: var(--monastery-accent);
    color: #fff !important;
  }

  .navbar-monastery .dropdown-menu {
    border-radius: 10px;
    border: 1px solid #e9dbc8;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .navbar-monastery .dropdown-item:hover {
    background: #f5efe6;
    color: var(--monastery-accent);
  }

  .navbar-monastery .menu-btn {
    border: 1px solid rgba(255,255,255,0.45);
    border-radius: 10px;
    color: #fff;
    background: transparent;
    padding: 8px 12px;
    margin-right: 10px;
  }

  .offcanvas-monastery {
    background: #F5EFE6;
  }

  .offcanvas-monastery .offcanvas-title {
    color: #4F3422;
    font-weight: 700;
  }

  .sidebar-link {
    display: block;
    padding: 10px 12px;
    border-radius: 10px;
    color: #4B5563;
    text-decoration: none;
    transition: all 0.25s ease;
    margin-bottom: 6px;
  }

  .sidebar-link:hover {
    background: #E9DDCD;
    color: #7A1E1E;
    transform: translateX(4px);
  }

  .sidebar-link.active {
    background: #7A1E1E;
    color: #fff;
  }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-monastery mb-4">
  <div class="container-fluid">
    <button class="menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainSidebar" aria-controls="mainSidebar">
      <i class="bi bi-list"></i>
    </button>
    <a class="navbar-brand" href="dashboard.php">
      <i class="bi bi-person-hearts"></i> Seela Suwa Herath Arana
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link <?= isActivePage('dashboard.php', $currentPage) ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i> <?= __('dashboard') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('donation_management.php', $currentPage) ?>" href="donation_management.php"><i class="bi bi-cash-coin"></i> <?= __('donations') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('bill_management.php', $currentPage) ?>" href="bill_management.php"><i class="bi bi-receipt"></i> <?= __('bills') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('patient_appointments.php', $currentPage) ?>" href="patient_appointments.php"><i class="bi bi-calendar-check"></i> <?= __('appointments') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('reports.php', $currentPage) ?>" href="reports.php"><i class="bi bi-graph-up-arrow"></i> <?= __('reports') ?></a></li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('chatbot.php', $currentPage) ?>" href="chatbot.php"><i class="bi bi-robot"></i> <?= __('ai_assistant') ?></a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> <?= __('manage') ?>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="table.php"><i class="bi bi-people"></i> <?= __('users') ?></a></li>
            <li><a class="dropdown-item" href="monk_management.php"><i class="bi bi-person-hearts"></i> <?= __('monks') ?></a></li>
            <li><a class="dropdown-item" href="doctor_management.php"><i class="bi bi-person-badge"></i> <?= __('doctors') ?></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="import_monks.php"><i class="bi bi-upload"></i> <?= __('import_monks') ?></a></li>
            <li><a class="dropdown-item" href="category_management.php"><i class="bi bi-tag"></i> <?= __('categories') ?></a></li>
            <li><a class="dropdown-item" href="title_management.php"><i class="bi bi-award"></i> <?= __('titles') ?></a></li>
            <li><a class="dropdown-item" href="doctor_availability.php"><i class="bi bi-clock"></i> <?= __('doctor_availability') ?></a></li>
            <li><a class="dropdown-item" href="room_management.php"><i class="bi bi-door-open"></i> <?= __('rooms') ?></a></li>
            <li><a class="dropdown-item" href="room_slot_management.php"><i class="bi bi-calendar3"></i> <?= __('room_slots') ?></a></li>
          </ul>
        </li>
        <!-- Language Switcher -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" title="<?= __('language') ?>">
            <i class="bi bi-translate"></i> <?= getCurrentLanguage() == 'si' ? '🇱🇰 සිංහල' : '🇬🇧 English' ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item <?= getCurrentLanguage() == 'en' ? 'active' : '' ?>" href="?lang=en">
              <i class="bi bi-check-circle <?= getCurrentLanguage() == 'en' ? '' : 'invisible' ?>"></i> 🇬🇧 English
            </a></li>
            <li><a class="dropdown-item <?= getCurrentLanguage() == 'si' ? 'active' : '' ?>" href="?lang=si">
              <i class="bi bi-check-circle <?= getCurrentLanguage() == 'si' ? '' : 'invisible' ?>"></i> 🇱🇰 සිංහල
            </a></li>
          </ul>
        </li>
        <!-- Dark Mode Toggle -->
        <li class="nav-item">
            <button id="theme-toggle" class="nav-link btn btn-link" style="border:none; background:none; color: white; font-size: 1.2rem; transition: transform 0.3s; padding: 0.5rem 1rem; cursor: pointer; z-index: 10; position: relative;" title="Toggle Dark Mode">
            <i class="bi bi-moon-fill"></i>
          </button>
        </li>
        <li class="nav-item"><a class="nav-link <?= isActivePage('logout.php', $currentPage) ?>" href="logout.php"><i class="bi bi-box-arrow-right"></i> <?= __('logout') ?></a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-start offcanvas-monastery" tabindex="-1" id="mainSidebar" aria-labelledby="mainSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mainSidebarLabel"><i class="bi bi-grid-1x2-fill"></i> System Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <a class="sidebar-link <?= isActivePage('dashboard.php', $currentPage) ?>" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a class="sidebar-link <?= isActivePage('donation_management.php', $currentPage) ?>" href="donation_management.php"><i class="bi bi-cash-stack"></i> Donation Management</a>
    <a class="sidebar-link <?= isActivePage('bill_management.php', $currentPage) ?>" href="bill_management.php"><i class="bi bi-receipt"></i> Bill Management</a>
    <a class="sidebar-link <?= isActivePage(['room_management.php','room_slot_management.php'], $currentPage) ?>" href="room_management.php"><i class="bi bi-door-open"></i> Room & Slots</a>
    <a class="sidebar-link <?= isActivePage('patient_appointments.php', $currentPage) ?>" href="patient_appointments.php"><i class="bi bi-calendar-week"></i> Appointments</a>
    <a class="sidebar-link <?= isActivePage('reports.php', $currentPage) ?>" href="reports.php"><i class="bi bi-bar-chart-line"></i> Reports</a>
    <a class="sidebar-link <?= isActivePage('chatbot.php', $currentPage) ?>" href="chatbot.php"><i class="bi bi-robot"></i> Chatbot</a>
    
    <div style="border-top: 2px solid rgba(138, 90, 59, 0.15); margin: 1rem 0; padding-top: 1rem;">
      <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; padding: 0 12px; margin-bottom: 8px;"><i class="bi bi-gear-fill"></i> System Management</div>
      <a class="sidebar-link <?= isActivePage('monk_management.php', $currentPage) ?>" href="monk_management.php"><i class="bi bi-person-hearts"></i> Monk Management</a>
      <a class="sidebar-link <?= isActivePage('doctor_management.php', $currentPage) ?>" href="doctor_management.php"><i class="bi bi-person-badge"></i> Doctor Management</a>
      <a class="sidebar-link <?= isActivePage(['room_management.php','room_slot_management.php'], $currentPage) ?>" href="room_management.php"><i class="bi bi-door-open"></i> Room & Slot Management</a>
      <a class="sidebar-link <?= isActivePage('table.php', $currentPage) ?>" href="table.php"><i class="bi bi-people"></i> User Management</a>
      <a class="sidebar-link <?= isActivePage('category_management.php', $currentPage) ?>" href="category_management.php"><i class="bi bi-tags"></i> Category Management</a>
      <a class="sidebar-link <?= isActivePage('title_management.php', $currentPage) ?>" href="title_management.php"><i class="bi bi-award"></i> Title Management</a>
      <a class="sidebar-link <?= isActivePage('doctor_availability.php', $currentPage) ?>" href="doctor_availability.php"><i class="bi bi-clock-history"></i> Doctor Availability</a>
      <a class="sidebar-link <?= isActivePage('import_monks.php', $currentPage) ?>" href="import_monks.php"><i class="bi bi-upload"></i> Import Monks</a>
    </div>
    
    <a class="sidebar-link" href="logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</div>
