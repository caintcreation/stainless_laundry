<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];

// Fetch customer statistics
$counts = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'completed' => 0
];

$stmtCounts = db()->prepare('
    SELECT status, COUNT(*) as cnt 
    FROM orders 
    WHERE customer_id = ? 
    GROUP BY status
');
$stmtCounts->execute([$userId]);
while ($row = $stmtCounts->fetch()) {
    $c = (int)$row['cnt'];
    $counts['total'] += $c;
    if ($row['status'] === 'Delivered') {
        $counts['completed'] += $c;
    } elseif (in_array($row['status'], ['Order Placed', 'Pickup Scheduled', 'Pickup Assigned'], true)) {
        $counts['pending'] += $c;
    } elseif ($row['status'] !== 'Cancelled') {
        $counts['processing'] += $c;
    }
}

// Fetch most recent orders
$stmtOrders = db()->prepare('
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 6
');
$stmtOrders->execute([$userId]);
$recentOrders = $stmtOrders->fetchAll();

// Fetch latest active order for live tracking highlight
$stmtActive = db()->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? AND status NOT IN ('Delivered', 'Cancelled')
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmtActive->execute([$userId]);
$activeOrder = $stmtActive->fetch();

$activeOrderHistory = [];
if ($activeOrder) {
    $stmtHist = db()->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at ASC');
    $stmtHist->execute([(int)$activeOrder['id']]);
    $activeOrderHistory = $stmtHist->fetchAll();
}

// Fetch latest notifications
$stmtNotifs = db()->prepare('
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 4
');
$stmtNotifs->execute([$userId]);
$notifications = $stmtNotifs->fetchAll();

$pageTitle = 'Customer Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">CUSTOMER PORTAL</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Welcome, <?= e($user['full_name']) ?>!</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage your laundry bookings, track washing progress, and check receipts.
                </p>
            </div>
            <div>
                <a class="btn btn-accent btn-lg" href="<?= APP_URL ?>/customer/place-order.php">
                    + Book Laundry Pickup
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <!-- Stat Cards -->
        <div class="stat-cards-grid">
            <div class="stat-card">
                <div class="stat-card-number"><?= $counts['total'] ?></div>
                <div class="stat-card-label">Total Laundry Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--warning);"><?= $counts['pending'] ?></div>
                <div class="stat-card-label">Pending Collection</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--blue);"><?= $counts['processing'] ?></div>
                <div class="stat-card-label">In Washing &amp; Drying</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--success);"><?= $counts['completed'] ?></div>
                <div class="stat-card-label">Successfully Delivered</div>
            </div>
        </div>

        <!-- Active Laundry Order Live Tracker Highlight -->
        <?php if ($activeOrder): ?>
            <div class="panel" style="border-left: 5px solid var(--blue);">
                <div class="panel-header">
                    <div>
                        <span class="portal-title-tag">ACTIVE LAUNDRY PROGRESS</span>
                        <h2 class="panel-title" style="margin-top: 4px;">
                            Order: <?= e($activeOrder['reference']) ?>
                        </h2>
                        <span class="text-muted" style="font-size: 0.88rem;">
                            Pickup: <?= format_date($activeOrder['pickup_date']) ?> (<?= e($activeOrder['pickup_time']) ?>) &bull; Total: <?= money($activeOrder['total_amount']) ?>
                        </span>
                    </div>
                    <div>
                        <?= status_badge($activeOrder['status']) ?>
                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $activeOrder['id'] ?>" style="margin-left: 8px;">
                            Full Details
                        </a>
                    </div>
                </div>

                <div class="order-tracking-vertical">
                    <?php foreach ($activeOrderHistory as $ev): ?>
                        <div class="timeline-event-row">
                            <div class="timeline-event-marker">&#10003;</div>
                            <div class="timeline-event-content">
                                <div class="timeline-event-title"><?= e($ev['status']) ?></div>
                                <div class="timeline-event-meta"><?= format_datetime($ev['created_at']) ?></div>
                                <?php if (!empty($ev['notes'])): ?>
                                    <div class="timeline-event-notes"><?= e($ev['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Split View: Recent Orders and Notifications / Profile -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 28px; align-items: start;">
            <!-- Recent Orders Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Recent Laundry Orders</h2>
                    <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/orders.php">View All Orders</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4" style="padding: 40px 0;">
                        <p class="text-muted mb-3">You have not placed any laundry bookings yet.</p>
                        <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Book Your First Pickup</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Pickup Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><strong><?= e($order['reference']) ?></strong></td>
                                        <td><?= format_date($order['pickup_date']) ?><br><small class="text-muted"><?= e($order['pickup_time']) ?></small></td>
                                        <td><?= money($order['total_amount']) ?></td>
                                        <td><?= status_badge($order['status']) ?></td>
                                        <td>
                                            <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $order['id'] ?>">
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

            <!-- Side Column: Notifications & Profile -->
            <div>
                <!-- Notifications Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title" style="font-size: 1.15rem;">Notifications</h2>
                        <a href="<?= APP_URL ?>/customer/notifications.php" style="font-size: 0.85rem; font-weight: 600;">View All</a>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <p class="text-muted" style="font-size: 0.88rem;">No notifications right now.</p>
                    <?php else: ?>
                        <div style="display: grid; gap: 12px;">
                            <?php foreach ($notifications as $n): ?>
                                <div style="background-color: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 12px; font-size: 0.88rem;">
                                    <strong style="color: var(--navy); display: block; margin-bottom: 2px;"><?= e($n['title']) ?></strong>
                                    <p style="margin: 0 0 4px; color: var(--ink);"><?= e($n['message']) ?></p>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= format_datetime($n['created_at']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Customer Profile Card -->
                <div class="panel">
                    <h2 class="panel-title mb-2" style="font-size: 1.15rem;">Default Location</h2>
                    <p style="font-size: 0.9rem; margin-bottom: 8px;">
                        <strong>Location:</strong> <?= e($user['location'] ?? 'Ndagani, Chuka') ?><br>
                        <strong>Phone:</strong> <?= e($user['phone']) ?><br>
                        <?php if (!empty($user['address_details'])): ?>
                            <strong>Room/Apartment:</strong> <?= e($user['address_details']) ?>
                        <?php endif; ?>
                    </p>
                    <a class="btn btn-secondary btn-sm mt-2" href="<?= APP_URL ?>/customer/profile.php">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>