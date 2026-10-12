<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = db();
$currentUser = current_user();

// Handle Create / Toggle User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['create_staff'])) {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = sanitize_phone($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'staff';
        $password = $_POST['password'] ?? '';

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            flash('error', 'Please fill all required fields properly. Password must be at least 8 characters.');
        } else {
            // Check duplicate email
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                flash('error', 'An account with that email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$name, $email, $phone, $hash, $role]);
                audit_log((int)$currentUser['id'], 'Create staff account', 'users', (int)$pdo->lastInsertId(), "Created {$role} account for {$email}");
                flash('success', "New {$role} account created successfully.");
                redirect('admin/users.php');
            }
        }
    } elseif (isset($_POST['toggle_active'])) {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $currentStatus = (int)($_POST['current_status'] ?? 0);
        $newStatus = $currentStatus === 1 ? 0 : 1;

        if ($targetId === (int)$currentUser['id']) {
            flash('error', 'You cannot deactivate your own administrative account.');
        } else {
            $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$newStatus, $targetId]);
            audit_log((int)$currentUser['id'], 'Toggle user status', 'users', $targetId, "Changed active state to {$newStatus}");
            flash('success', 'User account status updated.');
            redirect('admin/users.php');
        }
    }
}

// Fetch all admin and staff users
$staffList = $pdo->query("
    SELECT * FROM users 
    WHERE role IN ('admin', 'staff') 
    ORDER BY role ASC, created_at DESC
")->fetchAll();

$pageTitle = 'Manage Staff & Roles';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ACCESS CONTROL</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Staff &amp; Admin Users</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage team access credentials, operational roles, and active accounts for Stainless Laundry Services.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 28px; align-items: start;">
            <!-- Staff Accounts List -->
            <div class="panel">
                <h2 class="panel-title mb-3">Authorized Staff Team</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name &amp; Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffList as $st): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($st['full_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($st['email']) ?></small>
                                    </td>
                                    <td><?= e($st['phone']) ?></td>
                                    <td>
                                        <span class="status-pill <?= $st['role'] === 'admin' ? 'badge-new' : 'badge-facility' ?>">
                                            <?= strtoupper($st['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($st['is_active']): ?>
                                            <span class="status-pill badge-delivered">Active</span>
                                        <?php else: ?>
                                            <span class="status-pill badge-cancelled">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$st['id'] !== (int)$currentUser['id']): ?>
                                            <form method="post" action="<?= APP_URL ?>/admin/users.php" style="margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= $st['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $st['is_active'] ?>">
                                                <button type="submit" name="toggle_active" class="btn btn-secondary btn-sm">
                                                    <?= $st['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <small class="text-muted">Current user</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create New Staff Account -->
            <div class="panel">
                <h2 class="panel-title mb-3">Add Staff Member</h2>
                <form method="post" action="<?= APP_URL ?>/admin/users.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="full_name">Staff Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="e.g. Kelvin Mutua">
                    </div>

                    <div class="form-group">
                        <label for="email">Staff Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="kelvin@stainlesslaundry.co.ke">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="text" id="phone" name="phone" required placeholder="0101476174">
                    </div>

                    <div class="form-group">
                        <label for="role">Assigned Role *</label>
                        <select id="role" name="role" required>
                            <option value="staff">Staff (Logistics &amp; Tracking)</option>
                            <option value="admin">Administrator (Full Access)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Initial Password (Min 8 Chars) *</label>
                        <input type="password" id="password" name="password" required minlength="8" placeholder="Create strong temporary password">
                    </div>

                    <button type="submit" name="create_staff" class="btn btn-primary btn-block">
                        Create Staff Member
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>