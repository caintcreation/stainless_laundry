<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];

// Handle Mark All Read action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    verify_csrf();
    db()->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL')->execute([$userId]);
    flash('success', 'All notifications marked as read.');
    redirect('customer/notifications.php');
}

// Fetch all notifications
$stmt = db()->prepare('
    SELECT n.*, o.reference 
    FROM notifications n 
    LEFT JOIN orders o ON o.id = n.order_id 
    WHERE n.user_id = ? 
    ORDER BY n.created_at DESC
');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container" style="max-width: 860px;">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">IN-APP ALERTS</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">My Notifications</h1>
                <p class="text-muted" style="margin: 0;">
                    Real-time status updates on order pickups, wash cycles, and deliveries.
                </p>
            </div>
            <div>
                <?php if (!empty($notifications)): ?>
                    <form method="post" action="<?= APP_URL ?>/customer/notifications.php" style="margin: 0;">
                        <?= csrf_field() ?>
                        <button type="submit" name="mark_all_read" class="btn btn-secondary btn-sm">Mark All as Read</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <div class="panel">
            <?php if (empty($notifications)): ?>
                <div class="text-center" style="padding: 40px 0;">
                    <p class="text-muted">You have no notifications right now.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 14px;">
                    <?php foreach ($notifications as $n): ?>
                        <div style="background-color: <?= empty($n['read_at']) ? 'var(--bg-light)' : '#FFFFFF' ?>; border: 1.5px solid <?= empty($n['read_at']) ? 'var(--blue)' : 'var(--border)' ?>; border-radius: var(--radius-md); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <strong style="color: var(--navy); font-size: 1rem;"><?= e($n['title']) ?></strong>
                                    <?php if (empty($n['read_at'])): ?>
                                        <span class="status-pill badge-new" style="font-size: 0.72rem; padding: 2px 8px;">New</span>
                                    <?php endif; ?>
                                </div>
                                <p style="margin: 0 0 6px; color: var(--ink); font-size: 0.92rem;"><?= e($n['message']) ?></p>
                                <span class="text-muted" style="font-size: 0.8rem;"><?= format_datetime($n['created_at']) ?></span>
                            </div>
                            <div>
                                <?php if (!empty($n['order_id'])): ?>
                                    <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/order-details.php?id=<?= $n['order_id'] ?>">
                                        View Order &rarr;
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>