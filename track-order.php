<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Track Laundry Order';
$refInput = trim($_GET['ref'] ?? ($_POST['ref'] ?? ''));
$phoneInput = sanitize_phone(trim($_GET['phone'] ?? ($_POST['phone'] ?? '')));
$order = null;
$history = [];
$searched = false;
$error = null;

if (!empty($refInput)) {
    $searched = true;
    if (empty($phoneInput)) {
        $error = 'For privacy, please enter the customer phone number used when placing the order.';
    } else {
        try {
            // Find order matching reference and phone number
            $stmt = db()->prepare('
                SELECT o.id, o.reference, o.status, o.pickup_date, o.pickup_time, o.created_at
                FROM orders o
                WHERE o.reference = ? AND (o.phone = ? OR o.phone LIKE ?)
                LIMIT 1
            ');
            $stmt->execute([$refInput, $phoneInput, "%{$phoneInput}%"]);
            $order = $stmt->fetch();

            if ($order) {
                // Fetch public tracking events
                $stmtHistory = db()->prepare('
                    SELECT status, notes, created_at
                    FROM order_status_history
                    WHERE order_id = ?
                    ORDER BY created_at ASC
                ');
                $stmtHistory->execute([(int)$order['id']]);
                $history = $stmtHistory->fetchAll();
            } else {
                $error = 'No order found matching reference "' . e($refInput) . '" and the provided phone number. Please check and try again.';
            }
        } catch (Throwable $e) {
            $error = 'Unable to look up order at this time.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="section section-bg-light" style="padding: 50px 0;">
        <div class="container text-center">
            <span class="section-tag">DATABASE ORDER TRACKING</span>
            <h1 class="section-title">Track Your Laundry Live</h1>
            <p class="section-subtext" style="max-width: 650px; margin: 0 auto;">
                Check the real-time milestone status of your clothes recorded by our Ndagani team.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width: 860px;">
            <!-- Search Lookup Form -->
            <div class="panel">
                <h3 class="mb-3">Order Status Lookup</h3>
                <form method="get" action="<?= APP_URL ?>/track-order.php">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="ref">Order Reference Number *</label>
                            <input type="text" id="ref" name="ref" required value="<?= e($refInput) ?>" placeholder="e.g. SLS-261011-A1B2">
                        </div>
                        <div class="form-group">
                            <label for="phone">Customer Phone Number *</label>
                            <input type="text" id="phone" name="phone" required value="<?= e($phoneInput) ?>" placeholder="e.g. 0101476174">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Track Order Status</button>
                </form>

                <p class="form-hint text-center mt-2">
                    Note: Sensitive customer addresses and full payment histories are kept confidential and are only visible inside your logged-in customer account.
                </p>
            </div>

            <!-- Results Section -->
            <?php if ($searched): ?>
                <?php if ($error): ?>
                    <div class="alert alert-error mt-4"><?= e($error) ?></div>
                <?php elseif ($order): ?>
                    <div class="panel mt-4">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border-light); padding-bottom: 16px; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <span class="portal-title-tag">VERIFIED ORDER</span>
                                <h2 style="font-size: 1.6rem; color: var(--navy);"><?= e($order['reference']) ?></h2>
                                <span class="text-muted" style="font-size: 0.88rem;">Booked on <?= format_datetime($order['created_at']) ?></span>
                            </div>
                            <div>
                                <?= status_badge($order['status']) ?>
                            </div>
                        </div>

                        <!-- Vertical Milestone Timeline -->
                        <h3 class="mb-3">Live Progress History</h3>
                        <div class="order-tracking-vertical">
                            <?php foreach ($history as $event): ?>
                                <div class="timeline-event-row">
                                    <div class="timeline-event-marker">&#10003;</div>
                                    <div class="timeline-event-content">
                                        <div class="timeline-event-title"><?= e($event['status']) ?></div>
                                        <div class="timeline-event-meta"><?= format_datetime($event['created_at']) ?></div>
                                        <?php if (!empty($event['notes'])): ?>
                                            <div class="timeline-event-notes"><?= e($event['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="background-color: var(--bg-light); border-radius: var(--radius-md); padding: 16px; margin-top: 24px; font-size: 0.9rem;">
                            <strong>Scheduled Pickup:</strong> <?= format_date($order['pickup_date']) ?> (<?= e($order['pickup_time']) ?>)<br>
                            For full order receipts, item details, or to book a new pickup, please <a href="<?= APP_URL ?>/login.php" class="fw-bold">Log in to your Customer Account</a>.
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>