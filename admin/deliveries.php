<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();
$user = current_user();

// Handle Delivery State Changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['mark_out_for_delivery'])) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $assignedTo = (int)($_POST['assigned_to'] ?? $user['id']);

        if ($orderId > 0) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE orders SET status = 'Out for Delivery' WHERE id = ?")->execute([$orderId]);

                $pdo->prepare('
                    INSERT INTO deliveries (order_id, scheduled_at, assigned_to)
                    VALUES (?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE scheduled_at = NOW(), assigned_to = ?
                ')->execute([$orderId, $assignedTo, $assignedTo]);

                $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                    ->execute([$orderId, 'Out for Delivery', 'Clean laundry is out with rider for doorstep delivery.', $user['id']]);

                $stmtO = $pdo->prepare('SELECT customer_id, reference FROM orders WHERE id = ?');
                $stmtO->execute([$orderId]);
                $ord = $stmtO->fetch();
                if ($ord) {
                    notify_user(
                        (int)$ord['customer_id'],
                        $orderId,
                        "Laundry Out for Delivery ({$ord['reference']})",
                        "Your clean, folded clothes are on the way to your delivery destination!"
                    );
                }

                $pdo->commit();
                flash('success', "Order #{$orderId} is now Out for Delivery.");
                redirect('admin/deliveries.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash('error', 'Update failed: ' . $e->getMessage());
            }
        }
    } elseif (isset($_POST['mark_delivered'])) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $customerNotes = trim($_POST['customer_notes'] ?? 'Delivered cleanly to customer.');

        if ($orderId > 0) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ?")->execute([$orderId]);

                $pdo->prepare('
                    INSERT INTO deliveries (order_id, scheduled_at, delivered_at, assigned_to, customer_notes)
                    VALUES (?, NOW(), NOW(), ?, ?)
                    ON DUPLICATE KEY UPDATE delivered_at = NOW(), customer_notes = ?
                ')->execute([$orderId, $user['id'], $customerNotes, $customerNotes]);

                $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                    ->execute([$orderId, 'Delivered', $customerNotes, $user['id']]);

                $stmtO = $pdo->prepare('SELECT customer_id, reference FROM orders WHERE id = ?');
                $stmtO->execute([$orderId]);
                $ord = $stmtO->fetch();
                if ($ord) {
                    notify_user(
                        (int)$ord['customer_id'],
                        $orderId,
                        "Order Delivered ({$ord['reference']})",
                        "Your clean laundry has been delivered! Thank you for choosing Stainless Laundry Services."
                    );
                }

                $pdo->commit();
                flash('success', "Order #{$orderId} confirmed as Delivered.");
                redirect('admin/deliveries.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                flash('error', 'Delivery update failed: ' . $e->getMessage());
            }
        }
    }
}

// Fetch orders ready or out for delivery
$stmtAwaiting = $pdo->query("
    SELECT o.*, u.full_name as customer_name, d.delivered_at, d.assigned_to,
           staff.full_name as assigned_staff_name
    FROM orders o 
    JOIN users u ON u.id = o.customer_id
    LEFT JOIN deliveries d ON d.order_id = o.id
    LEFT JOIN users staff ON staff.id = d.assigned_to
    WHERE o.status IN ('Ready for Delivery', 'Out for Delivery')
    ORDER BY o.created_at ASC
");
$awaitingDeliveries = $stmtAwaiting->fetchAll();

// Fetch recently completed deliveries
$stmtCompleted = $pdo->query("
    SELECT o.*, u.full_name as customer_name, d.delivered_at, staff.full_name as delivered_by_name
    FROM orders o 
    JOIN users u ON u.id = o.customer_id
    JOIN deliveries d ON d.order_id = o.id
    LEFT JOIN users staff ON staff.id = d.assigned_to
    WHERE d.delivered_at IS NOT NULL
    ORDER BY d.delivered_at DESC
    LIMIT 12
");
$recentCompleted = $stmtCompleted->fetchAll();

$staffMembers = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'staff') AND is_active = 1")->fetchAll();

$pageTitle = 'Manage Deliveries';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">LOGISTICS DISPATCH</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Return Delivery Management</h1>
                <p class="text-muted" style="margin: 0;">
                    Dispatch clean laundry back to customers around Ndagani and confirm successful doorstep handover.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Active Deliveries Queue -->
        <div class="panel">
            <h2 class="panel-title mb-3">Deliveries in Transit &amp; Ready for Handover (<?= count($awaitingDeliveries) ?>)</h2>

            <?php if (empty($awaitingDeliveries)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">No orders currently awaiting return delivery.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer &amp; Phone</th>
                                <th>Delivery Destination</th>
                                <th>Current Status</th>
                                <th>Dispatch / Confirm Handover</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($awaitingDeliveries as $del): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/admin/order-details.php?id=<?= $del['id'] ?>" class="fw-bold" style="color: var(--navy);">
                                            <?= e($del['reference']) ?>
                                        </a>
                                        <br><small class="text-muted">Total: <?= money($del['total_amount']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= e($del['customer_name']) ?></strong><br>
                                        <a href="tel:<?= e($del['phone']) ?>"><?= e($del['phone']) ?></a>
                                    </td>
                                    <td>
                                        <?= e($del['delivery_location']) ?>
                                    </td>
                                    <td>
                                        <?= status_badge($del['status']) ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <?php if ($del['status'] === 'Ready for Delivery'): ?>
                                                <form method="post" action="<?= APP_URL ?>/admin/deliveries.php" style="margin: 0;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="order_id" value="<?= $del['id'] ?>">
                                                    <button type="submit" name="mark_out_for_delivery" class="btn btn-primary btn-sm">
                                                        &rarr; Out for Delivery
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="post" action="<?= APP_URL ?>/admin/deliveries.php" style="margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="order_id" value="<?= $del['id'] ?>">
                                                <button type="submit" name="mark_delivered" class="btn btn-accent btn-sm" onclick="return confirm('Confirm customer has received order <?= e($del['reference']) ?>?')">
                                                    &#10003; Confirm Delivered
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Completed Deliveries Panel -->
        <div class="panel">
            <h2 class="panel-title mb-3">Completed Deliveries History</h2>
            <?php if (empty($recentCompleted)): ?>
                <p class="text-muted">No completed deliveries yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer</th>
                                <th>Delivered Destination</th>
                                <th>Delivery Timestamp</th>
                                <th>Delivered By Staff</th>
                                <th>Order Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCompleted as $rc): ?>
                                <tr>
                                    <td><strong><?= e($rc['reference']) ?></strong></td>
                                    <td><?= e($rc['customer_name']) ?> (<?= e($rc['phone']) ?>)</td>
                                    <td><?= e($rc['delivery_location']) ?></td>
                                    <td><?= format_datetime($rc['delivered_at']) ?></td>
                                    <td><?= e($rc['delivered_by_name'] ?? 'Staff') ?></td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/order-details.php?id=<?= $rc['id'] ?>">
                                            View
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