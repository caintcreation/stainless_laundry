<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];
$errors = [];

// Fetch active services and options
$services = db()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY id')->fetchAll();
$optionsStmt = db()->query('
    SELECT so.*, s.name as service_name 
    FROM service_options so 
    JOIN services s ON s.id = so.service_id 
    WHERE so.is_active = 1 AND s.is_active = 1 
    ORDER BY s.id, so.id
');
$options = $optionsStmt->fetchAll();

// Group options by service
$groupedOptions = [];
foreach ($options as $opt) {
    $groupedOptions[$opt['service_name']][] = $opt;
}

$pickupFee = (float)setting('pickup_fee', '0');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $selectedOptionIds = $_POST['option_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $pickupDate = trim($_POST['pickup_date'] ?? '');
    $pickupTime = trim($_POST['pickup_time'] ?? '');
    $phone = sanitize_phone(trim($_POST['phone'] ?? $user['phone']));
    $pickupLocation = trim($_POST['pickup_location'] ?? '');
    $deliveryLocation = trim($_POST['delivery_location'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'Cash';

    // Validation
    if (empty($selectedOptionIds) || !is_array($selectedOptionIds)) {
        $errors[] = 'Please select at least one laundry item to book.';
    }

    if (empty($pickupDate) || $pickupDate < date('Y-m-d')) {
        $errors[] = 'Please select a valid pickup date from today onwards.';
    }

    if (empty($pickupTime)) {
        $errors[] = 'Please select your preferred pickup time window.';
    }

    if (!is_valid_kenyan_phone($phone)) {
        $errors[] = 'Please provide a valid Kenyan phone number (e.g. 0101476174 or 07XXXXXXXX).';
    }

    if (empty($pickupLocation)) {
        $errors[] = 'Please enter your pickup location / hostel in Ndagani or Chuka.';
    }

    if (empty($deliveryLocation)) {
        $errors[] = 'Please enter your return delivery location.';
    }

    if (!in_array($paymentMethod, ['Cash', 'M-Pesa', 'Bank'], true)) {
        $errors[] = 'Please select an approved payment method.';
    }

    if (empty($errors)) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Secure server-side calculation: look up each selected option strictly from the DB!
            $subtotal = 0.0;
            $orderItems = [];

            $lookupStmt = $pdo->prepare('
                SELECT so.id, so.option_name, so.unit_price, s.name as service_name
                FROM service_options so
                JOIN services s ON s.id = so.service_id
                WHERE so.id = ? AND so.is_active = 1
            ');

            foreach ($selectedOptionIds as $optId) {
                $optIdInt = (int)$optId;
                $lookupStmt->execute([$optIdInt]);
                $dbItem = $lookupStmt->fetch();

                if ($dbItem) {
                    $qty = max(1, (int)($quantities[$optIdInt] ?? 1));
                    $unitPrice = (float)$dbItem['unit_price'];
                    $lineTotal = $unitPrice * $qty;
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'service_option_id' => $optIdInt,
                        'item_name' => $dbItem['service_name'],
                        'size_name' => $dbItem['option_name'],
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineTotal
                    ];
                }
            }

            if (empty($orderItems)) {
                throw new RuntimeException('No valid active items selected.');
            }

            $totalAmount = $subtotal + $pickupFee;
            $reference = order_reference();

            // Insert into orders table
            $stmtOrder = $pdo->prepare('
                INSERT INTO orders (
                    reference, customer_id, status, pickup_date, pickup_time, phone,
                    pickup_location, delivery_location, instructions, payment_method,
                    subtotal, pickup_fee, total_amount
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtOrder->execute([
                $reference,
                $userId,
                'Order Placed',
                $pickupDate,
                $pickupTime,
                $phone,
                $pickupLocation,
                $deliveryLocation,
                $instructions,
                $paymentMethod,
                $subtotal,
                $pickupFee,
                $totalAmount
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Insert order items snapshots
            $stmtItem = $pdo->prepare('
                INSERT INTO order_items (
                    order_id, service_option_id, item_name, size_name, quantity, unit_price, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($orderItems as $item) {
                $stmtItem->execute([
                    $orderId,
                    $item['service_option_id'],
                    $item['item_name'],
                    $item['size_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                ]);
            }

            // Create initial tracking entry in order_status_history
            $stmtHistory = $pdo->prepare('
                INSERT INTO order_status_history (order_id, status, notes, updated_by)
                VALUES (?, ?, ?, ?)
            ');
            $stmtHistory->execute([
                $orderId,
                'Order Placed',
                'Laundry pickup order booked online by customer.',
                $userId
            ]);

            // Create pickup dispatch record
            $scheduledPickupTime = $pickupDate . ' ' . (str_contains($pickupTime, ':') ? substr($pickupTime, 0, 5) . ':00' : '09:00:00');
            $stmtPickup = $pdo->prepare('
                INSERT INTO pickups (order_id, scheduled_at, customer_notes)
                VALUES (?, ?, ?)
            ');
            $stmtPickup->execute([$orderId, $scheduledPickupTime, $instructions]);

            // Create customer notification
            notify_user(
                $userId,
                $orderId,
                'Order ' . $reference . ' Placed',
                'Your laundry pickup request has been successfully registered. Our team will collect it on ' . format_date($pickupDate) . '.'
            );

            // Audit log
            audit_log($userId, 'Place order', 'orders', $orderId, "Placed order {$reference} with total KES {$totalAmount}");

            $pdo->commit();

            flash('success', "Order {$reference} booked successfully! We look forward to cleaning your laundry.");
            redirect("customer/order-details.php?id={$orderId}");
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to submit order due to a system error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Book Laundry Pickup';
$extraJs = 'assets/js/order.js';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container" style="max-width: 980px;">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">NEW BOOKING</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Book a Laundry Pickup</h1>
                <p class="text-muted" style="margin: 0;">
                    Select your basket or duvet sizes and schedule when our team should collect your clothes.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Please correct the following:</strong>
                <ul style="padding-left: 20px; margin-top: 6px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" id="orderForm" action="<?= APP_URL ?>/customer/place-order.php" data-pickup-fee="<?= $pickupFee ?>">
            <?= csrf_field() ?>

            <!-- Item Selection Panel -->
            <div class="panel">
                <h2 class="panel-title mb-3">1. Select Laundry Items</h2>
                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">
                    Check the items you want cleaned and specify the quantity for each. Prices match the Stainless Laundry Services poster.
                </p>

                <?php foreach ($groupedOptions as $serviceName => $items): ?>
                    <h3 style="font-size: 1.15rem; color: var(--navy); margin: 24px 0 12px; border-bottom: 1px solid var(--border-light); padding-bottom: 6px;">
                        <?= e($serviceName) ?>
                    </h3>
                    <div class="item-picker-list">
                        <?php foreach ($items as $item): ?>
                            <div class="item-picker-row">
                                <input type="checkbox" 
                                       class="item-checkbox" 
                                       id="opt_<?= $item['id'] ?>" 
                                       name="option_ids[]" 
                                       value="<?= $item['id'] ?>"
                                       data-price="<?= $item['unit_price'] ?>"
                                       data-name="<?= e($item['service_name'] . ' - ' . $item['option_name']) ?>">
                                <label class="item-picker-label" for="opt_<?= $item['id'] ?>">
                                    <strong><?= e($item['option_name']) ?></strong>
                                </label>
                                <div class="item-picker-price">
                                    KES <?= number_format((float)$item['unit_price'], 0) ?>
                                </div>
                                <div>
                                    <input type="number" 
                                           class="item-qty qty-input" 
                                           name="quantities[<?= $item['id'] ?>]" 
                                           value="1" 
                                           min="1" 
                                           max="20" 
                                           disabled 
                                           aria-label="Quantity for <?= e($item['option_name']) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Schedule & Location Panel -->
            <div class="panel">
                <h2 class="panel-title mb-3">2. Pickup &amp; Delivery Details</h2>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="pickup_date">Preferred Pickup Date *</label>
                        <input type="date" id="pickup_date" name="pickup_date" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['pickup_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="pickup_time">Preferred Pickup Time Slot *</label>
                        <select id="pickup_time" name="pickup_time" required>
                            <option value="">-- Choose Time Window --</option>
                            <option value="08:00 AM - 10:00 AM" <?= (($_POST['pickup_time'] ?? '') === '08:00 AM - 10:00 AM') ? 'selected' : '' ?>>Morning: 08:00 AM &ndash; 10:00 AM</option>
                            <option value="10:00 AM - 12:00 PM" <?= (($_POST['pickup_time'] ?? '') === '10:00 AM - 12:00 PM') ? 'selected' : '' ?>>Late Morning: 10:00 AM &ndash; 12:00 PM</option>
                            <option value="02:00 PM - 04:00 PM" <?= (($_POST['pickup_time'] ?? '') === '02:00 PM - 04:00 PM') ? 'selected' : '' ?>>Afternoon: 02:00 PM &ndash; 04:00 PM</option>
                            <option value="04:00 PM - 06:00 PM" <?= (($_POST['pickup_time'] ?? '') === '04:00 PM - 06:00 PM') ? 'selected' : '' ?>>Evening: 04:00 PM &ndash; 06:00 PM</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="phone">Phone / WhatsApp Number for Collection *</label>
                        <input type="text" id="phone" name="phone" required value="<?= e($_POST['phone'] ?? $user['phone']) ?>">
                        <span class="form-hint">Our collection rider will call this number before arrival.</span>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Preferred Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="Cash" <?= (($_POST['payment_method'] ?? '') === 'Cash') ? 'selected' : '' ?>>Cash on Collection / Delivery</option>
                            <option value="M-Pesa" <?= (($_POST['payment_method'] ?? '') === 'M-Pesa') ? 'selected' : '' ?>>M-Pesa (Buy Goods / Till)</option>
                            <option value="Bank" <?= (($_POST['payment_method'] ?? '') === 'Bank') ? 'selected' : '' ?>>Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="pickup_location">Pickup Location / Hostel / Apartment *</label>
                        <input type="text" id="pickup_location" name="pickup_location" required value="<?= e($_POST['pickup_location'] ?? ($user['location'] ?? '')) ?>" placeholder="e.g. Ndagani Stage, Sunrise Hostels">
                    </div>

                    <div class="form-group">
                        <label for="delivery_location">Return Delivery Location *</label>
                        <input type="text" id="delivery_location" name="delivery_location" required value="<?= e($_POST['delivery_location'] ?? ($user['location'] ?? '')) ?>" placeholder="e.g. Same as pickup or specify">
                    </div>
                </div>

                <div class="form-group">
                    <label for="instructions">Special Instructions or Fabric Care Notes (Optional)</label>
                    <textarea id="instructions" name="instructions" placeholder="e.g. Please wash the white hoodie separately, gate call code 123..."><?= e($_POST['instructions'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Order Total Summary & Confirmation -->
            <div class="panel">
                <h2 class="panel-title mb-3">3. Order Summary &amp; Confirmation</h2>
                <div id="summaryItems" style="margin-bottom: 14px;">
                    <!-- Dynamically populated by JS order.js -->
                </div>

                <div class="calc-summary-box">
                    <div class="calc-line">
                        <span>Items Subtotal:</span>
                        <strong id="calcSubtotal">KES 0</strong>
                    </div>
                    <div class="calc-line">
                        <span>Doorstep Pickup &amp; Delivery Fee:</span>
                        <strong id="calcPickupFee">KES <?= number_format($pickupFee, 0) ?></strong>
                    </div>
                    <div class="calc-line total">
                        <span>Estimated Total:</span>
                        <span id="calcGrandTotal">KES 0</span>
                    </div>
                </div>

                <p class="text-muted mt-3" style="font-size: 0.85rem;">
                    * All final pricing is securely confirmed and verified against the database. No payment is charged until confirmed by our Ndagani team.
                </p>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-3" disabled>
                    Select at least 1 item to proceed
                </button>
            </div>
        </form>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>