<?php
/**
 * Admin Dashboard - Monastery Healthcare System
 */

require_once __DIR__ . '/../layout.php';

$db = Database::getInstance();
$page = $_GET['page'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_monk':
                $pass = password_hash($_POST['password'] ?? 'monk123', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $userId = $db->insert('users', [
                    'email' => $_POST['email'],
                    'password' => $pass,
                    'role' => 'monk',
                    'full_name' => $_POST['full_name'],
                    'phone' => $_POST['phone'],
                    'status' => 'active'
                ]);
                $db->insert('monks', [
                    'user_id' => $userId,
                    'monk_id' => 'MONK' . str_pad($userId, 3, '0', STR_PAD_LEFT),
                    'ordained_date' => $_POST['ordained_date'],
                    'age' => $_POST['age'] ?? null,
                    'emergency_contact' => $_POST['emergency_contact'],
                    'emergency_phone' => $_POST['emergency_phone'],
                    'blood_group' => $_POST['blood_group'],
                    'medical_conditions' => $_POST['medical_conditions'],
                    'room_id' => !empty($_POST['room_id']) ? $_POST['room_id'] : null,
                    'status' => 'active'
                ]);
                if (!empty($_POST['room_id'])) {
                    $db->query("UPDATE rooms SET current_occupancy = current_occupancy + 1, status = IF(current_occupancy + 1 >= capacity, 'occupied', 'available') WHERE id = ?", [$_POST['room_id']]);
                }
                setFlash('success', 'Monk added successfully!');
                break;

            case 'add_doctor':
                $pass = password_hash($_POST['password'] ?? 'doctor123', PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $userId = $db->insert('users', [
                    'email' => $_POST['email'],
                    'password' => $pass,
                    'role' => 'doctor',
                    'full_name' => $_POST['full_name'],
                    'phone' => $_POST['phone'],
                    'status' => 'active'
                ]);
                $db->insert('doctors', [
                    'user_id' => $userId,
                    'doctor_id' => 'DOC' . str_pad($userId, 3, '0', STR_PAD_LEFT),
                    'specialization' => $_POST['specialization'],
                    'qualifications' => $_POST['qualifications'] ?? '',
                    'experience_years' => $_POST['experience_years'],
                    'license_number' => $_POST['license_number'],
                    'available_days' => implode(',', $_POST['available_days'] ?? []),
                    'availability_start' => $_POST['availability_start'],
                    'availability_end' => $_POST['availability_end'],
                    'status' => 'active'
                ]);
                setFlash('success', 'Doctor added successfully!');
                break;

            case 'add_category':
                $db->insert('donation_categories', [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'target_amount' => $_POST['target_amount'],
                    'monthly_target' => $_POST['monthly_target'],
                    'priority' => $_POST['priority'],
                    'status' => 'active'
                ]);
                setFlash('success', 'Donation category added!');
                break;

            case 'add_expense':
                $db->insert('expenses', [
                    'expense_id' => 'EXP-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'category_id' => $_POST['category_id'],
                    'amount' => $_POST['amount'],
                    'description' => $_POST['description'],
                    'expense_date' => $_POST['expense_date'],
                    'vendor_name' => $_POST['vendor_name'],
                    'payment_method' => $_POST['payment_method'],
                    'status' => 'pending',
                    'created_by' => $_SESSION['user_id']
                ]);
                setFlash('success', 'Expense recorded!');
                break;

            case 'add_room':
                $db->insert('rooms', [
                    'room_number' => $_POST['room_number'],
                    'room_type' => $_POST['room_type'],
                    'capacity' => $_POST['capacity'],
                    'floor' => $_POST['floor'],
                    'building' => $_POST['building'],
                    'facilities' => $_POST['facilities'],
                    'status' => 'available'
                ]);
                setFlash('success', 'Room added!');
                break;
                
            case 'verify_donation':
                $db->update('donations', [
                    'status' => $_POST['status'],
                    'verification_notes' => $_POST['notes'] ?? '',
                    'verified_by' => $_SESSION['user_id'],
                    'verified_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$_POST['donation_id']]);
                if ($_POST['status'] === 'completed') {
                    $donation = $db->fetch("SELECT category_id, amount FROM donations WHERE id = ?", [$_POST['donation_id']]);
                    $db->query("UPDATE donation_categories SET current_amount = current_amount + ? WHERE id = ?", [$donation['amount'], $donation['category_id']]);
                }
                setFlash('success', 'Donation status updated!');
                break;
                
            case 'delete_monk':
                $monk = $db->fetch("SELECT user_id, room_id FROM monks WHERE id = ?", [$_POST['monk_id']]);
                if ($monk) {
                    if ($monk['room_id']) {
                        $db->query("UPDATE rooms SET current_occupancy = GREATEST(0, current_occupancy - 1), status = 'available' WHERE id = ?", [$monk['room_id']]);
                    }
                    $db->delete('monks', 'id = ?', [$_POST['monk_id']]);
                    $db->delete('users', 'id = ?', [$monk['user_id']]);
                }
                setFlash('success', 'Monk removed.');
                break;
                
            case 'delete_doctor':
                $doctor = $db->fetch("SELECT user_id FROM doctors WHERE id = ?", [$_POST['doctor_id']]);
                if ($doctor) {
                    $db->delete('doctors', 'id = ?', [$_POST['doctor_id']]);
                    $db->delete('users', 'id = ?', [$doctor['user_id']]);
                }
                setFlash('success', 'Doctor removed.');
                break;

            case 'add_user':
                $pass = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $db->insert('users', [
                    'email' => $_POST['email'],
                    'password' => $pass,
                    'role' => $_POST['role'],
                    'full_name' => $_POST['full_name'],
                    'phone' => $_POST['phone'],
                    'status' => 'active'
                ]);
                setFlash('success', 'User created!');
                break;
        }
    } catch (Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
    }
    
    // PRG pattern: redirect to prevent double-submit
    header("Location: dashboard?page=" . $page);
    exit;
}

// Fetch data based on page
$stats = [];
try {
    $stats['monks'] = $db->count('monks', "status = 'active'");
    $stats['doctors'] = $db->count('doctors', "status = 'active'");
    $stats['donations_total'] = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status = 'completed'")['total'];
    $stats['donations_pending'] = $db->count('donations', "status = 'pending'");
    $stats['expenses_total'] = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM expenses")['total'];
    $stats['appointments'] = $db->count('appointments', "appointment_date >= CURDATE()");
    $stats['rooms_available'] = $db->count('rooms', "status = 'available'");
    $stats['donators'] = $db->count('donators');
} catch (Exception $e) {
    // If tables don't exist yet, use defaults
    $stats = array_fill_keys(['monks', 'doctors', 'donations_total', 'donations_pending', 'expenses_total', 'appointments', 'rooms_available', 'donators'], 0);
}

renderHeader('Admin Dashboard');
renderSidebar('admin', $page);
renderTopbar(ucfirst(str_replace('-', ' ', $page)));
?>

<div class="main-content">
    <?php renderFlash(); ?>

<?php if ($page === 'dashboard'): ?>
    <!-- Admin Dashboard Overview -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue">🧘</div>
            <div class="stat-info">
                <h4>Total Monks</h4>
                <div class="number"><?= $stats['monks'] ?></div>
                <div class="change">Active residents</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">👨‍⚕️</div>
            <div class="stat-info">
                <h4>Doctors</h4>
                <div class="number"><?= $stats['doctors'] ?></div>
                <div class="change">Available doctors</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">💰</div>
            <div class="stat-info">
                <h4>Total Donations</h4>
                <div class="number">Rs. <?= number_format($stats['donations_total'], 2) ?></div>
                <div class="change"><?= $stats['donations_pending'] ?> pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">💸</div>
            <div class="stat-info">
                <h4>Total Expenses</h4>
                <div class="number">Rs. <?= number_format($stats['expenses_total'], 2) ?></div>
                <div class="change">Approved expenses</div>
            </div>
        </div>
    </div>
    
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon teal">📅</div>
            <div class="stat-info">
                <h4>Upcoming Appointments</h4>
                <div class="number"><?= $stats['appointments'] ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🏠</div>
            <div class="stat-info">
                <h4>Rooms Available</h4>
                <div class="number"><?= $stats['rooms_available'] ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💝</div>
            <div class="stat-info">
                <h4>Donators</h4>
                <div class="number"><?= $stats['donators'] ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">💵</div>
            <div class="stat-info">
                <h4>Balance</h4>
                <div class="number">Rs. <?= number_format($stats['donations_total'] - $stats['expenses_total'], 2) ?></div>
                <div class="change">Donations - Expenses</div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3>📈 Recent Donations</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>ID</th><th>Amount</th><th>Category</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php
                    try {
                        $donations = $db->fetchAll("SELECT d.*, dc.name as category_name FROM donations d JOIN donation_categories dc ON d.category_id = dc.id ORDER BY d.created_at DESC LIMIT 5");
                        foreach ($donations as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['donation_id']) ?></td>
                                <td>Rs. <?= number_format($d['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($d['category_name']) ?></td>
                                <td><span class="badge badge-<?= $d['status'] === 'completed' ? 'green' : ($d['status'] === 'pending' ? 'yellow' : 'red') ?>"><?= ucfirst($d['status']) ?></span></td>
                                <td><?= $d['donation_date'] ?></td>
                            </tr>
                        <?php endforeach;
                    } catch(Exception $e) { echo '<tr><td colspan="5">No donations yet</td></tr>'; }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <h3>💸 Recent Expenses</h3>
            <div class="table-container">
                <table>
                    <thead><tr><th>ID</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php
                    try {
                        $expenses = $db->fetchAll("SELECT * FROM expenses ORDER BY created_at DESC LIMIT 5");
                        foreach ($expenses as $exp): ?>
                            <tr>
                                <td><?= htmlspecialchars($exp['expense_id']) ?></td>
                                <td>Rs. <?= number_format($exp['amount'], 2) ?></td>
                                <td><?= htmlspecialchars(substr($exp['description'], 0, 40)) ?>...</td>
                                <td><?= $exp['expense_date'] ?></td>
                            </tr>
                        <?php endforeach;
                    } catch(Exception $e) { echo '<tr><td colspan="4">No expenses yet</td></tr>'; }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($page === 'monks'): ?>
    <!-- Monk Management -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>🧘 Monk Management</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addMonkModal').classList.add('active')">+ Add Monk</button>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Age</th><th>Blood</th><th>Room</th><th>Ordained</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                try {
                    $monks = $db->fetchAll("SELECT m.*, u.full_name, u.email, u.phone, r.room_number FROM monks m JOIN users u ON m.user_id = u.id LEFT JOIN rooms r ON m.room_id = r.id WHERE m.status = 'active' ORDER BY u.full_name");
                    foreach ($monks as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['monk_id']) ?></td>
                            <td><strong><?= htmlspecialchars($m['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= $m['age'] ?? 'N/A' ?></td>
                            <td><span class="badge badge-red"><?= $m['blood_group'] ?></span></td>
                            <td><?= $m['room_number'] ?: 'Unassigned' ?></td>
                            <td><?= $m['ordained_date'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this monk?')">
                                    <input type="hidden" name="action" value="delete_monk">
                                    <input type="hidden" name="monk_id" value="<?= $m['id'] ?>">
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($monks)) echo '<tr><td colspan="8" style="text-align:center">No monks registered yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="8">Error loading data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Monk Modal -->
    <div class="modal-overlay" id="addMonkModal">
        <div class="modal">
            <div class="modal-header"><h3>Add New Monk</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_monk">
                    <div class="grid-2">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" value="monk123" placeholder="Default: monk123"></div>
                        <div class="form-group"><label>Age</label><input type="number" name="age" class="form-control" min="1" max="150"></div>
                        <div class="form-group"><label>Blood Group</label>
                            <select name="blood_group" class="form-control"><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select>
                        </div>
                        <div class="form-group"><label>Ordained Date</label><input type="date" name="ordained_date" class="form-control"></div>
                        <div class="form-group"><label>Room</label>
                            <select name="room_id" class="form-control"><option value="">-- No Room --</option>
                            <?php try { $rooms = $db->fetchAll("SELECT * FROM rooms WHERE status = 'available'"); foreach ($rooms as $r) echo "<option value='{$r['id']}'>{$r['room_number']} ({$r['room_type']})</option>"; } catch(Exception $e) {} ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label>Emergency Contact</label><input type="text" name="emergency_contact" class="form-control"></div>
                    <div class="form-group"><label>Emergency Phone</label><input type="text" name="emergency_phone" class="form-control"></div>
                    <div class="form-group"><label>Medical Conditions</label><textarea name="medical_conditions" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add Monk</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'doctors'): ?>
    <!-- Doctor Management -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>👨‍⚕️ Doctor Management</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addDoctorModal').classList.add('active')">+ Add Doctor</button>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Qualifications</th><th>Experience</th><th>License</th><th>Available Days</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                try {
                    $doctors = $db->fetchAll("SELECT d.*, u.full_name, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.status = 'active' ORDER BY u.full_name");
                    foreach ($doctors as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['doctor_id']) ?></td>
                            <td><strong><?= htmlspecialchars($d['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($d['specialization']) ?></td>
                            <td><?= htmlspecialchars($d['qualifications'] ?? '') ?></td>
                            <td><?= $d['experience_years'] ?> yrs</td>
                            <td><?= htmlspecialchars($d['license_number']) ?></td>
                            <td><?= ucwords(str_replace(',', ', ', $d['available_days'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this doctor?')">
                                    <input type="hidden" name="action" value="delete_doctor">
                                    <input type="hidden" name="doctor_id" value="<?= $d['id'] ?>">
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($doctors)) echo '<tr><td colspan="8" style="text-align:center">No doctors registered yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="8">Error loading data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal-overlay" id="addDoctorModal">
        <div class="modal">
            <div class="modal-header"><h3>Add New Doctor</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_doctor">
                    <div class="grid-2">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" value="doctor123"></div>
                        <div class="form-group"><label>Specialization *</label><input type="text" name="specialization" class="form-control" required></div>
                        <div class="form-group"><label>Qualifications</label><input type="text" name="qualifications" class="form-control"></div>
                        <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" class="form-control" min="0"></div>
                        <div class="form-group"><label>License Number</label><input type="text" name="license_number" class="form-control"></div>
                        <div class="form-group"><label>Available From</label><input type="time" name="availability_start" class="form-control" value="09:00"></div>
                        <div class="form-group"><label>Available Until</label><input type="time" name="availability_end" class="form-control" value="17:00"></div>
                    </div>
                    <div class="form-group">
                        <label>Available Days *</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day): ?>
                                <label style="display:flex;align-items:center;gap:0.25rem;font-weight:normal;"><input type="checkbox" name="available_days[]" value="<?= $day ?>"> <?= ucfirst($day) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add Doctor</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'rooms'): ?>
    <!-- Room Management -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>🏠 Room Management</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addRoomModal').classList.add('active')">+ Add Room</button>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Room #</th><th>Type</th><th>Building</th><th>Floor</th><th>Capacity</th><th>Occupancy</th><th>Facilities</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                try {
                    $rooms = $db->fetchAll("SELECT * FROM rooms ORDER BY building, room_number");
                    foreach ($rooms as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
                            <td><?= ucfirst($r['room_type']) ?></td>
                            <td><?= htmlspecialchars($r['building']) ?></td>
                            <td>Floor <?= $r['floor'] ?></td>
                            <td><?= $r['capacity'] ?></td>
                            <td><?= $r['current_occupancy'] ?>/<?= $r['capacity'] ?></td>
                            <td><?= htmlspecialchars(substr($r['facilities'], 0, 30)) ?></td>
                            <td><span class="badge badge-<?= $r['status'] === 'available' ? 'green' : ($r['status'] === 'occupied' ? 'yellow' : 'red') ?>"><?= ucfirst($r['status']) ?></span></td>
                        </tr>
                    <?php endforeach;
                    if (empty($rooms)) echo '<tr><td colspan="8" style="text-align:center">No rooms added yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="8">Error loading rooms</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal-overlay" id="addRoomModal">
        <div class="modal">
            <div class="modal-header"><h3>Add New Room</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    <div class="grid-2">
                        <div class="form-group"><label>Room Number *</label><input type="text" name="room_number" class="form-control" required></div>
                        <div class="form-group"><label>Type *</label><select name="room_type" class="form-control"><option value="single">Single</option><option value="shared">Shared</option><option value="dormitory">Dormitory</option></select></div>
                        <div class="form-group"><label>Building</label><input type="text" name="building" class="form-control"></div>
                        <div class="form-group"><label>Floor</label><input type="number" name="floor" class="form-control" min="1"></div>
                        <div class="form-group"><label>Capacity</label><input type="number" name="capacity" class="form-control" value="1" min="1"></div>
                    </div>
                    <div class="form-group"><label>Facilities</label><textarea name="facilities" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add Room</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'categories'): ?>
    <!-- Donation Categories -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>📂 Donation Categories</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addCategoryModal').classList.add('active')">+ Add Category</button>
    </div>
    
    <div class="stats-row">
        <?php
        try {
            $categories = $db->fetchAll("SELECT * FROM donation_categories WHERE status = 'active' ORDER BY priority DESC, name");
            foreach ($categories as $c):
                $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
                $color = $progress >= 75 ? 'green' : ($progress >= 40 ? 'blue' : 'orange');
        ?>
            <div class="card" style="margin: 0;">
                <h3><?= htmlspecialchars($c['name']) ?></h3>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.75rem;"><?= htmlspecialchars($c['description']) ?></p>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem;">
                    <span>Rs. <?= number_format($c['current_amount'], 2) ?></span>
                    <span>Rs. <?= number_format($c['target_amount'], 2) ?></span>
                </div>
                <div class="progress-bar"><div class="progress-fill <?= $color ?>" style="width: <?= $progress ?>%"></div></div>
                <div style="text-align: center; margin-top: 0.5rem; font-size: 0.8rem; color: #64748b;">
                    <?= number_format($progress, 1) ?>% of target | Monthly: Rs. <?= number_format($c['monthly_target'], 2) ?>
                </div>
                <div style="margin-top: 0.5rem;"><span class="badge badge-<?= $c['priority'] === 'high' ? 'red' : ($c['priority'] === 'medium' ? 'yellow' : 'blue') ?>"><?= ucfirst($c['priority']) ?> Priority</span></div>
            </div>
        <?php endforeach;
        } catch(Exception $e) { echo '<div class="alert alert-error">Error loading categories</div>'; }
        ?>
    </div>
    
    <div class="modal-overlay" id="addCategoryModal">
        <div class="modal">
            <div class="modal-header"><h3>Add Donation Category</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group"><label>Category Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <div class="grid-2">
                        <div class="form-group"><label>Target Amount (Rs.)</label><input type="number" name="target_amount" class="form-control" step="0.01" min="0" required></div>
                        <div class="form-group"><label>Monthly Target (Rs.)</label><input type="number" name="monthly_target" class="form-control" step="0.01" min="0"></div>
                    </div>
                    <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="high">High</option><option value="medium" selected>Medium</option><option value="low">Low</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add Category</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'donations'): ?>
    <!-- Donations Management -->
    <h2 style="margin-bottom: 1.5rem;">💰 Donation Management</h2>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Donation ID</th><th>Donor</th><th>Category</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                try {
                    $donations = $db->fetchAll("SELECT d.*, dc.name as category_name, u.full_name as donor_name FROM donations d JOIN donation_categories dc ON d.category_id = dc.id JOIN donators dn ON d.donator_id = dn.id JOIN users u ON dn.user_id = u.id ORDER BY d.created_at DESC");
                    foreach ($donations as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['donation_id']) ?></td>
                            <td><?= htmlspecialchars($d['donor_name']) ?></td>
                            <td><?= htmlspecialchars($d['category_name']) ?></td>
                            <td><strong>Rs. <?= number_format($d['amount'], 2) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $d['donation_method'])) ?></td>
                            <td><span class="badge badge-<?= $d['status'] === 'completed' ? 'green' : ($d['status'] === 'pending' ? 'yellow' : 'red') ?>"><?= ucfirst($d['status']) ?></span></td>
                            <td><?= $d['donation_date'] ?></td>
                            <td>
                                <?php if ($d['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="verify_donation">
                                        <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="notes" value="Verified by admin">
                                        <button class="btn btn-success btn-sm">✓ Verify</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="verify_donation">
                                        <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <input type="hidden" name="notes" value="Rejected by admin">
                                        <button class="btn btn-danger btn-sm">✗ Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-green">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    if (empty($donations)) echo '<tr><td colspan="8" style="text-align:center">No donations yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="8">Error loading donations</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'expenses'): ?>
    <!-- Expense Management -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>💸 Expense Management</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addExpenseModal').classList.add('active')">+ Add Expense</button>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Category</th><th>Amount</th><th>Description</th><th>Vendor</th><th>Method</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                try {
                    $expenses = $db->fetchAll("SELECT e.*, dc.name as category_name FROM expenses e LEFT JOIN donation_categories dc ON e.category_id = dc.id ORDER BY e.created_at DESC");
                    foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['expense_id']) ?></td>
                            <td><?= htmlspecialchars($e['category_name'] ?? 'N/A') ?></td>
                            <td><strong>Rs. <?= number_format($e['amount'], 2) ?></strong></td>
                            <td><?= htmlspecialchars(substr($e['description'], 0, 40)) ?></td>
                            <td><?= htmlspecialchars($e['vendor_name'] ?? '') ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $e['payment_method'])) ?></td>
                            <td><?= $e['expense_date'] ?></td>
                            <td><span class="badge badge-<?= $e['status'] === 'approved' ? 'green' : ($e['status'] === 'pending' ? 'yellow' : ($e['status'] === 'paid' ? 'blue' : 'red')) ?>"><?= ucfirst($e['status']) ?></span></td>
                        </tr>
                    <?php endforeach;
                    if (empty($expenses)) echo '<tr><td colspan="8" style="text-align:center">No expenses recorded yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="8">Error loading expenses</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal-overlay" id="addExpenseModal">
        <div class="modal">
            <div class="modal-header"><h3>Add New Expense</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_expense">
                    <div class="grid-2">
                        <div class="form-group"><label>Category *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php try { $cats = $db->fetchAll("SELECT * FROM donation_categories WHERE status = 'active'"); foreach ($cats as $cat) echo "<option value='{$cat['id']}'>{$cat['name']}</option>"; } catch(Exception $e) {} ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Amount (Rs.) *</label><input type="number" name="amount" class="form-control" step="0.01" min="0" required></div>
                        <div class="form-group"><label>Vendor Name</label><input type="text" name="vendor_name" class="form-control"></div>
                        <div class="form-group"><label>Payment Method *</label>
                            <select name="payment_method" class="form-control"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="check">Check</option><option value="card">Card</option><option value="other">Other</option></select>
                        </div>
                        <div class="form-group"><label>Expense Date *</label><input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    </div>
                    <div class="form-group"><label>Description *</label><textarea name="description" class="form-control" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add Expense</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'appointments'): ?>
    <!-- Appointments -->
    <h2 style="margin-bottom: 1.5rem;">📅 Appointments</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Monk</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                try {
                    $appointments = $db->fetchAll("SELECT a.*, um.full_name as monk_name, ud.full_name as doctor_name FROM appointments a JOIN monks m ON a.monk_id = m.id JOIN users um ON m.user_id = um.id JOIN doctors d ON a.doctor_id = d.id JOIN users ud ON d.user_id = ud.id ORDER BY a.appointment_date DESC");
                    foreach ($appointments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['appointment_id']) ?></td>
                            <td><?= htmlspecialchars($a['monk_name']) ?></td>
                            <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                            <td><?= $a['appointment_date'] ?></td>
                            <td><?= $a['appointment_time'] ?></td>
                            <td><?= htmlspecialchars(substr($a['reason'] ?? 'N/A', 0, 40)) ?></td>
                            <td><span class="badge badge-<?= $a['status'] === 'completed' ? 'green' : ($a['status'] === 'scheduled' ? 'blue' : 'yellow') ?>"><?= ucfirst($a['status']) ?></span></td>
                        </tr>
                    <?php endforeach;
                    if (empty($appointments)) echo '<tr><td colspan="7" style="text-align:center">No appointments</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">No appointments yet</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'medical'): ?>
    <!-- Medical Records -->
    <h2 style="margin-bottom: 1.5rem;">📋 Medical Records</h2>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Record ID</th><th>Monk</th><th>Doctor</th><th>Diagnosis</th><th>Prescription</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                try {
                    $records = $db->fetchAll("SELECT mr.*, um.full_name as monk_name, ud.full_name as doctor_name FROM medical_records mr JOIN monks m ON mr.monk_id = m.id JOIN users um ON m.user_id = um.id JOIN doctors d ON mr.doctor_id = d.id JOIN users ud ON d.user_id = ud.id ORDER BY mr.visit_date DESC");
                    foreach ($records as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['record_id']) ?></td>
                            <td><?= htmlspecialchars($r['monk_name']) ?></td>
                            <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                            <td><?= htmlspecialchars(substr($r['diagnosis'], 0, 50)) ?></td>
                            <td><?= htmlspecialchars(substr($r['prescription'] ?? 'N/A', 0, 40)) ?></td>
                            <td><?= $r['visit_date'] ?></td>
                        </tr>
                    <?php endforeach;
                    if (empty($records)) echo '<tr><td colspan="6" style="text-align:center">No records found</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="6">No records yet</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'users'): ?>
    <!-- User Management -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>👥 User Management</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addUserModal').classList.add('active')">+ Add User</button>
    </div>
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th>Last Login</th></tr></thead>
                <tbody>
                <?php
                try {
                    $users = $db->fetchAll("SELECT * FROM users ORDER BY role, full_name");
                    foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'purple' : ($u['role'] === 'doctor' ? 'blue' : ($u['role'] === 'monk' ? 'green' : 'yellow')) ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></td>
                            <td><span class="badge badge-<?= $u['status'] === 'active' ? 'green' : 'red' ?>"><?= ucfirst($u['status']) ?></span></td>
                            <td><?= $u['last_login'] ?? 'Never' ?></td>
                        </tr>
                    <?php endforeach;
                } catch(Exception $e) { echo '<tr><td colspan="7">Error loading users</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal-overlay" id="addUserModal">
        <div class="modal">
            <div class="modal-header"><h3>Add New User</h3><button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('active')">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div class="grid-2">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="form-group"><label>Password *</label><input type="password" name="password" class="form-control" required></div>
                        <div class="form-group"><label>Role *</label><select name="role" class="form-control"><option value="admin">Admin</option><option value="monk">Monk</option><option value="doctor">Doctor</option><option value="donator">Donator</option></select></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" onclick="this.closest('.modal-overlay').classList.remove('active')" style="background:#e5e7eb;color:#374151;">Cancel</button><button type="submit" class="btn btn-primary">Add User</button></div>
            </form>
        </div>
    </div>

<?php elseif ($page === 'reports'): ?>
    <!-- Reports -->
    <h2 style="margin-bottom: 1.5rem;">📈 Reports & Analytics</h2>
    
    <div class="grid-2">
        <div class="card">
            <h3>💰 Financial Summary</h3>
            <div style="padding: 1rem 0;">
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                    <span>Total Donations</span><strong style="color: #059669;">Rs. <?= number_format($stats['donations_total'], 2) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0;">
                    <span>Total Expenses</span><strong style="color: #dc2626;">Rs. <?= number_format($stats['expenses_total'], 2) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; font-size: 1.1rem;">
                    <span><strong>Net Balance</strong></span><strong style="color: #2563eb;">Rs. <?= number_format($stats['donations_total'] - $stats['expenses_total'], 2) ?></strong>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>📊 System Overview</h3>
            <div style="padding: 1rem 0;">
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Active Monks</span><strong><?= $stats['monks'] ?></strong></div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Active Doctors</span><strong><?= $stats['doctors'] ?></strong></div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Total Donators</span><strong><?= $stats['donators'] ?></strong></div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Pending Donations</span><strong><?= $stats['donations_pending'] ?></strong></div>
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;"><span>Available Rooms</span><strong><?= $stats['rooms_available'] ?></strong></div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h3>📂 Category-wise Report</h3>
        <div class="table-container">
            <table>
                <thead><tr><th>Category</th><th>Target</th><th>Collected</th><th>Expenses</th><th>Remaining</th><th>Progress</th></tr></thead>
                <tbody>
                <?php
                try {
                    $categories = $db->fetchAll("SELECT dc.*, COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.category_id = dc.id), 0) as total_expenses FROM donation_categories dc WHERE dc.status = 'active'");
                    foreach ($categories as $c):
                        $remaining = $c['target_amount'] - $c['current_amount'];
                        $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                            <td>Rs. <?= number_format($c['target_amount'], 2) ?></td>
                            <td style="color: #059669;">Rs. <?= number_format($c['current_amount'], 2) ?></td>
                            <td style="color: #dc2626;">Rs. <?= number_format($c['total_expenses'], 2) ?></td>
                            <td>Rs. <?= number_format(max(0, $remaining), 2) ?></td>
                            <td style="width: 150px;">
                                <div class="progress-bar"><div class="progress-fill <?= $progress >= 75 ? 'green' : ($progress >= 40 ? 'blue' : 'orange') ?>" style="width: <?= $progress ?>%"></div></div>
                                <small><?= number_format($progress, 1) ?>%</small>
                            </td>
                        </tr>
                    <?php endforeach;
                } catch(Exception $e) { echo '<tr><td colspan="6">Error loading categories</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'transparency'): ?>
    <!-- Transparency Dashboard -->
    <h2 style="margin-bottom: 1.5rem;">🔍 Transparency Dashboard</h2>
    
    <div class="alert alert-info">This transparency dashboard is publicly accessible to ensure full accountability for all donations received and expenses incurred.</div>
    
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <h4>Total Donations</h4>
                <div class="number">Rs. <?= number_format($stats['donations_total'], 2) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">💸</div>
            <div class="stat-info">
                <h4>Total Expenses</h4>
                <div class="number">Rs. <?= number_format($stats['expenses_total'], 2) ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">💵</div>
            <div class="stat-info">
                <h4>Remaining Balance</h4>
                <div class="number">Rs. <?= number_format($stats['donations_total'] - $stats['expenses_total'], 2) ?></div>
            </div>
        </div>
    </div>
    
    <?php
    try {
        $categories = $db->fetchAll("SELECT dc.*, COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.category_id = dc.id), 0) as total_expenses FROM donation_categories dc WHERE dc.status = 'active'");
        foreach ($categories as $c):
            $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
    ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3><?= htmlspecialchars($c['name']) ?></h3>
                <span class="badge badge-<?= $c['priority'] === 'high' ? 'red' : ($c['priority'] === 'medium' ? 'yellow' : 'blue') ?>"><?= ucfirst($c['priority']) ?></span>
            </div>
            <p style="color: #64748b; margin-bottom: 1rem;"><?= htmlspecialchars($c['description']) ?></p>
            
            <div class="grid-3" style="margin-bottom: 1rem;">
                <div style="text-align: center;"><div style="font-size: 0.75rem; color: #64748b;">Target</div><div style="font-size: 1.25rem; font-weight: 700;">Rs. <?= number_format($c['target_amount'], 2) ?></div></div>
                <div style="text-align: center;"><div style="font-size: 0.75rem; color: #64748b;">Collected</div><div style="font-size: 1.25rem; font-weight: 700; color: #059669;">Rs. <?= number_format($c['current_amount'], 2) ?></div></div>
                <div style="text-align: center;"><div style="font-size: 0.75rem; color: #64748b;">Spent</div><div style="font-size: 1.25rem; font-weight: 700; color: #dc2626;">Rs. <?= number_format($c['total_expenses'], 2) ?></div></div>
            </div>
            
            <div class="progress-bar" style="height: 12px;"><div class="progress-fill <?= $progress >= 75 ? 'green' : ($progress >= 40 ? 'blue' : 'orange') ?>" style="width: <?= $progress ?>%"></div></div>
            <div style="text-align: center; margin-top: 0.5rem; font-size: 0.85rem; color: #64748b;"><?= number_format($progress, 1) ?>% of target reached</div>
        </div>
    <?php endforeach;
    } catch(Exception $e) { echo '<div class="alert alert-error">Error loading data</div>'; }
    ?>

<?php endif; ?>

</div>

<?php renderFooter(); ?>
