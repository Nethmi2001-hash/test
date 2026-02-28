<?php
/**
 * Donator Dashboard - Monastery Healthcare System
 */

require_once __DIR__ . '/../layout.php';

$db = Database::getInstance();
$page = $_GET['page'] ?? 'dashboard';
$userId = $_SESSION['user_id'];

// Get donator profile
$donator = null;
try {
    $donator = $db->fetch("SELECT dn.*, u.full_name, u.email, u.phone FROM donators dn JOIN users u ON dn.user_id = u.id WHERE dn.user_id = ?", [$userId]);
} catch(Exception $e) {}

$donatorId = $donator['id'] ?? null;

// If donator record doesn't exist yet, create one
if (!$donatorId && $userId) {
    try {
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        $donatorId = $db->insert('donators', [
            'user_id' => $userId,
            'donator_id' => 'DON-' . str_pad($userId, 3, '0', STR_PAD_LEFT),
            'address' => '',
            'city' => ''
        ]);
        $donator = $db->fetch("SELECT dn.*, u.full_name, u.email, u.phone FROM donators dn JOIN users u ON dn.user_id = u.id WHERE dn.id = ?", [$donatorId]);
    } catch(Exception $e) {}
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $donatorId) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'make_donation':
                $donationId = 'DN-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $db->insert('donations', [
                    'donation_id' => $donationId,
                    'donator_id' => $donatorId,
                    'category_id' => $_POST['category_id'],
                    'amount' => $_POST['amount'],
                    'donation_method' => $_POST['donation_method'],
                    'donation_date' => date('Y-m-d'),
                    'transaction_reference' => $_POST['transaction_reference'] ?? null,
                    'notes' => $_POST['notes'] ?? '',
                    'status' => 'pending'
                ]);
                setFlash('success', 'Donation submitted! Reference: ' . $donationId . '. Pending verification.');
                break;
                
            case 'update_profile':
                $db->update('donators', [
                    'address' => $_POST['address'],
                    'city' => $_POST['city'] ?? '',
                    'postal_code' => $_POST['postal_code'] ?? '',
                    'country' => $_POST['country'] ?? 'Sri Lanka'
                ], 'id = ?', [$donatorId]);
                $db->update('users', [
                    'full_name' => $_POST['full_name'],
                    'phone' => $_POST['phone']
                ], 'id = ?', [$userId]);
                setFlash('success', 'Profile updated!');
                break;
        }
    } catch(Exception $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
    }
    header("Location: dashboard?page=" . $page);
    exit;
}

renderHeader('Donator Dashboard');
renderSidebar('donator', $page);
renderTopbar(ucfirst(str_replace('-', ' ', $page)));
?>

<div class="main-content">
    <?php renderFlash(); ?>

<?php if ($page === 'dashboard'): ?>
    <!-- Donator Dashboard -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">💝</div>
            <div>
                <h2 style="margin: 0 0 0.25rem 0;"><?= htmlspecialchars($donator['full_name'] ?? 'Donator') ?></h2>
                <p style="color: #64748b; margin: 0;">ID: <?= htmlspecialchars($donator['donator_id'] ?? 'N/A') ?> | <?= htmlspecialchars($donator['city'] ?? '') ?></p>
            </div>
        </div>
    </div>
    
    <div class="stats-row">
        <?php
        try {
            $myDonations = $db->fetch("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM donations WHERE donator_id = ?", [$donatorId]);
            $completedAmt = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE donator_id = ? AND status = 'completed'", [$donatorId])['total'];
            $pendingAmt = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE donator_id = ? AND status = 'pending'", [$donatorId])['total'];
        } catch(Exception $e) { $myDonations = ['count' => 0, 'total' => 0]; $completedAmt = 0; $pendingAmt = 0; }
        ?>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info"><h4>Total Donated</h4><div class="number">Rs. <?= number_format($completedAmt, 2) ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-info"><h4>Pending</h4><div class="number">Rs. <?= number_format($pendingAmt, 2) ?></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">📊</div>
            <div class="stat-info"><h4>Donations Made</h4><div class="number"><?= $myDonations['count'] ?></div></div>
        </div>
    </div>
    
    <!-- Recent Donations -->
    <div class="card">
        <h3>📈 My Recent Donations</h3>
        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Category</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                try {
                    $recent = $db->fetchAll("SELECT d.*, dc.name as category_name FROM donations d JOIN donation_categories dc ON d.category_id = dc.id WHERE d.donator_id = ? ORDER BY d.created_at DESC LIMIT 5", [$donatorId]);
                    foreach ($recent as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['donation_id']) ?></td>
                            <td><?= htmlspecialchars($d['category_name']) ?></td>
                            <td><strong>Rs. <?= number_format($d['amount'], 2) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $d['donation_method'])) ?></td>
                            <td><span class="badge badge-<?= $d['status'] === 'completed' ? 'green' : ($d['status'] === 'pending' ? 'yellow' : 'red') ?>"><?= ucfirst($d['status']) ?></span></td>
                            <td><?= $d['donation_date'] ?></td>
                        </tr>
                    <?php endforeach;
                    if (empty($recent)) echo '<tr><td colspan="6" style="text-align:center">No donations yet. <a href="dashboard?page=make-donation">Make your first donation!</a></td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="6">No data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'make-donation'): ?>
    <!-- Make Donation -->
    <h2 style="margin-bottom: 1.5rem;">💰 Make a Donation</h2>
    
    <!-- Category Cards -->
    <div class="stats-row" style="margin-bottom: 2rem;">
        <?php
        try {
            $categories = $db->fetchAll("SELECT * FROM donation_categories WHERE status = 'active' ORDER BY priority DESC");
            foreach ($categories as $c):
                $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
        ?>
            <div class="card" style="margin: 0; cursor: pointer;" onclick="document.getElementById('category_select').value='<?= $c['id'] ?>'; document.getElementById('category_select').dispatchEvent(new Event('change'));">
                <h4 style="margin: 0 0 0.25rem 0;"><?= htmlspecialchars($c['name']) ?></h4>
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem;"><?= htmlspecialchars(substr($c['description'], 0, 60)) ?></p>
                <div class="progress-bar"><div class="progress-fill <?= $progress >= 75 ? 'green' : 'blue' ?>" style="width: <?= $progress ?>%"></div></div>
                <small style="color: #64748b;"><?= number_format($progress, 0) ?>% funded</small>
            </div>
        <?php endforeach;
        } catch(Exception $e) {}
        ?>
    </div>
    
    <div class="card">
        <h3>💰 Donation Form</h3>
        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="make_donation">
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Donation Category *</label>
                    <select name="category_id" id="category_select" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php
                        try {
                            foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (Target: Rs. <?= number_format($c['target_amount'], 2) ?>)</option>
                            <?php endforeach;
                        } catch(Exception $e) {}
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount (Rs.) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="1" required placeholder="Enter donation amount">
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="donation_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online Payment</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference Number</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="Bank/payment reference">
                </div>
            </div>
            
            <div class="form-group">
                <label>Notes (optional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Any notes about this donation..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">💰 Submit Donation</button>
        </form>
    </div>

<?php elseif ($page === 'my-donations'): ?>
    <!-- My Donations History -->
    <h2 style="margin-bottom: 1.5rem;">📋 My Donation History</h2>
    
    <div class="stats-row" style="margin-bottom: 1.5rem;">
        <?php
        try {
            $dSummary = $db->fetch("SELECT COUNT(*) as total_count, 
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as completed_total, 
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_total 
                FROM donations WHERE donator_id = ?", [$donatorId]);
        } catch(Exception $e) { $dSummary = ['total_count' => 0, 'completed_total' => 0, 'pending_total' => 0]; }
        ?>
        <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><h4>Completed</h4><div class="number">Rs. <?= number_format($dSummary['completed_total'], 2) ?></div></div></div>
        <div class="stat-card"><div class="stat-icon yellow">⏳</div><div class="stat-info"><h4>Pending</h4><div class="number">Rs. <?= number_format($dSummary['pending_total'], 2) ?></div></div></div>
        <div class="stat-card"><div class="stat-icon blue">📊</div><div class="stat-info"><h4>Total Donations</h4><div class="number"><?= $dSummary['total_count'] ?></div></div></div>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table>
                <thead><tr><th>Donation ID</th><th>Category</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                try {
                    $allDonations = $db->fetchAll("SELECT d.*, dc.name as category_name FROM donations d JOIN donation_categories dc ON d.category_id = dc.id WHERE d.donator_id = ? ORDER BY d.donation_date DESC", [$donatorId]);
                    foreach ($allDonations as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['donation_id']) ?></td>
                            <td><?= htmlspecialchars($d['category_name']) ?></td>
                            <td><strong>Rs. <?= number_format($d['amount'], 2) ?></strong></td>
                            <td><?= ucfirst(str_replace('_', ' ', $d['donation_method'])) ?></td>
                            <td><?= htmlspecialchars($d['transaction_reference'] ?? 'N/A') ?></td>
                            <td><span class="badge badge-<?= $d['status'] === 'completed' ? 'green' : ($d['status'] === 'pending' ? 'yellow' : 'red') ?>"><?= ucfirst($d['status']) ?></span></td>
                            <td><?= $d['donation_date'] ?></td>
                        </tr>
                    <?php endforeach;
                    if (empty($allDonations)) echo '<tr><td colspan="7" style="text-align:center">No donations yet</td></tr>';
                } catch(Exception $e) { echo '<tr><td colspan="7">Error loading data</td></tr>'; }
                ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'categories'): ?>
    <!-- Donation Categories View -->
    <h2 style="margin-bottom: 1.5rem;">📂 Donation Categories</h2>
    
    <?php
    try {
        $categories = $db->fetchAll("SELECT * FROM donation_categories WHERE status = 'active' ORDER BY priority DESC");
        foreach ($categories as $c):
            $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
    ?>
        <div class="card" style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3><?= htmlspecialchars($c['name']) ?></h3>
                <span class="badge badge-<?= $c['priority'] === 'high' ? 'red' : ($c['priority'] === 'medium' ? 'yellow' : 'blue') ?>"><?= ucfirst($c['priority']) ?></span>
            </div>
            <p style="color: #64748b; margin: 0.5rem 0 1rem;"><?= htmlspecialchars($c['description']) ?></p>
            
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem;">
                <span>Rs. <?= number_format($c['current_amount'], 2) ?> raised</span>
                <span>Target: Rs. <?= number_format($c['target_amount'], 2) ?></span>
            </div>
            <div class="progress-bar" style="height: 10px;"><div class="progress-fill <?= $progress >= 75 ? 'green' : ($progress >= 40 ? 'blue' : 'orange') ?>" style="width: <?= $progress ?>%"></div></div>
            <div style="display: flex; justify-content: space-between; margin-top: 0.75rem;">
                <small style="color: #64748b;"><?= number_format($progress, 1) ?>% funded</small>
                <a href="dashboard?page=make-donation" class="btn btn-primary btn-sm">Donate</a>
            </div>
        </div>
    <?php endforeach;
    } catch(Exception $e) { echo '<div class="alert alert-error">Error loading categories</div>'; }
    ?>

<?php elseif ($page === 'transparency'): ?>
    <!-- Transparency -->
    <h2 style="margin-bottom: 1.5rem;">🔍 Transparency Dashboard</h2>
    <div class="alert alert-info">Full transparency of how your donations are utilized.</div>
    
    <?php
    try {
        $totalDonations = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status = 'completed'")['total'];
        $totalExpenses = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE status = 'approved'")['total'];
    ?>
        <div class="stats-row">
            <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><h4>Total Donations</h4><div class="number">Rs. <?= number_format($totalDonations, 2) ?></div></div></div>
            <div class="stat-card"><div class="stat-icon red">💸</div><div class="stat-info"><h4>Total Expenses</h4><div class="number">Rs. <?= number_format($totalExpenses, 2) ?></div></div></div>
            <div class="stat-card"><div class="stat-icon blue">💵</div><div class="stat-info"><h4>Balance</h4><div class="number">Rs. <?= number_format($totalDonations - $totalExpenses, 2) ?></div></div></div>
        </div>
        
        <?php
        $categories = $db->fetchAll("SELECT dc.*, 
            COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.category_id = dc.id AND e.status = 'approved'), 0) as total_expenses 
            FROM donation_categories dc WHERE dc.status = 'active' ORDER BY dc.name");
        foreach ($categories as $c):
            $progress = $c['target_amount'] > 0 ? min(100, ($c['current_amount'] / $c['target_amount']) * 100) : 0;
        ?>
            <div class="card">
                <h3><?= htmlspecialchars($c['name']) ?></h3>
                <div class="grid-3" style="margin: 1rem 0;">
                    <div style="text-align:center;"><small style="color:#64748b;">Target</small><div style="font-weight:700;">Rs. <?= number_format($c['target_amount'], 2) ?></div></div>
                    <div style="text-align:center;"><small style="color:#64748b;">Collected</small><div style="font-weight:700;color:#059669;">Rs. <?= number_format($c['current_amount'], 2) ?></div></div>
                    <div style="text-align:center;"><small style="color:#64748b;">Spent</small><div style="font-weight:700;color:#dc2626;">Rs. <?= number_format($c['total_expenses'], 2) ?></div></div>
                </div>
                <div class="progress-bar"><div class="progress-fill <?= $progress >= 75 ? 'green' : 'blue' ?>" style="width: <?= $progress ?>%"></div></div>
                <small style="color:#64748b;"><?= number_format($progress, 1) ?>% of target</small>
            </div>
        <?php endforeach;
    } catch(Exception $e) { echo '<div class="alert alert-error">Error loading data</div>'; }
    ?>

<?php endif; ?>

</div>

<?php renderFooter(); ?>
