<?php
declare(strict_types=1);

$currentAdminScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<div class="portal-nav-tabs">
    <a class="portal-tab-link <?= $currentAdminScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/dashboard.php">
        Dashboard
    </a>
    <a class="portal-tab-link <?= in_array($currentAdminScript, ['orders.php', 'order-details.php'], true) ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/orders.php">
        Orders
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'tracking.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/tracking.php">
        Tracking
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'pickups.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/pickups.php">
        Pickups
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'deliveries.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/deliveries.php">
        Deliveries
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'payments.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/payments.php">
        Payments
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'customers.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/customers.php">
        Customers
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'services.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/services.php">
        Services
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'pricing.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/pricing.php">
        Pricing
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/reports.php">
        Reports
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'users.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/users.php">
        Staff &amp; Users
    </a>
    <a class="portal-tab-link <?= $currentAdminScript === 'settings.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/settings.php">
        Settings
    </a>
</div>
