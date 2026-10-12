<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT u.*, cp.location, cp.address_details,
           COUNT(o.id) as order_count,
           COALESCE(SUM(o.total_amount), 0) as total_spend
    FROM users u 
    LEFT JOIN customer_profiles cp ON cp.user_id = u.id 
    LEFT JOIN orders o ON o.customer_id = u.id AND o.status != 'Cancelled'
    WHERE u.role = 'customer'
";

$params = [];
if (!empty($search)) {
    $query .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR cp.location LIKE ?)';
    $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
}

$query .= ' GROUP BY u.id ORDER BY u.created_at DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Manage Customers';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">CLIENT DIRECTORY</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Registered Customers</h1>
                <p class="text-muted" style="margin: 0;">
                    Client profiles, contact telephone numbers, Ndagani hostel locations, and lifetime laundry volume.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Search Toolbar -->
        <div class="filter-toolbar">
            <form method="get" action="<?= APP_URL ?>/admin/customers.php" style="display: flex; gap: 12px; width: 100%; max-width: 600px;">
                <input type="text" name="search" class="filter-input" placeholder="Search customer by name, phone, email, hostel..." value="<?= e($search) ?>">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= APP_URL ?>/admin/customers.php" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel">
            <?php if (empty($customers)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">No customers found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Contact Information</th>
                                <th>Hostel / Estate Location</th>
                                <th>Total Bookings</th>
                                <th>Lifetime Value</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['full_name']) ?></strong>
                                    </td>
                                    <td>
                                        <a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone']) ?></a><br>
                                        <small class="text-muted"><?= e($c['email']) ?></small>
                                    </td>
                                    <td>
                                        <?= e($c['location'] ?? 'Not specified') ?><br>
                                        <?php if (!empty($c['address_details'])): ?>
                                            <small class="text-muted"><?= e($c['address_details']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-pill badge-default"><?= (int)$c['order_count'] ?> orders</span>
                                    </td>
                                    <td>
                                        <strong><?= money($c['total_spend']) ?></strong>
                                    </td>
                                    <td>
                                        <?= format_date($c['created_at']) ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/orders.php?search=<?= urlencode($c['phone']) ?>">
                                            Orders
                                        </a>
                                        <a class="btn btn-whatsapp btn-sm" href="https://wa.me/<?= sanitize_phone($c['phone']) ?>" target="_blank" rel="noopener noreferrer">
                                            Chat
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