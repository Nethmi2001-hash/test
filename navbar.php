<?php
require_once __DIR__ . '/includes/language.php';
?>

<!-- Premium Dark Mode Toggle -->
<link rel="stylesheet" href="assets/css/premium-theme.css">
<link rel="stylesheet" href="assets/css/sacred-care-theme.css">
<script src="assets/js/theme-toggle.js"></script>

<style>
  :root {
    --monastery-saffron: #C2410C;
    --monastery-gold: #F59E0B;
    --monastery-accent: #1E3A8A;
    --monastery-cream: #FFF7ED;
  }

  .navbar-monastery {
    background: linear-gradient(135deg, var(--monastery-saffron) 0%, #9A3412 100%) !important;
    box-shadow: 0 2px 14px rgba(154, 52, 18, 0.30);
  }

  .navbar-monastery .navbar-brand,
  .navbar-monastery .nav-link {
    color: #fff !important;
    font-weight: 500;
  }

  .navbar-monastery .nav-link:hover,
  .navbar-monastery .nav-link:focus {
    color: var(--monastery-gold) !important;
  }

  .navbar-monastery .dropdown-menu {
    border-radius: 10px;
    border: 1px solid #f3dfc5;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }

  .navbar-monastery .dropdown-item:hover {
    background: #fff7ed;
    color: var(--monastery-accent);
  }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-monastery mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <i class="bi bi-person-hearts"></i> Seela Suwa Herath Arana
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> <?= __('dashboard') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="donation_management.php"><i class="bi bi-cash-coin"></i> <?= __('donations') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="bill_management.php"><i class="bi bi-receipt"></i> <?= __('bills') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="patient_appointments.php"><i class="bi bi-calendar-check"></i> <?= __('appointments') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up-arrow"></i> <?= __('reports') ?></a></li>
        <li class="nav-item"><a class="nav-link" href="chatbot.php"><i class="bi bi-robot"></i> <?= __('ai_assistant') ?></a></li>
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
            <i class="bi bi-translate"></i> <?= getCurrentLanguage() == 'si' ? 'සිං' : 'EN' ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item <?= getCurrentLanguage() == 'en' ? 'active' : '' ?>" href="?lang=en">
              <i class="bi bi-check-circle <?= getCurrentLanguage() == 'en' ? '' : 'invisible' ?>"></i> English
            </a></li>
            <li><a class="dropdown-item <?= getCurrentLanguage() == 'si' ? 'active' : '' ?>" href="?lang=si">
              <i class="bi bi-check-circle <?= getCurrentLanguage() == 'si' ? '' : 'invisible' ?>"></i> සිංහල
            </a></li>
          </ul>
        </li>
        <!-- Dark Mode Toggle -->
        <li class="nav-item">
            <button id="theme-toggle" class="nav-link btn btn-link" style="border:none; background:none; color: white; font-size: 1.2rem; transition: transform 0.3s; padding: 0.5rem 1rem; cursor: pointer; z-index: 10; position: relative;" title="Toggle Dark Mode">
            <i class="bi bi-moon-fill"></i>
          </button>
        </li>
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> <?= __('logout') ?></a></li>
      </ul>
    </div>
  </div>
</nav>
