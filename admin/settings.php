<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    verify_csrf();
    $phone = sanitize_phone(trim($_POST['business_phone'] ?? '0101476174'));
    $location = trim($_POST['business_location'] ?? 'Around Ndagani, Chuka, Kenya');
    $pickupFee = (float)($_POST['pickup_fee'] ?? 0);

    update_setting('business_phone', $phone);
    update_setting('business_location', $location);
    update_setting('pickup_fee', (string)$pickupFee);

    audit_log((int)$user['id'], 'Update settings', 'settings', null, "Updated contact info and pickup fee");
    flash('success', 'Business settings successfully updated.');
    redirect('admin/settings.php');
}

$phone = setting('business_phone', '0101476174');
$location = setting('business_location', 'Around Ndagani, Chuka, Kenya');
$pickupFee = (float)setting('pickup_fee', '0');

// Fetch recent audit logs
$auditLogs = $pdo->query('
    SELECT al.*, u.full_name as user_name 
    FROM audit_logs al 
    LEFT JOIN users u ON u.id = al.user_id 
    ORDER BY al.created_at DESC 
    LIMIT 20
')->fetchAll();

$pageTitle = 'Business Settings';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">CONFIGURATION</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">System &amp; Business Settings</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage business contact info, location details, pickup delivery fees, and system audit logs.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 28px; align-items: start;">
            <!-- Settings Form -->
            <div class="panel">
                <h2 class="panel-title mb-3">Stainless Laundry Configuration</h2>
                <form method="post" action="<?= APP_URL ?>/admin/settings.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="business_phone">Business Call / WhatsApp Phone *</label>
                        <input type="text" id="business_phone" name="business_phone" required value="<?= e($phone) ?>" placeholder="0101476174">
                        <span class="form-hint">Displayed across the public website and footer links.</span>
                    </div>

                    <div class="form-group">
                        <label for="business_location">Business Physical Location *</label>
                        <input type="text" id="business_location" name="business_location" required value="<?= e($location) ?>" placeholder="Around Ndagani, Chuka, Kenya">
                    </div>

                    <div class="form-group">
                        <label for="pickup_fee">Default Pickup &amp; Delivery Fee (KES) *</label>
                        <input type="number" id="pickup_fee" name="pickup_fee" required min="0" step="1" value="<?= (int)$pickupFee ?>">
                        <span class="form-hint">Set to 0 for free pickup around Ndagani as advertised.</span>
                    </div>

                    <button type="submit" name="save_settings" class="btn btn-primary btn-block">
                        Save System Settings
                    </button>
                </form>
            </div>

            <!-- Recent System Audit Logs -->
            <div class="panel">
                <h2 class="panel-title mb-3">Security &amp; Operational Audit Trail</h2>
                <p class="text-muted" style="font-size: 0.88rem; margin-bottom: 16px;">
                    Records all key administrative events, status updates, and financial actions.
                </p>

                <?php if (empty($auditLogs)): ?>
                    <p class="text-muted">No audit logs recorded yet.</p>
                <?php else: ?>
                    <div style="display: grid; gap: 10px; max-height: 480px; overflow-y: auto;">
                        <?php foreach ($auditLogs as $log): ?>
                            <div style="background-color: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 0.85rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                    <strong><?= e($log['action']) ?></strong>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= format_datetime($log['created_at']) ?></span>
                                </div>
                                <div style="color: var(--ink);">
                                    <?= e($log['details'] ?? 'No extra details') ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem; margin-top: 2px;">
                                    By: <?= e($log['user_name'] ?? 'System') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>