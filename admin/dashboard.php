<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$user = current_user();
$pdo = db();

// Handle quick inline status update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_update_status'])) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? 'Status updated from admin dashboard');
    $validStatuses = get_order_statuses();

    if ($orderId > 0 && in_array($newStatus, $validStatuses, true)) {
        $pdo->beginTransaction();
        try {
            // Update order status
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);

            // Insert into history
            $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                ->execute([$orderId, $newStatus, $notes, $user['id']]);

            // Notify customer
            $stmtOrder = $pdo->prepare('SELECT customer_id, reference FROM orders WHERE id = ?');
            $stmtOrder->execute([$orderId]);
            $ordInfo = $stmtOrder->fetch();

            if ($ordInfo) {
                notify_user(
                    (int)$ordInfo['customer_id'],
                    $orderId,
                    "Order {$ordInfo['reference']} Updated",
                    "Your laundry order is now marked: {$newStatus}."
                );
            }

            audit_log((int)$user['id'], 'Update order status', 'orders', $orderId, "Updated to {$newStatus}");

            $pdo->commit();
            flash('success', "Order #{$orderId} status updated to {$newStatus}.");
            redirect('admin/dashboard.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }
}

// Compute metrics
$metrics = [
    'total_orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'new_orders' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Order Placed'")->fetchColumn(),
    'awaiting_pickup' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pickup Scheduled', 'Pickup Assigned')")->fetchColumn(),
    'being_washed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Laundry Collected', 'Received at Laundry Facility', 'Washing in Progress', 'Drying in Progress', 'Folding and Quality Check')")->fetchColumn(),
    'awaiting_delivery' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Ready for Delivery', 'Out for Delivery')")->fetchColumn(),
    'completed' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn(),
    'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn(),
    // Total revenue received only from paid transactions!
    'revenue_received' => (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn(),
    'total_invoiced' => (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn()
];

$metrics['outstanding_balance'] = max(0.0, $metrics['total_invoiced'] - $metrics['revenue_received']);

// Fetch recent 15 orders with customer info
$stmtOrders = $pdo->query('
    SELECT o.*, u.full_name as customer_name, u.email as customer_email,
           (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id AND status = "Paid") as paid_amount
    FROM orders o 
    JOIN users u ON u.id = o.customer_id 
    ORDER BY o.created_at DESC 
    LIMIT 15
');
$recentOrders = $stmtOrders->fetchAll();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <!-- Dashboard Top Header -->
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ADMIN OPERATIONS CONSOLE</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Operations Dashboard</h1>
                <p class="text-muted" style="margin: 0;">
                    Live oversight of customer pickups, wash cycles, return deliveries, and financial receipts.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/admin/orders.php">Manage All Orders</a>
                <a class="btn btn-accent btn-sm" href="<?= APP_URL ?>/admin/payments.php">Record Payment</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Stat Cards Grid -->
        <div class="stat-cards-grid">
            <div class="stat-card">
                <div class="stat-card-number"><?= $metrics['total_orders'] ?></div>
                <div class="stat-card-label">Total Orders Placed</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--blue);"><?= $metrics['new_orders'] ?></div>
                <div class="stat-card-label">New Orders Awaiting Action</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--warning);"><?= $metrics['awaiting_pickup'] ?></div>
                <div class="stat-card-label">Awaiting Pickup Collection</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: #6366F1;"><?= $metrics['being_washed'] ?></div>
                <div class="stat-card-label">In Washing / Drying Facility</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: #EA580C;"><?= $metrics['awaiting_delivery'] ?></div>
                <div class="stat-card-label">Awaiting / Out for Delivery</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--success);"><?= $metrics['completed'] ?></div>
                <div class="stat-card-label">Completed Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--success); font-size: 1.8rem;">
                    KES <?= number_format($metrics['revenue_received'], 0) ?>
                </div>
                <div class="stat-card-label">Total Verified Revenue Received</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--error); font-size: 1.8rem;">
                    KES <?= number_format($metrics['outstanding_balance'], 0) ?>
                </div>
                <div class="stat-card-label">Outstanding Customer Balances</div>
            </div>
        </div>

        <!-- Recent Orders Table Panel -->
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Recent Laundry Orders</h2>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/orders.php">Full Orders Filter &amp; Search &rarr;</a>
            </div>

            <?php if (empty($recentOrders)): ?>
                <p class="text-muted text-center" style="padding: 30px 0;">No orders currently in the system.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Pickup Date / Slot</th>
                                <th>Total / Paid</th>
                                <th>Status</th>
                                <th>Quick Advance Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ord): ?>
                                <?php 
                                    $paid = (float)$ord['paid_amount'];
                                    $total = (float)$ord['total_amount'];
                                    $pStatus = $paid >= $total ? 'Paid' : ($paid > 0 ? 'Partially Paid' : 'Unpaid');
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/admin/order-details.php?id=<?= $ord['id'] ?>" class="fw-bold" style="color: var(--navy);">
                                            <?= e($ord['reference']) ?>
                                        </a>
                                        <br><small class="text-muted"><?= format_datetime($ord['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= e($ord['customer_name']) ?></strong><br>
                                        <small><a href="tel:<?= e($ord['phone']) ?>"><?= e($ord['phone']) ?></a></small>
                                    </td>
                                    <td>
                                        <?= format_date($ord['pickup_date']) ?><br>
                                        <small class="text-muted"><?= e($ord['pickup_time']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= money($total) ?></strong><br>
                                        <?= payment_badge($pStatus) ?>
                                    </td>
                                    <td>
                                        <?= status_badge($ord['status']) ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/dashboard.php" style="display: flex; gap: 6px; align-items: center; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                            <select name="status" style="padding: 6px 8px; font-size: 0.85rem; width: auto;" aria-label="Change status">
                                                <option value="">-- Advance Status --</option>
                                                <?php foreach (get_order_statuses() as $st): ?>
                                                    <option value="<?= e($st) ?>" <?= $st === $ord['status'] ? 'selected' : '' ?>>
                                                        <?= e($st) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="quick_update_status" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/order-details.php?id=<?= $ord['id'] ?>">
                                            Manage
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