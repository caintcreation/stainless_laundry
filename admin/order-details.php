<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$user = current_user();
$orderId = (int)($_GET['id'] ?? 0);
$pdo = db();

// Fetch order with customer profile
$stmt = $pdo->prepare('
    SELECT o.*, u.full_name as customer_name, u.email as customer_email
    FROM orders o 
    JOIN users u ON u.id = o.customer_id 
    WHERE o.id = ?
');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    redirect('admin/orders.php');
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();
    $newStatus = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $validStatuses = get_order_statuses();

    if (in_array($newStatus, $validStatuses, true)) {
        $pdo->beginTransaction();
        try {
            // Update orders table
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$newStatus, $orderId]);

            // Insert into history
            $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)')
                ->execute([$orderId, $newStatus, $notes ?: "Order updated to {$newStatus}", $user['id']]);

            // Sync with pickups or deliveries table
            if ($newStatus === 'Laundry Collected') {
                $pdo->prepare('INSERT INTO pickups (order_id, scheduled_at, collected_at, assigned_to) VALUES (?, NOW(), NOW(), ?) ON DUPLICATE KEY UPDATE collected_at = NOW(), assigned_to = ?')
                    ->execute([$orderId, $user['id'], $user['id']]);
            } elseif ($newStatus === 'Delivered') {
                $pdo->prepare('INSERT INTO deliveries (order_id, scheduled_at, delivered_at, assigned_to) VALUES (?, NOW(), NOW(), ?) ON DUPLICATE KEY UPDATE delivered_at = NOW(), assigned_to = ?')
                    ->execute([$orderId, $user['id'], $user['id']]);
            }

            // Customer in-app notification
            notify_user(
                (int)$order['customer_id'],
                $orderId,
                "Order {$order['reference']} Update",
                "Your laundry is now marked: {$newStatus}." . ($notes ? " Note: {$notes}" : "")
            );

            audit_log((int)$user['id'], 'Update order status', 'orders', $orderId, "Updated {$order['reference']} to {$newStatus}");

            $pdo->commit();
            flash('success', "Order status successfully updated to {$newStatus}.");
            redirect("admin/order-details.php?id={$orderId}");
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}

// Handle Record Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    verify_csrf();
    $payAmount = (float)($_POST['amount'] ?? 0);
    $payMethod = $_POST['method'] ?? 'Cash';
    $payRef = trim($_POST['reference'] ?? '');
    $payStatus = $_POST['payment_status'] ?? 'Paid';

    if ($payAmount <= 0) {
        flash('error', 'Please enter a valid payment amount greater than zero.');
    } else {
        try {
            $stmtPay = $pdo->prepare('
                INSERT INTO payments (order_id, amount, method, reference, status, paid_at, recorded_by)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ');
            $stmtPay->execute([$orderId, $payAmount, $payMethod, $payRef ?: ('REF-' . time()), $payStatus, $user['id']]);

            notify_user(
                (int)$order['customer_id'],
                $orderId,
                'Payment Recorded',
                "A payment of KES " . number_format($payAmount, 0) . " ({$payMethod}) has been credited to order {$order['reference']}."
            );

            audit_log((int)$user['id'], 'Record payment', 'payments', $orderId, "Recorded payment of KES {$payAmount} for {$order['reference']}");

            flash('success', 'Payment recorded successfully.');
            redirect("admin/order-details.php?id={$orderId}");
        } catch (Throwable $e) {
            flash('error', 'Payment could not be saved: ' . $e->getMessage());
        }
    }
}

// Fetch items
$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
$items->execute([$orderId]);
$items = $items->fetchAll();

// Fetch status history
$history = $pdo->prepare('
    SELECT h.*, u.full_name as staff_name 
    FROM order_status_history h 
    LEFT JOIN users u ON u.id = h.updated_by 
    WHERE h.order_id = ? 
    ORDER BY h.created_at ASC
');
$history->execute([$orderId]);
$history = $history->fetchAll();

// Fetch payments
$payments = $pdo->prepare('
    SELECT p.*, u.full_name as recorded_by_name 
    FROM payments p 
    LEFT JOIN users u ON u.id = p.recorded_by 
    WHERE p.order_id = ? 
    ORDER BY p.paid_at DESC
');
$payments->execute([$orderId]);
$payments = $payments->fetchAll();

$totalPaid = 0.0;
foreach ($payments as $p) {
    if ($p['status'] === 'Paid') $totalPaid += (float)$p['amount'];
}
$outstanding = max(0.0, (float)$order['total_amount'] - $totalPaid);
$overallPayStatus = $outstanding <= 0.01 ? 'Paid' : ($totalPaid > 0 ? 'Partially Paid' : 'Unpaid');

// Fetch staff users for assignment
$staffMembers = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'staff') AND is_active = 1")->fetchAll();

$pageTitle = 'Order ' . $order['reference'];
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ADMIN ORDER MANAGEMENT</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">
                    Order: <?= e($order['reference']) ?>
                </h1>
                <p class="text-muted" style="margin: 0;">
                    Customer: <?= e($order['customer_name']) ?> &bull; Booked on <?= format_datetime($order['created_at']) ?>
                </p>
            </div>
            <div>
                <?= status_badge($order['status']) ?>
                <?= payment_badge($overallPayStatus) ?>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/orders.php" style="margin-left: 8px;">
                    &larr; Back to Orders
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div style="display: grid; grid-template-columns: 1.25fr 0.75fr; gap: 28px; align-items: start;">
            <!-- Left Column: Items, Status Updater, and History -->
            <div>
                <!-- Status Updater Form -->
                <div class="panel" style="border-top: 4px solid var(--blue);">
                    <h2 class="panel-title mb-3">Update Order Laundry Milestone</h2>
                    <form method="post" action="<?= APP_URL ?>/admin/order-details.php?id=<?= $orderId ?>">
                        <?= csrf_field() ?>
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="status">Change Current Status *</label>
                                <select id="status" name="status" required>
                                    <?php foreach (get_order_statuses() as $st): ?>
                                        <option value="<?= e($st) ?>" <?= $st === $order['status'] ? 'selected' : '' ?>>
                                            <?= e($st) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="notes">Staff Note / Customer Visible Update</label>
                                <input type="text" id="notes" name="notes" placeholder="e.g. Received at facility, sorted, washing cycle 2 started...">
                            </div>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary">
                            Apply Status Update
                        </button>
                    </form>
                </div>

                <!-- Status History Timeline -->
                <div class="panel">
                    <h2 class="panel-title mb-3">Milestone Progression Log</h2>
                    <div class="order-tracking-vertical">
                        <?php foreach ($history as $h): ?>
                            <div class="timeline-event-row">
                                <div class="timeline-event-marker">&#10003;</div>
                                <div class="timeline-event-content">
                                    <div class="timeline-event-title"><?= e($h['status']) ?></div>
                                    <div class="timeline-event-meta">
                                        <?= format_datetime($h['created_at']) ?>
                                        &bull; Staff: <?= e($h['staff_name'] ?? 'System/Customer') ?>
                                    </div>
                                    <?php if (!empty($h['notes'])): ?>
                                        <div class="timeline-event-notes"><?= e($h['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="panel">
                    <h2 class="panel-title mb-3">Ordered Items Snapshot</h2>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Size Option</th>
                                    <th>Quantity</th>
                                    <th>Locked Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><strong><?= e($it['item_name']) ?></strong></td>
                                        <td><?= e($it['size_name']) ?></td>
                                        <td><?= $it['quantity'] ?></td>
                                        <td><?= money($it['unit_price']) ?></td>
                                        <td><strong><?= money($it['subtotal']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="calc-summary-box mt-3">
                        <div class="calc-line">
                            <span>Subtotal:</span>
                            <strong><?= money($order['subtotal']) ?></strong>
                        </div>
                        <div class="calc-line">
                            <span>Pickup &amp; Delivery Fee:</span>
                            <strong><?= money($order['pickup_fee']) ?></strong>
                        </div>
                        <div class="calc-line total">
                            <span>Total Due:</span>
                            <span><?= money($order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Customer Info, Payment Management -->
            <div>
                <!-- Customer & Logistics Panel -->
                <div class="panel">
                    <h2 class="panel-title mb-3">Customer &amp; Location</h2>
                    <div style="line-height: 1.8; font-size: 0.92rem;">
                        <strong>Name:</strong> <?= e($order['customer_name']) ?><br>
                        <strong>Email:</strong> <?= e($order['customer_email']) ?><br>
                        <strong>Phone:</strong> <a href="tel:<?= e($order['phone']) ?>" class="fw-bold"><?= e($order['phone']) ?></a><br>
                        <strong>Pickup Date:</strong> <?= format_date($order['pickup_date']) ?> (<?= e($order['pickup_time']) ?>)<br>
                        <strong>Pickup Location:</strong> <?= e($order['pickup_location']) ?><br>
                        <strong>Delivery Location:</strong> <?= e($order['delivery_location']) ?><br>
                        <?php if (!empty($order['instructions'])): ?>
                            <div style="background-color: var(--bg-light); border-left: 3px solid var(--yellow); padding: 8px 12px; margin-top: 10px;">
                                <strong>Instructions:</strong> <?= e($order['instructions']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <a class="btn btn-whatsapp btn-sm btn-block" href="https://wa.me/<?= sanitize_phone($order['phone']) ?>" target="_blank" rel="noopener noreferrer">
                            WhatsApp Customer
                        </a>
                    </div>
                </div>

                <!-- Record Payment Panel -->
                <div class="panel">
                    <h2 class="panel-title mb-2">Record Verified Payment</h2>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 0.92rem;">
                        <span>Paid: <strong class="text-success"><?= money($totalPaid) ?></strong></span>
                        <span>Balance: <strong style="color: <?= $outstanding > 0 ? 'var(--error)' : 'var(--success)' ?>;"><?= money($outstanding) ?></strong></span>
                    </div>

                    <form method="post" action="<?= APP_URL ?>/admin/order-details.php?id=<?= $orderId ?>">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="amount">Payment Amount (KES) *</label>
                            <input type="number" id="amount" name="amount" required min="1" step="1" value="<?= $outstanding > 0 ? (int)$outstanding : 0 ?>">
                        </div>

                        <div class="form-group">
                            <label for="method">Method *</label>
                            <select id="method" name="method" required>
                                <option value="Cash">Cash (Physical Receipt)</option>
                                <option value="M-Pesa">M-Pesa (Verified Transaction)</option>
                                <option value="Bank">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reference">Transaction Code / Receipt Reference</label>
                            <input type="text" id="reference" name="reference" placeholder="e.g. QJK8928LMN">
                        </div>

                        <div class="form-group">
                            <label for="payment_status">Status</label>
                            <select id="payment_status" name="payment_status">
                                <option value="Paid">Paid / Confirmed</option>
                                <option value="Partially Paid">Partially Paid</option>
                            </select>
                        </div>

                        <button type="submit" name="record_payment" class="btn btn-accent btn-block">
                            Record Payment Entry
                        </button>
                    </form>

                    <!-- Payment History List -->
                    <?php if (!empty($payments)): ?>
                        <h4 class="mt-4 mb-2" style="font-size: 0.95rem;">Payment History</h4>
                        <div style="display: grid; gap: 8px;">
                            <?php foreach ($payments as $p): ?>
                                <div style="background-color: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: 0.85rem;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <strong><?= money($p['amount']) ?> (<?= e($p['method']) ?>)</strong>
                                        <span class="status-pill badge-paid" style="font-size: 0.7rem;"><?= e($p['status']) ?></span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                        Ref: <?= e($p['reference'] ?? 'N/A') ?> &bull; <?= format_datetime($p['paid_at']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
