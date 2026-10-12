<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['admin', 'staff']);

$pdo = db();
$user = current_user();

// Handle Record Payment Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    verify_csrf();
    $orderRef = trim($_POST['order_ref'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $method = $_POST['method'] ?? 'M-Pesa';
    $refCode = trim($_POST['reference'] ?? '');
    $status = $_POST['status'] ?? 'Paid';

    if ($amount <= 0) {
        flash('error', 'Please enter a valid payment amount greater than zero.');
    } else {
        // Look up order by reference
        $stmtOrder = $pdo->prepare('SELECT id, customer_id, reference, total_amount FROM orders WHERE reference = ?');
        $stmtOrder->execute([$orderRef]);
        $order = $stmtOrder->fetch();

        if (!$order) {
            flash('error', "No order found matching reference '{$orderRef}'.");
        } else {
            try {
                $orderId = (int)$order['id'];
                $paymentCode = $refCode ?: ('TXN-' . strtoupper(bin2hex(random_bytes(4))));

                // Check for duplicate payment reference on this order
                $checkDup = $pdo->prepare('SELECT id FROM payments WHERE order_id = ? AND reference = ?');
                $checkDup->execute([$orderId, $paymentCode]);
                if ($checkDup->fetch()) {
                    flash('error', "A payment with transaction reference '{$paymentCode}' has already been recorded for this order.");
                } else {
                    $stmtPay = $pdo->prepare('
                        INSERT INTO payments (order_id, amount, method, reference, status, paid_at, recorded_by)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?)
                    ');
                    $stmtPay->execute([$orderId, $amount, $method, $paymentCode, $status, $user['id']]);

                    notify_user(
                        (int)$order['customer_id'],
                        $orderId,
                        'Payment Verified',
                        "A payment of KES " . number_format($amount, 0) . " ({$method} - {$paymentCode}) has been confirmed for order {$order['reference']}."
                    );

                    audit_log((int)$user['id'], 'Record payment', 'payments', $orderId, "Recorded KES {$amount} ({$method}) for {$order['reference']}");
                    flash('success', "Payment of KES " . number_format($amount, 0) . " successfully credited to order {$order['reference']}.");
                    redirect('admin/payments.php');
                }
            } catch (Throwable $e) {
                flash('error', 'Failed to save payment: ' . $e->getMessage());
            }
        }
    }
}

// Financial metrics
$totalCollected = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn();
$mpesaTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid' AND method = 'M-Pesa'")->fetchColumn();
$cashTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid' AND method = 'Cash'")->fetchColumn();
$bankTotal = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid' AND method = 'Bank'")->fetchColumn();

// Fetch all recorded payments
$stmtPayments = $pdo->query('
    SELECT p.*, o.reference as order_reference, o.total_amount, u.full_name as customer_name, staff.full_name as recorded_by_staff
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN users u ON u.id = o.customer_id
    LEFT JOIN users staff ON staff.id = p.recorded_by
    ORDER BY p.paid_at DESC
    LIMIT 30
');
$paymentsList = $stmtPayments->fetchAll();

$pageTitle = 'Payments & Financial Ledger';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container-wide">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">FINANCIAL CONTROL</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Payments &amp; Receipts</h1>
                <p class="text-muted" style="margin: 0;">
                    Manual confirmation and transaction ledger for M-Pesa, Cash, and Bank payments.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/admin/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/admin-nav.php'; ?>

        <!-- Daraja M-Pesa Integration Notice -->
        <div class="panel" style="background-color: #F0FDF4; border-left: 4px solid #16A34A; padding: 16px;">
            <strong style="color: #15803D; font-size: 0.95rem;">M-Pesa Verification Mode: Manual Confirmation Active</strong>
            <p style="margin: 4px 0 0; font-size: 0.88rem; color: #166534;">
                Payments are recorded here after physically verifying customer cash or M-Pesa SMS confirmation codes. Automated Safaricom Daraja STK Push API can be connected via API keys in production.
            </p>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stat-cards-grid">
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--success); font-size: 2rem;">
                    KES <?= number_format($totalCollected, 0) ?>
                </div>
                <div class="stat-card-label">Total Verified Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: #059669; font-size: 2rem;">
                    KES <?= number_format($mpesaTotal, 0) ?>
                </div>
                <div class="stat-card-label">M-Pesa Collections</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--blue); font-size: 2rem;">
                    KES <?= number_format($cashTotal, 0) ?>
                </div>
                <div class="stat-card-label">Cash Collections</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-number" style="color: var(--navy); font-size: 2rem;">
                    KES <?= number_format($bankTotal, 0) ?>
                </div>
                <div class="stat-card-label">Bank Transfers</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.35fr 0.65fr; gap: 28px; align-items: start;">
            <!-- Payments Ledger Table -->
            <div class="panel">
                <h2 class="panel-title mb-3">Recorded Payment Transactions</h2>
                <?php if (empty($paymentsList)): ?>
                    <p class="text-muted text-center" style="padding: 30px 0;">No payments recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order Ref</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction Code</th>
                                    <th>Status</th>
                                    <th>Timestamp / Staff</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentsList as $pay): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/admin/order-details.php?id=<?= $pay['order_id'] ?>" class="fw-bold" style="color: var(--navy);">
                                                <?= e($pay['order_reference']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($pay['customer_name']) ?></td>
                                        <td><strong><?= money($pay['amount']) ?></strong></td>
                                        <td><span class="status-pill badge-default"><?= e($pay['method']) ?></span></td>
                                        <td><code><?= e($pay['reference'] ?? 'N/A') ?></code></td>
                                        <td><?= payment_badge($pay['status']) ?></td>
                                        <td>
                                            <?= format_datetime($pay['paid_at']) ?><br>
                                            <small class="text-muted"><?= e($pay['recorded_by_staff'] ?? 'Staff') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Record New Payment Form -->
            <div class="panel">
                <h2 class="panel-title mb-3">Record Verified Payment</h2>
                <form method="post" action="<?= APP_URL ?>/admin/payments.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="order_ref">Order Reference *</label>
                        <input type="text" id="order_ref" name="order_ref" required placeholder="e.g. SLS-261011-A1B2">
                    </div>

                    <div class="form-group">
                        <label for="amount">Amount Received (KES) *</label>
                        <input type="number" id="amount" name="amount" required min="1" step="1" placeholder="300">
                    </div>

                    <div class="form-group">
                        <label for="method">Payment Method *</label>
                        <select id="method" name="method" required>
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reference">Transaction Reference / Receipt #</label>
                        <input type="text" id="reference" name="reference" placeholder="e.g. QJK78491LK">
                    </div>

                    <div class="form-group">
                        <label for="status">Payment Status</label>
                        <select id="status" name="status">
                            <option value="Paid">Paid / Confirmed</option>
                            <option value="Partially Paid">Partially Paid</option>
                        </select>
                    </div>

                    <button type="submit" name="record_payment" class="btn btn-primary btn-block">
                        Save Verified Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>