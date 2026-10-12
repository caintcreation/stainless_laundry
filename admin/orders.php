<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();

// Filter parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$whereClauses = ['1=1'];
$params = [];

if (!empty($search)) {
    $whereClauses[] = '(o.reference LIKE ? OR u.full_name LIKE ? OR o.phone LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $whereClauses[] = 'o.status = ?';
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $whereClauses[] = 'o.pickup_date >= ?';
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereClauses[] = 'o.pickup_date <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $whereClauses);

// Count total matching
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.customer_id WHERE {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRecords / $limit));

// Fetch page results
$query = "
    SELECT o.*, u.full_name as customer_name, u.email as customer_email,
           (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = o.id AND status = 'Paid') as paid_amount,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    JOIN users u ON u.id = o.customer_id 
    WHERE {$whereSql}
    ORDER BY o.created_at DESC 
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Manage Orders';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">OPERATIONS</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Orders Directory</h1>
                <p class="text-muted" style="margin: 0;">
                    Filtered overview of all laundry orders (Total: <?= $totalRecords ?> found).
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Filters Toolbar -->
        <div class="filter-toolbar">
            <form method="get" action="<?= APP_URL ?>/admin/orders.php" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
                <input type="text" name="search" class="filter-input" placeholder="Search reference, customer name, phone..." value="<?= e($search) ?>">

                <select name="status" style="width: auto; min-width: 170px;">
                    <option value="">-- All Statuses --</option>
                    <?php foreach (get_order_statuses() as $st): ?>
                        <option value="<?= e($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="date_from" value="<?= e($dateFrom) ?>" title="Pickup from" style="width: auto;">
                <input type="date" name="date_to" value="<?= e($dateTo) ?>" title="Pickup to" style="width: auto;">

                <button type="submit" class="btn btn-primary btn-sm">Filter Orders</button>
                <?php if (!empty($search) || !empty($statusFilter) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="<?= APP_URL ?>/admin/orders.php" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <?php if (empty($orders)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">No orders match the selected filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Customer Info</th>
                                <th>Pickup Date &amp; Slot</th>
                                <th>Pickup Location</th>
                                <th>Total &amp; Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
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
                                        <a href="tel:<?= e($ord['phone']) ?>"><?= e($ord['phone']) ?></a>
                                    </td>
                                    <td>
                                        <?= format_date($ord['pickup_date']) ?><br>
                                        <small class="text-muted"><?= e($ord['pickup_time']) ?></small>
                                    </td>
                                    <td>
                                        <?= e($ord['pickup_location']) ?>
                                    </td>
                                    <td>
                                        <strong><?= money($total) ?></strong><br>
                                        <?= payment_badge($pStatus) ?>
                                    </td>
                                    <td>
                                        <?= status_badge($ord['status']) ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/order-details.php?id=<?= $ord['id'] ?>">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= APP_URL ?>/admin/orders.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>