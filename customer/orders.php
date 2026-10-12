<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];

$filter = trim($_GET['filter'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$query = 'SELECT * FROM orders WHERE customer_id = ?';
$params = [$userId];

if ($filter === 'active') {
    $query .= " AND status NOT IN ('Delivered', 'Cancelled')";
} elseif ($filter === 'completed') {
    $query .= " AND status = 'Delivered'";
} elseif ($filter === 'cancelled') {
    $query .= " AND status = 'Cancelled'";
}

if (!empty($search)) {
    $query .= ' AND reference LIKE ?';
    $params[] = "%{$search}%";
}

$query .= ' ORDER BY created_at DESC';

$stmt = db()->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'My Laundry Orders';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ORDER HISTORY</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">My Laundry Bookings</h1>
                <p class="text-muted" style="margin: 0;">
                    Complete history of your past and current laundry orders with Stainless Laundry Services.
                </p>
            </div>
            <div>
                <a class="btn btn-accent" href="<?= APP_URL ?>/customer/place-order.php">
                    + Book New Pickup
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <form method="get" action="<?= APP_URL ?>/customer/orders.php" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
                <input type="text" name="search" class="filter-input" placeholder="Search by Reference (e.g. SLS-...)" value="<?= e($search) ?>">

                <select name="filter" style="width: auto; min-width: 160px;">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                    <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Active / In Progress</option>
                    <option value="completed" <?= $filter === 'completed' ? 'selected' : '' ?>>Delivered / Completed</option>
                    <option value="cancelled" <?= $filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if ($filter !== 'all' || !empty($search)): ?>
                    <a href="<?= APP_URL ?>/customer/orders.php" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <?php if (empty($orders)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted mb-3">No orders found matching your search criteria.</p>
                    <a class="btn btn-primary" href="<?= APP_URL ?>/customer/place-order.php">Place an Order Now</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Pickup Date &amp; Time</th>
                                <th>Items / Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/customer/order-details.php?id=<?= $order['id'] ?>" class="fw-bold" style="color: var(--navy);">
                                            <?= e($order['reference']) ?>
                                        </a>
                                        <br><small class="text-muted"><?= format_datetime($order['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <?= format_date($order['pickup_date']) ?><br>
                                        <small class="text-muted"><?= e($order['pickup_time']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= money($order['total_amount']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-pill badge-default"><?= e($order['payment_method']) ?></span>
                                    </td>
                                    <td>
                                        <?= status_badge($order['status']) ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $order['id'] ?>">
                                            View Progress
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