<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];

// If an ID is passed in GET, redirect to order-details
if (!empty($_GET['id'])) {
    redirect('customer/order-details.php?id=' . (int)$_GET['id']);
}

// Fetch customer's active orders
$stmtActive = db()->prepare("
    SELECT * FROM orders 
    WHERE customer_id = ? AND status NOT IN ('Delivered', 'Cancelled')
    ORDER BY created_at DESC
");
$stmtActive->execute([$userId]);
$activeOrders = $stmtActive->fetchAll();

// Fetch customer's all recent orders
$stmtAll = db()->prepare('
    SELECT * FROM orders 
    WHERE customer_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
');
$stmtAll->execute([$userId]);
$recentOrders = $stmtAll->fetchAll();

$pageTitle = 'Track Laundry';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">LIVE PROGRESS</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Track Laundry Orders</h1>
                <p class="text-muted" style="margin: 0;">
                    Monitor current washing, drying, folding, and delivery stages for your clothes.
                </p>
            </div>
            <div>
                <a class="btn btn-accent" href="<?= APP_URL ?>/customer/place-order.php">+ Book New Order</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <?php if (!empty($activeOrders)): ?>
            <div class="panel">
                <h2 class="panel-title mb-3">Orders Currently in Progress</h2>
                <div style="display: grid; gap: 16px;">
                    <?php foreach ($activeOrders as $ord): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; background-color: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; flex-wrap: wrap; gap: 14px;">
                            <div>
                                <strong style="color: var(--navy); font-size: 1.15rem; display: block;">
                                    <?= e($ord['reference']) ?>
                                </strong>
                                <span class="text-muted" style="font-size: 0.88rem;">
                                    Pickup: <?= format_date($ord['pickup_date']) ?> (<?= e($ord['pickup_time']) ?>) &bull; Total: <?= money($ord['total_amount']) ?>
                                </span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?= status_badge($ord['status']) ?>
                                <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $ord['id'] ?>">
                                    Live Tracker &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="panel">
            <h2 class="panel-title mb-3">All Customer Orders</h2>
            <?php if (empty($recentOrders)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted mb-3">You do not have any orders to track yet.</p>
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
                                <th>Current Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr>
                                    <td><strong><?= e($ro['reference']) ?></strong></td>
                                    <td><?= format_date($ro['pickup_date']) ?> (<?= e($ro['pickup_time']) ?>)</td>
                                    <td><?= money($ro['total_amount']) ?></td>
                                    <td><?= status_badge($ro['status']) ?></td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $ro['id'] ?>">
                                            Track Progress
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