<?php
/**
 * Monk Dashboard - Monastery Healthcare System
 */

require_once __DIR__ . '/../layout.php';

$db = Database::getInstance();
$page = $_GET['page'] ?? 'dashboard';
$userId = $_SESSION['user_id'];

// Get monk profile
$monk = null;
try {
    $monk = $db->fetch("SELECT m.*, u.full_name, u.email, u.phone, r.room_number, r.room_type, r.building 
        FROM monks m JOIN users u ON m.user_id = u.id LEFT JOIN rooms r ON m.room_id = r.id 
        WHERE m.user_id = ?", [$userId]);
} catch(Exception $e) {}

$monkId = $monk['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $monkId) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'book_appointment':
                $apptId = 'APT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $db->insert('appointments', [
                    'appointment_id' => $apptId,
                    'monk_id' => $monkId,
                    'doctor_id' => $_POST['doctor_id'],
                    'appointment_date' => $_POST['appointment_date'],
                    'appointment_time' => $_POST['appointment_time'],
                    'reason' => $_POST['reason'],
                    'status' => 'scheduled'
                ]);
                setFlash('success', 'Appointment booked! ID: ' . $apptId);
                break;
                
            case 'cancel_appointment':
                $db->update('appointments', ['status' => 'cancelled'], 'id = ? AND monk_id = ?', [$_POST['appointment_id'], $monkId]);
                setFlash('success', 'Appointment cancelled.');
                break;
        }
    } catch(Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
    }
    header("Location: dashboard?page=" . $page);
    exit;
}

renderHeader('Monk Dashboard');
renderSidebar('monk', $page);
renderTopbar(ucfirst(str_replace('-', ' ', $page)));
?>

<div class="main-content">
    <?php renderFlash(); ?>

<?php if ($page === 'dashboard'): ?>
    <!-- Monk Dashboard Overview -->
    <?php if ($monk): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">🧘</div>
                <div>
                    <h2 style="margin: 0 0 0.25rem 0;"><?= htmlspecialchars($monk['full_name']) ?></h2>
                    <p style="color: #64748b; margin: 0;">ID: <?= htmlspecialchars($monk['monk_id']) ?> | Blood Group: <span class="badge badge-red"><?= $monk['blood_group'] ?></span></p>
                    <p style="color: #64748b; margin: 0.25rem 0 0 0;">
                        <?php if ($monk['room_number']): ?>
                            Room: <?= htmlspecialchars($monk['room_number']) ?> (<?= ucfirst($monk['room_type']) ?>) - <?= htmlspecialchars($monk['building']) ?>
                        <?php else: ?>
                            No room assigned
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="stats-row">
            <?php
            try {
                $upcomingAppts = $db->count('appointments', "monk_id = ? AND appointment_date >= CURDATE() AND status = 'scheduled'", [$monkId]);
                $totalRecords = $db->count('medical_records', "monk_id = ?", [$monkId]);
                $lastVisit = $db->fetch("SELECT visit_date FROM medical_records WHERE monk_id = ? ORDER BY visit_date DESC LIMIT 1", [$monkId]);
            } catch(Exception $e) { $upcomingAppts = 0; $totalRecords = 0; $lastVisit = null; }
            ?>
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info"><h4>Upcoming Appointments</h4><div class="number"><?= $upcomingAppts ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📋</div>
                <div class="stat-info"><h4>Medical Records</h4><div class="number"><?= $totalRecords ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🕐</div>
                <div class="stat-info"><h4>Last Visit</h4><div class="number" style="font-size: 1.1rem;"><?= $lastVisit ? $lastVisit['visit_date'] : 'N/A' ?></div></div>
            </div>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="card">
            <h3>📅 Upcoming Appointments</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>ID</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    try {
                        $appts = $db->fetchAll("SELECT a.*, ud.full_name as doctor_name, d.specialization FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id WHERE a.monk_id = ? AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date ASC LIMIT 5", [$monkId]);
                        foreach ($appts as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                                <td><?= htmlspecialchars($a['doctor_name']) ?> <small>(<?= $a['specialization'] ?>)</small></td>
                                <td><?= $a['appointment_date'] ?></td>
                                <td><?= $a['appointment_time'] ?></td>
                                <td><?= htmlspecialchars(substr($a['reason'] ?? '', 0, 40)) ?></td>
                                <td><span class="badge badge-blue"><?= ucfirst($a['status']) ?></span></td>
                            </tr>
                        <?php endforeach;
                        if (empty($appts)) echo '<tr><td colspan="6" style="text-align:center">No upcoming appointments</td></tr>';
                    } catch(Exception $e) { echo '<tr><td colspan="6">No data</td></tr>'; }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-error">Monk profile not found. Please contact administration.</div>
    <?php endif; ?>

<?php elseif ($page === 'my-records'): ?>
    <!-- Medical Records -->
    <h2 style="margin-bottom: 1.5rem;">📋 My Medical Records</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Record ID</th><th>Doctor</th><th>Date</th><th>Diagnosis</th><th>Treatment</th><th>Prescription</th><th>Follow-up</th></tr></thead>
                <tbody>
                <?php
                try {
                    $records = $db->fetchAll("SELECT mr.*, ud.full_name as doctor_name FROM medical_records mr JOIN doctors d ON mr.doctor_id = d.id JOIN users ud ON d.user_id = ud.id WHERE mr.monk_id = ? ORDER BY mr.visit_date DESC", [$monkId]);
                    foreach ($records as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['record_id']) ?></td>
                            <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                            <td><?= $r['visit_date'] ?></td>
                            <td><?= htmlspecialchars($r['diagnosis']) ?></td>
                            <td><?= htmlspecialchars(substr($r['treatment_notes'] ?? 'N/A', 0, 40)) ?></td>
                            <td><?= htmlspecialchars(substr($r['prescription'] ?? 'N/A', 0, 40)) ?></td>
                            <td><?= $r['next_appointment_date'] ?? 'None' ?></td>
                        </tr>
                    <?php endforeach;
                    if (empty($records)) echo '<tr><td colspan="7" style="text-align:center">No medical records found</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">No records available</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'book-appointment'): ?>
    <!-- Book Appointment -->
    <h2 style="margin-bottom: 1.5rem;">📅 Book an Appointment</h2>
    
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="book_appointment">
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Select Doctor *</label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="">-- Choose a Doctor --</option>
                        <?php
                        try {
                            $doctors = $db->fetchAll("SELECT d.*, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.status = 'active' ORDER BY u.full_name");
                            foreach ($doctors as $d): ?>
                                <option value="<?= $d['id'] ?>">Dr. <?= htmlspecialchars($d['full_name']) ?> (<?= $d['specialization'] ?>) - <?= ucwords(str_replace(',', ', ', $d['available_days'])) ?></option>
                            <?php endforeach;
                        } catch(Exception $e) {}
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Appointment Date *</label>
                    <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Preferred Time *</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Reason / Symptoms *</label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Describe your symptoms or reason for the appointment..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">📅 Book Appointment</button>
        </form>
    </div>
    
    <!-- Available Doctors -->
    <div class="card" style="margin-top: 1.5rem;">
        <h3>👨‍⚕️ Available Doctors</h3>
        <div class="table-container">
            <table>
                <thead><tr><th>Doctor</th><th>Specialization</th><th>Available Days</th><th>Hours</th></tr></thead>
                <tbody>
                <?php
                try {
                    foreach ($doctors as $d): ?>
                        <tr>
                            <td><strong>Dr. <?= htmlspecialchars($d['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($d['specialization']) ?></td>
                            <td><?= ucwords(str_replace(',', ', ', $d['available_days'])) ?></td>
                            <td><?= $d['availability_start'] ?> - <?= $d['availability_end'] ?></td>
                        </tr>
                    <?php endforeach;
                } catch(Exception $e) {}
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'my-appointments'): ?>
    <!-- My Appointments -->
    <h2 style="margin-bottom: 1.5rem;">📋 My Appointments</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                try {
                    $appointments = $db->fetchAll("SELECT a.*, ud.full_name as doctor_name, d.specialization FROM appointments a JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id WHERE a.monk_id = ? ORDER BY a.appointment_date DESC", [$monkId]);
                    foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                            <td>Dr. <?= htmlspecialchars($a['doctor_name']) ?> <small>(<?= $a['specialization'] ?>)</small></td>
                            <td><?= $a['appointment_date'] ?></td>
                            <td><?= $a['appointment_time'] ?></td>
                            <td><?= htmlspecialchars(substr($a['reason'] ?? '', 0, 40)) ?></td>
                            <td><span class="badge badge-<?= $a['status'] === 'completed' ? 'green' : ($a['status'] === 'scheduled' ? 'blue' : ($a['status'] === 'cancelled' ? 'red' : 'yellow')) ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td>
                                <?php if ($a['status'] === 'scheduled'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel this appointment?')">
                                        <input type="hidden" name="action" value="cancel_appointment">
                                        <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                        <button class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($appointments)) echo '<tr><td colspan="7" style="text-align:center">No appointments</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">No appointments</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'my-room'): ?>
    <!-- My Room -->
    <h2 style="margin-bottom: 1.5rem;">🏠 My Room</h2>
    <?php if ($monk && $monk['room_number']): ?>
        <div class="card">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Room Number</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= htmlspecialchars($monk['room_number']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Type</div>
                    <div style="font-size: 1.25rem; font-weight: 600;"><?= ucfirst($monk['room_type']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase;">Building</div>
                    <div style="font-size: 1.25rem; font-weight: 600;"><?= htmlspecialchars($monk['building']) ?></div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No room has been assigned to you yet. Please contact the administration.</div>
    <?php endif; ?>

<?php endif; ?>

</div>

<?php renderFooter(); ?>
