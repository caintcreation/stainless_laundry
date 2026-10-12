<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();
$user = current_user();

// Handle Pickup Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['mark_collected'])) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $internalNotes = trim($_POST['internal_notes'] ?? '');
        $customerNotes = trim($_POST['customer_notes'] ?? 'Laundry collected from customer location.');

        if ($orderId > 0) {
            $pdo->beginTransaction();
            try {
                // Update order status
                $pdo->prepare("UPDATE orders SET status = 'Laundry Collected' WHERE id = ?")->execute([$orderId]);

                // Update pickups record
                $pdo->prepare('
                    INSERT INTO pickups (order_id, scheduled_at, collected_at, assigned_to, internal_notes, customer_notes)
                    VALUES (?, NOW(), NOW(), ?, ?, ?)
                    ON DUPLICATE KEY UPDATE collected_at = NOW(), assigned_to = ?, internal_notes = ?, customer_notes = ?
                ')->execute([$orderId, $user['id'], $internalNotes, $customerNotes, $user['id'], $internalNotes, $customerNotes]);

                // Insert into status history
                $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                    ->execute([$orderId, 'Laundry Collected', $customerNotes, $user['id']]);

                // Notify customer
                $stmtO = $pdo->prepare('SELECT customer_id, reference FROM orders WHERE id = ?');
                $stmtO->execute([$orderId]);
                $ord = $stmtO->fetch();
                if ($ord) {
                    notify_user(
                        (int)$ord['customer_id'],
                        $orderId,
                        "Laundry Collected ({$ord['reference']})",
                        "Our team has collected your laundry basket/items. They are on their way to our Ndagani washing facility."
                    );
                }

                $pdo->commit();
                flash('success', "Order #{$orderId} marked as Laundry Collected.");
                redirect('admin/pickups.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash('error', 'Failed to update pickup: ' . $e->getMessage());
            }
        }
    } elseif (isset($_POST['assign_staff'])) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        if ($orderId > 0 && $assignedTo > 0) {
            $pdo->prepare('
                INSERT INTO pickups (order_id, scheduled_at, assigned_to)
                VALUES (?, NOW(), ?)
                ON DUPLICATE KEY UPDATE assigned_to = ?
            ')->execute([$orderId, $assignedTo, $assignedTo]);

            $pdo->prepare("UPDATE orders SET status = 'Pickup Assigned' WHERE id = ? AND status = 'Order Placed'")->execute([$orderId]);
            flash('success', "Pickup assigned to staff member.");
            redirect('admin/pickups.php');
        }
    }
}

// Fetch pickups awaiting collection
$stmtPickups = $pdo->query("
    SELECT o.*, u.full_name as customer_name, p.collected_at, p.assigned_to, p.internal_notes,
           staff.full_name as assigned_staff_name
    FROM orders o 
    JOIN users u ON u.id = o.customer_id
    LEFT JOIN pickups p ON p.order_id = o.id
    LEFT JOIN users staff ON staff.id = p.assigned_to
    WHERE o.status IN ('Order Placed', 'Pickup Scheduled', 'Pickup Assigned')
    ORDER BY o.pickup_date ASC, o.created_at ASC
");
$pendingPickups = $stmtPickups->fetchAll();

// Fetch recently collected pickups
$stmtCollected = $pdo->query("
    SELECT o.*, u.full_name as customer_name, p.collected_at, staff.full_name as collected_by_name
    FROM orders o 
    JOIN users u ON u.id = o.customer_id
    JOIN pickups p ON p.order_id = o.id
    LEFT JOIN users staff ON staff.id = p.assigned_to
    WHERE p.collected_at IS NOT NULL
    ORDER BY p.collected_at DESC
    LIMIT 10
");
$recentCollected = $stmtCollected->fetchAll();

$staffMembers = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'staff') AND is_active = 1")->fetchAll();

$today = date('Y-m-d');
$pageTitle = 'Manage Laundry Pickups';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">COLLECTION DISPATCH</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Pickup Management</h1>
                <p class="text-muted" style="margin: 0;">
                    Assign staff riders and record physical collection of laundry baskets from Ndagani and Chuka hostels.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Pending Pickups Panel -->
        <div class="panel">
            <h2 class="panel-title mb-3">Pending Doorstep Collections (<?= count($pendingPickups) ?>)</h2>

            <?php if (empty($pendingPickups)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">No pending pickups awaiting collection right now.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer &amp; Phone</th>
                                <th>Scheduled Date &amp; Slot</th>
                                <th>Hostel / Location</th>
                                <th>Assigned Staff</th>
                                <th>Confirm Collection</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPickups as $pk): ?>
                                <?php $isOverdue = $pk['pickup_date'] < $today; ?>
                                <tr style="<?= $isOverdue ? 'background-color: #FFF5F5;' : '' ?>">
                                    <td>
                                        <a href="<?= APP_URL ?>/admin/order-details.php?id=<?= $pk['id'] ?>" class="fw-bold" style="color: var(--navy);">
                                            <?= e($pk['reference']) ?>
                                        </a>
                                        <?php if ($isOverdue): ?>
                                            <br><span class="status-pill badge-cancelled" style="font-size: 0.7rem;">Overdue</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e($pk['customer_name']) ?></strong><br>
                                        <a href="tel:<?= e($pk['phone']) ?>"><?= e($pk['phone']) ?></a>
                                    </td>
                                    <td>
                                        <strong><?= format_date($pk['pickup_date']) ?></strong><br>
                                        <small class="text-muted"><?= e($pk['pickup_time']) ?></small>
                                    </td>
                                    <td>
                                        <?= e($pk['pickup_location']) ?>
                                        <?php if (!empty($pk['instructions'])): ?>
                                            <br><small class="text-muted"><?= e($pk['instructions']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/pickups.php" style="display: flex; gap: 4px; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $pk['id'] ?>">
                                            <select name="assigned_to" style="padding: 4px 6px; font-size: 0.8rem; width: auto;" onchange="this.form.submit()">
                                                <option value="">-- Assign Rider --</option>
                                                <?php foreach ($staffMembers as $sm): ?>
                                                    <option value="<?= $sm['id'] ?>" <?= $pk['assigned_to'] == $sm['id'] ? 'selected' : '' ?>>
                                                        <?= e($sm['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="assign_staff" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/pickups.php" style="margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $pk['id'] ?>">
                                            <button type="submit" name="mark_collected" class="btn btn-primary btn-sm" onclick="return confirm('Confirm that laundry for <?= e($pk['reference']) ?> has been physically collected?')">
                                                &#10003; Mark Collected
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recently Collected History -->
        <div class="panel">
            <h2 class="panel-title mb-3">Recently Collected Laundry</h2>
            <?php if (empty($recentCollected)): ?>
                <p class="text-muted">No recently collected pickups to display.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer</th>
                                <th>Pickup Location</th>
                                <th>Collection Time</th>
                                <th>Collected By Staff</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCollected as $rc): ?>
                                <tr>
                                    <td><strong><?= e($rc['reference']) ?></strong></td>
                                    <td><?= e($rc['customer_name']) ?> (<?= e($rc['phone']) ?>)</td>
                                    <td><?= e($rc['pickup_location']) ?></td>
                                    <td><?= format_datetime($rc['collected_at']) ?></td>
                                    <td><?= e($rc['collected_by_name'] ?? 'Staff') ?></td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/order-details.php?id=<?= $rc['id'] ?>">
                                            View Order
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>