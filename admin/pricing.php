<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = db();

// Handle Price Updates or Add Size Option
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['update_price'])) {
        $id = (int)($_POST['id'] ?? 0);
        $newPrice = (float)($_POST['unit_price'] ?? 0);

        if ($id > 0 && $newPrice >= 0) {
            $pdo->prepare('UPDATE service_options SET unit_price = ? WHERE id = ?')->execute([$newPrice, $id]);
            audit_log((int)current_user()['id'], 'Update price', 'service_options', $id, "Updated unit price to KES {$newPrice}");
            flash('success', "Price updated to KES " . number_format($newPrice, 0) . ". (Applies to new orders; past orders retain snapshot prices).");
            redirect('admin/pricing.php');
        }
    } elseif (isset($_POST['add_option'])) {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $optionName = trim($_POST['option_name'] ?? '');
        $price = (float)($_POST['unit_price'] ?? 0);

        if ($serviceId > 0 && !empty($optionName) && $price >= 0) {
            $pdo->prepare('INSERT INTO service_options (service_id, option_name, unit_price, is_active) VALUES (?, ?, ?, 1)')
                ->execute([$serviceId, $optionName, $price]);
            audit_log((int)current_user()['id'], 'Add price option', 'service_options', (int)$pdo->lastInsertId(), "Added {$optionName} at KES {$price}");
            flash('success', "Added size option '{$optionName}'.");
            redirect('admin/pricing.php');
        }
    } elseif (isset($_POST['toggle_option'])) {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['current_status'] ?? 0) === 1 ? 0 : 1;

        if ($id > 0) {
            $pdo->prepare('UPDATE service_options SET is_active = ? WHERE id = ?')->execute([$status, $id]);
            flash('success', 'Price option availability updated.');
            redirect('admin/pricing.php');
        }
    } elseif (isset($_POST['update_fee'])) {
        $fee = (float)($_POST['pickup_fee'] ?? 0);
        update_setting('pickup_fee', (string)$fee);
        audit_log((int)current_user()['id'], 'Update pickup fee', 'settings', null, "Set pickup fee to KES {$fee}");
        flash('success', "Doorstep pickup fee updated to KES " . number_format($fee, 0) . ".");
        redirect('admin/pricing.php');
    }
}

// Fetch all options grouped by service
$options = $pdo->query('
    SELECT so.*, s.name as service_name 
    FROM service_options so 
    JOIN services s ON s.id = so.service_id 
    ORDER BY s.id ASC, so.unit_price ASC
')->fetchAll();

$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC')->fetchAll();
$currentPickupFee = (float)setting('pickup_fee', '0');

$pageTitle = 'Manage Pricing & Fees';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">RATE CARD</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Service Pricing &amp; Fees</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage basket and duvet tier rates. Changes only apply to future orders; prior orders retain locked historical snapshots.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/services.php">&larr; Services List</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Pickup Fee Config Panel -->
        <div class="panel" style="background-color: var(--bg-light);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h3 style="margin-bottom: 4px; font-size: 1.15rem;">Configured Pickup &amp; Delivery Fee</h3>
                    <p class="text-muted" style="margin: 0; font-size: 0.88rem;">
                        Default is 0 (Free pickup around Ndagani/Chuka as featured on the poster).
                    </p>
                </div>
                <form method="post" action="<?= APP_URL ?>/admin/pricing.php" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                    <?= csrf_field() ?>
                    <label for="pickup_fee" style="margin: 0; font-size: 0.88rem;">Fee (KES):</label>
                    <input type="number" id="pickup_fee" name="pickup_fee" min="0" step="1" value="<?= (int)$currentPickupFee ?>" style="width: 100px;">
                    <button type="submit" name="update_fee" class="btn btn-primary btn-sm">Update Fee</button>
                </form>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.35fr 0.65fr; gap: 28px; align-items: start;">
            <!-- Price Options Table -->
            <div class="panel">
                <h2 class="panel-title mb-3">Live Service Pricing Options</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Size / Specification</th>
                                <th>Current Unit Price (KES)</th>
                                <th>Status</th>
                                <th>Update Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($options as $opt): ?>
                                <tr>
                                    <td><strong><?= e($opt['service_name']) ?></strong></td>
                                    <td><?= e($opt['option_name']) ?></td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/pricing.php" style="display: flex; gap: 6px; align-items: center; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $opt['id'] ?>">
                                            <input type="number" name="unit_price" value="<?= (int)$opt['unit_price'] ?>" min="0" step="1" style="width: 90px; padding: 6px;" aria-label="Unit price">
                                            <button type="submit" name="update_price" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($opt['is_active']): ?>
                                            <span class="status-pill badge-delivered">Active</span>
                                        <?php else: ?>
                                            <span class="status-pill badge-cancelled">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/pricing.php" style="margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $opt['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $opt['is_active'] ?>">
                                            <button type="submit" name="toggle_option" class="btn btn-secondary btn-sm">
                                                <?= $opt['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add New Size / Option Form -->
            <div class="panel">
                <h2 class="panel-title mb-3">Add Size Tier Option</h2>
                <form method="post" action="<?= APP_URL ?>/admin/pricing.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="service_id">Parent Service *</label>
                        <select id="service_id" name="service_id" required>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= $srv['id'] ?>"><?= e($srv['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="option_name">Size / Tier Label *</label>
                        <input type="text" id="option_name" name="option_name" required placeholder="e.g. Extra Large / 7 x 7">
                    </div>

                    <div class="form-group">
                        <label for="unit_price">Price in KES *</label>
                        <input type="number" id="unit_price" name="unit_price" required min="0" step="1" placeholder="450">
                    </div>

                    <button type="submit" name="add_option" class="btn btn-primary btn-block">
                        Add Price Option
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>