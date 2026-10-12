<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();
$user = current_user();

// Handle Status Advancement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['advance_status'])) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($orderId > 0 && in_array($status, get_order_statuses(), true)) {
        $pdo->beginTransaction();
        try {
            // Update order
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);

            // Insert into history
            $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                ->execute([$orderId, $status, $notes ?: "Advanced to {$status}", $user['id']]);

            // Sync with pickup or delivery tables if applicable
            if ($status === 'Laundry Collected') {
                $pdo->prepare('INSERT INTO pickups (order_id, scheduled_at, collected_at, assigned_to) VALUES (?, NOW(), NOW(), ?) ON DUPLICATE KEY UPDATE collected_at = NOW(), assigned_to = ?')
                    ->execute([$orderId, $user['id'], $user['id']]);
            } elseif ($status === 'Delivered') {
                $pdo->prepare('INSERT INTO deliveries (order_id, scheduled_at, delivered_at, assigned_to) VALUES (?, NOW(), NOW(), ?) ON DUPLICATE KEY UPDATE delivered_at = NOW(), assigned_to = ?')
                    ->execute([$orderId, $user['id'], $user['id']]);
            }

            // Customer notification
            $stmtOrder = $pdo->prepare('SELECT customer_id, reference FROM orders WHERE id = ?');
            $stmtOrder->execute([$orderId]);
            $ordInfo = $stmtOrder->fetch();

            if ($ordInfo) {
                notify_user(
                    (int)$ordInfo['customer_id'],
                    $orderId,
                    "Order {$ordInfo['reference']} Update",
                    "Your laundry order is now marked: {$status}." . ($notes ? " Note: {$notes}" : "")
                );
            }

            audit_log((int)$user['id'], 'Update order status', 'orders', $orderId, "Updated status to {$status}");

            $pdo->commit();
            flash('success', "Order status successfully updated to {$status}.");
            redirect('admin/tracking.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Status update failed: ' . $e->getMessage());
        }
    }
}

// Search and list active orders in tracking pipeline
$search = trim($_GET['search'] ?? '');
$query = "
    SELECT o.*, u.full_name as customer_name,
           (SELECT created_at FROM order_status_history WHERE order_id = o.id ORDER BY created_at DESC LIMIT 1) as last_updated
    FROM orders o 
    JOIN users u ON u.id = o.customer_id
    WHERE o.status NOT IN ('Delivered', 'Cancelled')
";
$params = [];
if (!empty($search)) {
    $query .= ' AND (o.reference LIKE ? OR u.full_name LIKE ? OR o.phone LIKE ?)';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
}
$query .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activeOrders = $stmt->fetchAll();

$pageTitle = 'Laundry Tracking Manager';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">WORKFLOW PIPELINE</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Order Tracking Management</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage orders through the 12 operational stages from collection through washing, drying, folding, to delivery.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Search Toolbar -->
        <div class="filter-toolbar">
            <form method="get" action="<?= APP_URL ?>/admin/tracking.php" style="display: flex; gap: 12px; width: 100%; max-width: 600px;">
                <input type="text" name="search" class="filter-input" placeholder="Quick find active order by ref, customer, phone..." value="<?= e($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Search Active Pipeline</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= APP_URL ?>/admin/tracking.php" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <h2 class="panel-title mb-3">Orders Currently in Process (<?= count($activeOrders) ?>)</h2>

            <?php if (empty($activeOrders)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">No active orders currently in the washing/drying pipeline.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer &amp; Location</th>
                                <th>Current Status</th>
                                <th>Last Status Event</th>
                                <th>Advance to Next Milestone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeOrders as $ao): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/admin/order-details.php?id=<?= $ao['id'] ?>" class="fw-bold" style="color: var(--navy);">
                                            <?= e($ao['reference']) ?>
                                        </a>
                                        <br><small class="text-muted">Total: <?= money($ao['total_amount']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= e($ao['customer_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($ao['pickup_location']) ?> &bull; <?= e($ao['phone']) ?></small>
                                    </td>
                                    <td>
                                        <?= status_badge($ao['status']) ?>
                                    </td>
                                    <td>
                                        <?= format_datetime($ao['last_updated'] ?? $ao['created_at']) ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/tracking.php" style="display: flex; gap: 6px; flex-wrap: wrap; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $ao['id'] ?>">
                                            <select name="status" style="padding: 6px 8px; font-size: 0.85rem; width: auto;" required>
                                                <?php foreach (get_order_statuses() as $st): ?>
                                                    <option value="<?= e($st) ?>" <?= $st === $ao['status'] ? 'selected' : '' ?>>
                                                        <?= e($st) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="notes" placeholder="Note (optional)" style="width: 140px; padding: 6px; font-size: 0.85rem;">
                                            <button type="submit" name="advance_status" class="btn btn-primary btn-sm">Update</button>
                                        </form>
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