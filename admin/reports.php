<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = db();

// Status breakdown
$statusBreakdown = $pdo->query('
    SELECT status, COUNT(*) as order_count, SUM(total_amount) as total_val 
    FROM orders 
    GROUP BY status
')->fetchAll();

// Popular items
$popularItems = $pdo->query('
    SELECT item_name, size_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue
    FROM order_items
    GROUP BY item_name, size_name
    ORDER BY total_qty DESC
    LIMIT 10
')->fetchAll();

// Revenue overview
$totalRevenuePaid = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn();
$totalOrdersBilled = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();
$unpaidBalance = max(0.0, $totalOrdersBilled - $totalRevenuePaid);

// Recent days summary
$recentDaily = $pdo->query("
    SELECT DATE(created_at) as order_date, COUNT(*) as count, SUM(total_amount) as billed
    FROM orders
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC
    LIMIT 7
")->fetchAll();

$pageTitle = 'Operational & Financial Reports';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">ANALYTICS &amp; AUDIT</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Business Reports</h1>
                <p class="text-muted" style="margin: 0;">
                    Service volume breakdowns, popular basket sizes, and realized revenues.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Stat Cards -->
        <div class="stat-cards-grid">
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--success); font-size: 2rem;">
                    KES <?= number_format($totalRevenuePaid, 0) ?>
                </div>
                <div class="stat-card-label">Realized Revenue Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--error); font-size: 2rem;">
                    KES <?= number_format($unpaidBalance, 0) ?>
                </div>
                <div class="stat-card-label">Pending Customer Balance</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--navy); font-size: 2rem;">
                    KES <?= number_format($totalOrdersBilled, 0) ?>
                </div>
                <div class="stat-card-label">Total Valid Orders Billed</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px; align-items: start;">
            <!-- Status Breakdown -->
            <div class="panel">
                <h2 class="panel-title mb-3">Orders Breakdown by Status</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Billed Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusBreakdown as $sb): ?>
                                <tr>
                                    <td><?= status_badge($sb['status']) ?></td>
                                    <td><strong><?= (int)$sb['order_count'] ?></strong></td>
                                    <td><?= money($sb['total_val'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Popular Laundry Items -->
            <div class="panel">
                <h2 class="panel-title mb-3">Top Laundry Items Cleaned</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item &amp; Size</th>
                                <th>Quantity Cleaned</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popularItems as $pi): ?>
                                <tr>
                                    <td><strong><?= e($pi['item_name']) ?></strong> (<?= e($pi['size_name']) ?>)</td>
                                    <td><span class="status-pill badge-default"><?= (int)$pi['total_qty'] ?> loads</span></td>
                                    <td><?= money($pi['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Orders Activity Table -->
        <div class="panel">
            <h2 class="panel-title mb-3">Daily Orders Activity (Last 7 Days)</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Orders Placed</th>
                            <th>Total Billed Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDaily as $rd): ?>
                            <tr>
                                <td><strong><?= format_date($rd['order_date']) ?></strong></td>
                                <td><?= (int)$rd['count'] ?> orders</td>
                                <td><?= money($rd['billed'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>