<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin']);

$pdo = db();
$errors = [];

// Handle Create / Edit Service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['create_service'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            flash('error', 'Service name cannot be empty.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO services (name, description, is_active) VALUES (?, ?, 1)');
            $stmt->execute([$name, $description]);
            audit_log((int)current_user()['id'], 'Create service', 'services', (int)$pdo->lastInsertId(), "Created service {$name}");
            flash('success', "Service '{$name}' created successfully.");
            redirect('admin/services.php');
        }
    } elseif (isset($_POST['update_service'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($id > 0 && !empty($name)) {
            $stmt = $pdo->prepare('UPDATE services SET name = ?, description = ?, is_active = ? WHERE id = ?');
            $stmt->execute([$name, $description, $isActive, $id]);
            audit_log((int)current_user()['id'], 'Update service', 'services', $id, "Updated service {$name}");
            flash('success', "Service '{$name}' updated.");
            redirect('admin/services.php');
        }
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['current_status'] ?? 0) === 1 ? 0 : 1;

        if ($id > 0) {
            $pdo->prepare('UPDATE services SET is_active = ? WHERE id = ?')->execute([$status, $id]);
            flash('success', 'Service visibility updated.');
            redirect('admin/services.php');
        }
    }
}

// Fetch all services with count of options
$services = $pdo->query('
    SELECT s.*, COUNT(so.id) as options_count 
    FROM services s 
    LEFT JOIN service_options so ON so.service_id = s.id 
    GROUP BY s.id 
    ORDER BY s.id ASC
')->fetchAll();

$pageTitle = 'Manage Services';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">CATALOGUE CONTROL</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Services Management</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage primary laundry service lines. Price options are configured in the Pricing section.
                </p>
            </div>
            <div>
                <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/admin/pricing.php">Manage Size Rates &amp; Pricing &rarr;</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 28px; align-items: start;">
            <!-- Current Services Table -->
            <div class="panel">
                <h2 class="panel-title mb-3">Service Offerings</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Service Name</th>
                                <th>Description</th>
                                <th>Pricing Tiers</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $srv): ?>
                                <tr>
                                    <td><strong><?= e($srv['name']) ?></strong></td>
                                    <td><small class="text-muted"><?= e($srv['description']) ?></small></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/admin/pricing.php" class="status-pill badge-default">
                                            <?= $srv['options_count'] ?> sizes
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($srv['is_active']): ?>
                                            <span class="status-pill badge-delivered">Active</span>
                                        <?php else: ?>
                                            <span class="status-pill badge-cancelled">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="<?= APP_URL ?>/admin/services.php" style="margin: 0; display: inline-block;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $srv['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $srv['is_active'] ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-secondary btn-sm">
                                                <?= $srv['is_active'] ? 'Disable' : 'Enable' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create New Service Form -->
            <div class="panel">
                <h2 class="panel-title mb-3">Add New Service</h2>
                <form method="post" action="<?= APP_URL ?>/admin/services.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name">Service Name *</label>
                        <input type="text" id="name" name="name" required placeholder="e.g. Curtain &amp; Drape Washing">
                    </div>

                    <div class="form-group">
                        <label for="description">Service Description</label>
                        <textarea id="description" name="description" placeholder="Short description of this laundry offering..."></textarea>
                    </div>

                    <button type="submit" name="create_service" class="btn btn-primary btn-block">
                        Create Service Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>