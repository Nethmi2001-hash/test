<?php

?>

<!-- Premium Dark Mode Toggle -->
<link rel="stylesheet" href="assets/css/premium-theme.css">
<script src="assets/js/theme-toggle.js"></script>

<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <i class="bi bi-hospital"></i> Seela Suwa Herath Arana
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="donation_management.php"><i class="bi bi-cash-coin"></i> Donations</a></li>
        <li class="nav-item"><a class="nav-link" href="bill_management.php"><i class="bi bi-receipt"></i> Expenses</a></li>
        <li class="nav-item"><a class="nav-link" href="patient_appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-graph-up-arrow"></i> Reports</a></li>
        <li class="nav-item"><a class="nav-link" href="chatbot.php"><i class="bi bi-robot"></i> AI Assistant</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> Manage
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="table.php"><i class="bi bi-people"></i> Users</a></li>
            <li><a class="dropdown-item" href="monk_management.php"><i class="bi bi-person-hearts"></i> Monks</a></li>
            <li><a class="dropdown-item" href="doctor_management.php"><i class="bi bi-person-badge"></i> Doctors</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="import_monks.php"><i class="bi bi-upload"></i> Import Monks</a></li>
            <li><a class="dropdown-item" href="category_management.php"><i class="bi bi-tag"></i> Categories</a></li>
            <li><a class="dropdown-item" href="title_management.php"><i class="bi bi-award"></i> Titles</a></li>
            <li><a class="dropdown-item" href="doctor_availability.php"><i class="bi bi-clock"></i> Doctor Availability</a></li>
            <li><a class="dropdown-item" href="room_management.php"><i class="bi bi-door-open"></i> Rooms</a></li>
            <li><a class="dropdown-item" href="room_slot_management.php"><i class="bi bi-calendar3"></i> Room Slots</a></li>
          </ul>
        </li>
        <!-- Dark Mode Toggle -->
        <li class="nav-item">
          <button id="theme-toggle" class="nav-link btn btn-link" style="border:none; background:none; color: white; font-size: 1.2rem; transition: transform 0.3s;" title="Toggle Dark Mode">
            <i class="bi bi-moon-fill"></i>
          </button>
        </li>
        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
