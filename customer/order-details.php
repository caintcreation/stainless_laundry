<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];
$orderId = (int)($_GET['id'] ?? 0);

// Look up order, verifying ownership
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND customer_id = ?');
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found or you do not have permission to view it.');
    redirect('customer/orders.php');
}

// Fetch order items snapshots
$stmtItems = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Fetch status history
$stmtHistory = db()->prepare('
    SELECT h.*, u.full_name as staff_name 
    FROM order_status_history h 
    LEFT JOIN users u ON u.id = h.updated_by 
    WHERE h.order_id = ? 
    ORDER BY h.created_at ASC
');
$stmtHistory->execute([$orderId]);
$history = $stmtHistory->fetchAll();

// Fetch payments
$stmtPayments = db()->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY paid_at DESC');
$stmtPayments->execute([$orderId]);
$payments = $stmtPayments->fetchAll();

$totalPaid = 0.0;
foreach ($payments as $p) {
    if ($p['status'] === 'Paid') {
        $totalPaid += (float)$p['amount'];
    }
}
$outstanding = max(0.0, (float)$order['total_amount'] - $totalPaid);
$paymentStatus = $outstanding <= 0.01 ? 'Paid' : ($totalPaid > 0 ? 'Partially Paid' : 'Unpaid');

// Fetch pickup and delivery details
$stmtPickup = db()->prepare('SELECT * FROM pickups WHERE order_id = ?');
$stmtPickup->execute([$orderId]);
$pickup = $stmtPickup->fetch();

$stmtDelivery = db()->prepare('SELECT * FROM deliveries WHERE order_id = ?');
$stmtDelivery->execute([$orderId]);
$delivery = $stmtDelivery->fetch();

// 10 milestone workflow steps for the progress meter
$workflowSteps = [
    'Order Placed',
    'Pickup Scheduled',
    'Laundry Collected',
    'Received at Laundry Facility',
    'Washing in Progress',
    'Drying in Progress',
    'Folding and Quality Check',
    'Ready for Delivery',
    'Out for Delivery',
    'Delivered'
];

$currentStatus = $order['status'];
$currentStepIndex = array_search($currentStatus, $workflowSteps, true);
if ($currentStepIndex === false && $currentStatus === 'Pickup Assigned') {
    $currentStepIndex = 1;
}

$pageTitle = 'Order ' . $order['reference'];
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container">
        <!-- Order Header -->
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ORDER SPECIFICATION</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">
                    Order: <?= e($order['reference']) ?>
                </h1>
                <p class="text-muted" style="margin: 0;">
                    Placed on <?= format_datetime($order['created_at']) ?> &bull; Payment: <?= payment_badge($paymentStatus) ?>
                </p>
            </div>
            <div>
                <?= status_badge($order['status']) ?>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/orders.php" style="margin-left: 8px;">
                    &larr; All Orders
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <!-- Visual Milestone Progress Meter -->
        <div class="panel">
            <h2 class="panel-title mb-2">Order Tracking Progress</h2>
            <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 20px;">
                Verified progress milestones recorded live by Stainless Laundry Services staff.
            </p>

            <ul class="tracking-stepper-list">
                <?php foreach ($workflowSteps as $idx => $step): ?>
                    <?php 
                        $isCompleted = ($currentStepIndex !== false && $idx < $currentStepIndex) || $currentStatus === 'Delivered';
                        $isCurrent = ($currentStepIndex !== false && $idx === $currentStepIndex) && $currentStatus !== 'Delivered';
                        $class = $isCompleted ? 'completed' : ($isCurrent ? 'current' : '');
                    ?>
                    <li class="tracking-stepper-item <?= $class ?>">
                        <span class="step-dot"><?= $isCompleted ? '&#10003;' : ($isCurrent ? '&#9654;' : ($idx + 1)) ?></span>
                        <?= e($step) ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($order['status'] === 'Cancelled'): ?>
                <div class="alert alert-error mt-3">
                    <strong>Notice:</strong> This order was cancelled. Please contact our Ndagani office if you have any questions.
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 28px; align-items: start;">
            <!-- Left: Tracking History Timeline & Pickup Info -->
            <div>
                <div class="panel">
                    <h2 class="panel-title mb-3">Live Status Timeline</h2>
                    <div class="order-tracking-vertical">
                        <?php foreach ($history as $event): ?>
                            <div class="timeline-event-row">
                                <div class="timeline-event-marker">&#10003;</div>
                                <div class="timeline-event-content">
                                    <div class="timeline-event-title"><?= e($event['status']) ?></div>
                                    <div class="timeline-event-meta">
                                        <?= format_datetime($event['created_at']) ?>
                                        <?php if (!empty($event['staff_name'])): ?>
                                            &bull; Updated by <?= e($event['staff_name']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($event['notes'])): ?>
                                        <div class="timeline-event-notes"><?= e($event['notes']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Logistics / Pickup Details -->
                <div class="panel">
                    <h2 class="panel-title mb-3">Pickup &amp; Delivery Logistics</h2>
                    <div style="line-height: 1.8; font-size: 0.92rem;">
                        <strong>Requested Pickup Date:</strong> <?= format_date($order['pickup_date']) ?> (<?= e($order['pickup_time']) ?>)<br>
                        <strong>Collection Phone:</strong> <?= e($order['phone']) ?><br>
                        <strong>Pickup Location:</strong> <?= e($order['pickup_location']) ?><br>
                        <strong>Return Delivery Location:</strong> <?= e($order['delivery_location']) ?><br>
                        <?php if (!empty($order['instructions'])): ?>
                            <strong>Special Instructions:</strong> <em><?= e($order['instructions']) ?></em><br>
                        <?php endif; ?>
                        <?php if ($pickup && !empty($pickup['collected_at'])): ?>
                            <span class="text-success fw-bold">&#10003; Laundry Collected at:</span> <?= format_datetime($pickup['collected_at']) ?><br>
                        <?php endif; ?>
                        <?php if ($delivery && !empty($delivery['delivered_at'])): ?>
                            <span class="text-success fw-bold">&#10003; Clean Laundry Delivered at:</span> <?= format_datetime($delivery['delivered_at']) ?><br>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Itemized Bill & Payments -->
            <div>
                <div class="panel">
                    <h2 class="panel-title mb-3">Itemized Receipt</h2>

                    <div style="display: grid; gap: 10px; margin-bottom: 20px;">
                        <?php foreach ($items as $item): ?>
                            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;">
                                <div>
                                    <strong style="color: var(--navy);"><?= e($item['item_name']) ?></strong>
                                    <span class="text-muted" style="display: block; font-size: 0.85rem;">
                                        Size: <?= e($item['size_name']) ?> &times; <?= $item['quantity'] ?> @ <?= money($item['unit_price']) ?>
                                    </span>
                                </div>
                                <div style="font-weight: 700; color: var(--navy);">
                                    <?= money($item['subtotal']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="calc-summary-box" style="margin-top: 0;">
                        <div class="calc-line">
                            <span>Subtotal:</span>
                            <strong><?= money($order['subtotal']) ?></strong>
                        </div>
                        <div class="calc-line">
                            <span>Pickup &amp; Delivery Fee:</span>
                            <strong><?= money($order['pickup_fee']) ?></strong>
                        </div>
                        <div class="calc-line total">
                            <span>Total Amount:</span>
                            <span><?= money($order['total_amount']) ?></span>
                        </div>
                    </div>

                    <!-- Payment Overview -->
                    <div style="margin-top: 20px; padding: 16px; background-color: var(--bg-light); border-radius: var(--radius-md); border: 1px solid var(--border);">
                        <strong style="color: var(--navy); display: block; margin-bottom: 8px;">Payment Status</strong>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem;">
                            <span>Payment Method:</span>
                            <strong><?= e($order['payment_method']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 0.9rem;">
                            <span>Amount Paid:</span>
                            <strong style="color: var(--success);"><?= money($totalPaid) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span>Outstanding Balance:</span>
                            <strong style="color: <?= $outstanding > 0 ? 'var(--error)' : 'var(--success)' ?>;"><?= money($outstanding) ?></strong>
                        </div>

                        <?php if ($outstanding > 0): ?>
                            <div class="alert alert-info mt-3" style="font-size: 0.85rem; margin-bottom: 0;">
                                <strong>Payment Instructions:</strong><br>
                                Pay via M-Pesa or Cash upon laundry collection/delivery. Our staff will update your payment confirmation in the system.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>