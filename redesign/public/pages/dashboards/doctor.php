<?php
/**
 * Doctor Dashboard - Monastery Healthcare System
 */

require_once __DIR__ . '/../layout.php';

$db = Database::getInstance();
$page = $_GET['page'] ?? 'dashboard';
$userId = $_SESSION['user_id'];

// Get doctor profile
$doctor = null;
try {
    $doctor = $db->fetch("SELECT d.*, u.full_name, u.email, u.phone FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?", [$userId]);
} catch(Exception $e) {}

$doctorId = $doctor['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctorId) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'add_record':
                $recordId = 'MR-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $db->insert('medical_records', [
                    'record_id' => $recordId,
                    'monk_id' => $_POST['monk_id'],
                    'doctor_id' => $doctorId,
                    'appointment_id' => !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null,
                    'visit_date' => date('Y-m-d'),
                    'chief_complaint' => $_POST['chief_complaint'] ?? '',
                    'diagnosis' => $_POST['diagnosis'],
                    'treatment_notes' => $_POST['treatment_notes'],
                    'prescription' => $_POST['prescription'],
                    'vital_signs' => $_POST['vital_signs'] ?? null,
                    'lab_results' => $_POST['lab_results'] ?? null,
                    'next_appointment_date' => !empty($_POST['next_appointment_date']) ? $_POST['next_appointment_date'] : null,
                    'status' => 'active'
                ]);
                
                // If linked to appointment, mark it completed
                if (!empty($_POST['appointment_id'])) {
                    $db->update('appointments', ['status' => 'completed'], 'id = ?', [$_POST['appointment_id']]);
                }
                
                setFlash('success', 'Medical record created! ID: ' . $recordId);
                break;
                
            case 'complete_appointment':
                $db->update('appointments', ['status' => 'completed'], 'id = ? AND doctor_id = ?', [$_POST['appointment_id'], $doctorId]);
                setFlash('success', 'Appointment marked as completed.');
                break;
                
            case 'update_availability':
                $db->update('doctors', [
                    'available_days' => implode(',', $_POST['available_days'] ?? []),
                    'availability_start' => $_POST['availability_start'],
                    'availability_end' => $_POST['availability_end']
                ], 'id = ?', [$doctorId]);
                setFlash('success', 'Availability updated!');
                break;
        }
    } catch(Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
    }
    header("Location: dashboard?page=" . $page);
    exit;
}

renderHeader('Doctor Dashboard');
renderSidebar('doctor', $page);
renderTopbar(ucfirst(str_replace('-', ' ', $page)));
?>

<div class="main-content">
    <?php renderFlash(); ?>

<?php if ($page === 'dashboard'): ?>
    <?php if ($doctor): ?>
        <div class="card" style="margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">👨‍⚕️</div>
                <div>
                    <h2 style="margin: 0 0 0.25rem 0;">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h2>
                    <p style="color: #64748b; margin: 0;">ID: <?= htmlspecialchars($doctor['doctor_id']) ?> | <?= htmlspecialchars($doctor['specialization']) ?></p>
                    <p style="color: #64748b; margin: 0.25rem 0 0 0;">License: <?= htmlspecialchars($doctor['license_number']) ?> | <?= $doctor['experience_years'] ?> years experience</p>
                </div>
            </div>
        </div>
        
        <div class="stats-row">
            <?php
            try {
                $todayAppts = $db->count('appointments', "doctor_id = ? AND appointment_date = CURDATE() AND status = 'scheduled'", [$doctorId]);
                $totalPatients = $db->fetch("SELECT COUNT(DISTINCT monk_id) as count FROM medical_records WHERE doctor_id = ?", [$doctorId])['count'];
                $totalRecords = $db->count('medical_records', "doctor_id = ?", [$doctorId]);
                $upcomingAppts = $db->count('appointments', "doctor_id = ? AND appointment_date >= CURDATE() AND status = 'scheduled'", [$doctorId]);
            } catch(Exception $e) { $todayAppts = 0; $totalPatients = 0; $totalRecords = 0; $upcomingAppts = 0; }
            ?>
            <div class="stat-card">
                <div class="stat-icon orange">📅</div>
                <div class="stat-info"><h4>Today's Appointments</h4><div class="number"><?= $todayAppts ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-info"><h4>Total Patients</h4><div class="number"><?= $totalPatients ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📋</div>
                <div class="stat-info"><h4>Medical Records</h4><div class="number"><?= $totalRecords ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🔮</div>
                <div class="stat-info"><h4>Upcoming</h4><div class="number"><?= $upcomingAppts ?></div></div>
            </div>
        </div>
        
        <!-- Today's Schedule -->
        <div class="card">
            <h3>📅 Today's Schedule (<?= date('l, F j, Y') ?>)</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>Time</th><th>Monk</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php
                    try {
                        $today = $db->fetchAll("SELECT a.*, um.full_name as monk_name FROM appointments a JOIN monks m ON a.monk_id = m.id JOIN users um ON m.user_id = um.id WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() ORDER BY a.appointment_time", [$doctorId]);
                        foreach ($today as $a): ?>
                            <tr>
                                <td><strong><?= $a['appointment_time'] ?></strong></td>
                                <td><?= htmlspecialchars($a['monk_name']) ?></td>
                                <td><?= htmlspecialchars(substr($a['reason'] ?? 'N/A', 0, 50)) ?></td>
                                <td><span class="badge badge-<?= $a['status'] === 'completed' ? 'green' : 'blue' ?>"><?= ucfirst($a['status']) ?></span></td>
                                <td>
                                    <?php if ($a['status'] === 'scheduled'): ?>
                                        <a href="dashboard?page=add-record&appointment=<?= $a['id'] ?>&monk=<?= $a['monk_id'] ?>" class="btn btn-primary btn-sm">Add Record</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach;
                        if (empty($today)) echo '<tr><td colspan="5" style="text-align:center">No appointments today</td></tr>';
                    } catch(Exception $e) { echo '<tr><td colspan="5">No data</td></tr>'; }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-error">Doctor profile not found. Please contact administration.</div>
    <?php endif; ?>

<?php elseif ($page === 'my-schedule'): ?>
    <!-- Full Schedule -->
    <h2 style="margin-bottom: 1.5rem;">📅 My Schedule</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Monk</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                try {
                    $schedule = $db->fetchAll("SELECT a.*, um.full_name as monk_name FROM appointments a JOIN monks m ON a.monk_id = m.id JOIN users um ON m.user_id = um.id WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date, a.appointment_time", [$doctorId]);
                    foreach ($schedule as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                            <td><?= htmlspecialchars($a['monk_name']) ?></td>
                            <td><?= $a['appointment_date'] ?></td>
                            <td><?= $a['appointment_time'] ?></td>
                            <td><?= htmlspecialchars(substr($a['reason'] ?? '', 0, 40)) ?></td>
                            <td><span class="badge badge-<?= $a['status'] === 'completed' ? 'green' : 'blue' ?>"><?= ucfirst($a['status']) ?></span></td>
                            <td>
                                <?php if ($a['status'] === 'scheduled'): ?>
                                    <a href="dashboard?page=add-record&appointment=<?= $a['id'] ?>&monk=<?= $a['monk_id'] ?>" class="btn btn-primary btn-sm">Add Record</a>
                                    <form method="POST" style="display:inline;"><input type="hidden" name="action" value="complete_appointment"><input type="hidden" name="appointment_id" value="<?= $a['id'] ?>"><button class="btn btn-success btn-sm">✓ Done</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($schedule)) echo '<tr><td colspan="7" style="text-align:center">No upcoming appointments</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">No data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'patient-list'): ?>
    <!-- Patient List -->
    <h2 style="margin-bottom: 1.5rem;">👥 My Patients</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Monk ID</th><th>Name</th><th>Age</th><th>Blood</th><th>Conditions</th><th>Records</th><th>Last Visit</th></tr></thead>
                <tbody>
                <?php
                try {
                    $patients = $db->fetchAll("SELECT m.*, u.full_name, u.phone, 
                        (SELECT COUNT(*) FROM medical_records WHERE monk_id = m.id AND doctor_id = ?) as record_count,
                        (SELECT MAX(visit_date) FROM medical_records WHERE monk_id = m.id AND doctor_id = ?) as last_visit
                        FROM monks m JOIN users u ON m.user_id = u.id 
                        WHERE m.id IN (SELECT DISTINCT monk_id FROM medical_records WHERE doctor_id = ?) OR m.id IN (SELECT DISTINCT monk_id FROM appointments WHERE doctor_id = ?)
                        ORDER BY u.full_name", [$doctorId, $doctorId, $doctorId, $doctorId]);
                    foreach ($patients as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['monk_id']) ?></td>
                            <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
                            <td><?= $p['age'] ?? 'N/A' ?></td>
                            <td><span class="badge badge-red"><?= $p['blood_group'] ?></span></td>
                            <td><?= htmlspecialchars(substr($p['medical_conditions'] ?? 'None', 0, 40)) ?></td>
                            <td><?= $p['record_count'] ?></td>
                            <td><?= $p['last_visit'] ?? 'N/A' ?></td>
                        </tr>
                    <?php endforeach;
                    if (empty($patients)) echo '<tr><td colspan="7" style="text-align:center">No patients yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">No data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'add-record'): ?>
    <!-- Add Medical Record -->
    <h2 style="margin-bottom: 1.5rem;">📝 Add Medical Record</h2>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="add_record">
            <?php if (isset($_GET['appointment'])): ?>
                <input type="hidden" name="appointment_id" value="<?= intval($_GET['appointment']) ?>">
            <?php endif; ?>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Select Monk (Patient) *</label>
                    <select name="monk_id" class="form-control" required>
                        <option value="">-- Choose Monk --</option>
                        <?php
                        try {
                            $monks = $db->fetchAll("SELECT m.*, u.full_name FROM monks m JOIN users u ON m.user_id = u.id WHERE m.status = 'active' ORDER BY u.full_name");
                            foreach ($monks as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= isset($_GET['monk']) && $_GET['monk'] == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['full_name']) ?> (<?= $m['monk_id'] ?>)</option>
                            <?php endforeach;
                        } catch(Exception $e) {}
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Chief Complaint</label>
                <textarea name="chief_complaint" class="form-control" rows="2" placeholder="Patient's chief complaint..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Diagnosis *</label>
                <textarea name="diagnosis" class="form-control" rows="3" required placeholder="Enter the diagnosis..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Treatment</label>
                <textarea name="treatment_notes" class="form-control" rows="3" placeholder="Treatment administered..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Prescription</label>
                <textarea name="prescription" class="form-control" rows="3" placeholder="Medications prescribed..."></textarea>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Vital Signs</label>
                    <input type="text" name="vital_signs" class="form-control" placeholder="e.g. BP: 120/80, Temp: 98.6°F">
                </div>
                <div class="form-group">
                    <label>Lab Results</label>
                    <input type="text" name="lab_results" class="form-control" placeholder="Any lab results...">
                </div>
                <div class="form-group">
                    <label>Follow-up Date</label>
                    <input type="date" name="next_appointment_date" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">💾 Save Medical Record</button>
        </form>
    </div>

<?php elseif ($page === 'my-availability'): ?>
    <!-- Update Availability -->
    <h2 style="margin-bottom: 1.5rem;">⏰ My Availability</h2>
    <div class="card">
        <form method="POST">
            <input type="hidden" name="action" value="update_availability">
            
            <div class="form-group">
                <label>Available Days *</label>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem;">
                    <?php 
                    $currentDays = explode(',', $doctor['available_days'] ?? '');
                    foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day): ?>
                        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;padding:0.5rem 1rem;background:#f1f5f9;border-radius:0.5rem;cursor:pointer;">
                            <input type="checkbox" name="available_days[]" value="<?= $day ?>" <?= in_array($day, $currentDays) ? 'checked' : '' ?>> <?= ucfirst($day) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Available From</label>
                    <input type="time" name="availability_start" class="form-control" value="<?= $doctor['availability_start'] ?? '09:00' ?>">
                </div>
                <div class="form-group">
                    <label>Available Until</label>
                    <input type="time" name="availability_end" class="form-control" value="<?= $doctor['availability_end'] ?? '17:00' ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">💾 Update Availability</button>
        </form>
    </div>
    
    <div class="card" style="margin-top: 1.5rem;">
        <h3>Current Availability</h3>
        <div style="padding: 1rem 0;">
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;"><span>Days:</span><strong><?= ucwords(str_replace(',', ', ', $doctor['available_days'] ?? 'Not set')) ?></strong></div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;"><span>Hours:</span><strong><?= ($doctor['availability_start'] ?? '?') ?> - <?= ($doctor['availability_end'] ?? '?') ?></strong></div>
            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Status:</span><span class="badge badge-green">Active</span></div>
        </div>
    </div>

<?php endif; ?>

</div>

<?php renderFooter(); ?>
