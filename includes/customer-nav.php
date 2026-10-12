<?php
declare(strict_types=1);

$currentCustomerScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$user = current_user();
$unread = $user ? unread_notifications_count($user['id']) : 0;
?>
<div class="portal-nav-tabs">
    <a class="portal-tab-link <?= $currentCustomerScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/dashboard.php">
        Overview
    </a>
    <a class="portal-tab-link <?= $currentCustomerScript === 'place-order.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/place-order.php">
        + Place New Order
    </a>
    <a class="portal-tab-link <?= in_array($currentCustomerScript, ['orders.php', 'order-details.php'], true) ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/orders.php">
        My Orders
    </a>
    <a class="portal-tab-link <?= $currentCustomerScript === 'track-order.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/track-order.php">
        Track Laundry
    </a>
    <a class="portal-tab-link <?= $currentCustomerScript === 'notifications.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/notifications.php">
        Notifications <?= $unread > 0 ? "({$unread})" : '' ?>
    </a>
    <a class="portal-tab-link <?= $currentCustomerScript === 'profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/profile.php">
        Profile Settings
    </a>
</div>
