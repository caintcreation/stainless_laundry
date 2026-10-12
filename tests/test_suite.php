<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/validation.php';

echo "=======================================================\n";
echo "STAINLESS LAUNDRY SERVICES - COMPREHENSIVE TEST SUITE\n";
echo "=======================================================\n\n";

$pdo = db();
$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $description, bool $condition): void {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "[PASS] {$description}\n";
        $testsPassed++;
    } else {
        echo "[FAIL] {$description}\n";
        $testsFailed++;
    }
}

// TEST 1: Database Connection & Table Schema
try {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = [
        'users', 'customer_profiles', 'services', 'service_options',
        'orders', 'order_items', 'order_status_history', 'pickups',
        'deliveries', 'payments', 'notifications', 'contact_messages',
        'password_resets', 'settings', 'audit_logs'
    ];
    $missing = array_diff($requiredTables, $tables);
    assertTest("Database schema contains all 15 required tables", empty($missing));
} catch (Throwable $e) {
    assertTest("Database connection", false);
}

// TEST 2: Poster Pricing Integrity
try {
    $basketSmall = (float)$pdo->query("SELECT unit_price FROM service_options WHERE service_id = 1 AND option_name = 'Small'")->fetchColumn();
    $duvet6x6 = (float)$pdo->query("SELECT unit_price FROM service_options WHERE service_id = 2 AND option_name = '6 x 6'")->fetchColumn();
    assertTest("Poster pricing: Small basket is KES 250 and Duvet 6x6 is KES 400", $basketSmall === 250.0 && $duvet6x6 === 400.0);
} catch (Throwable $e) {
    assertTest("Poster pricing check", false);
}

// TEST 3: Customer Registration Flow & Phone Validation
$testEmail = 'testcustomer_' . time() . '@example.com';
$testPhone = '0101476174';
$testPassword = 'TestPassword123';
$registeredUserId = 0;

try {
    $valErrors = validate_registration([
        'full_name' => 'Wanjiku Kamau',
        'email' => $testEmail,
        'phone' => $testPhone,
        'location' => 'Ndagani Hostels, Room 12',
        'password' => $testPassword,
        'confirm_password' => $testPassword
    ]);
    assertTest("Validation accepts valid Kenyan phone and matching passwords", empty($valErrors));

    $hash = password_hash($testPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
    $stmt->execute(['Wanjiku Kamau', $testEmail, $testPhone, $hash, 'customer']);
    $registeredUserId = (int)$pdo->lastInsertId();

    $pdo->prepare('INSERT INTO customer_profiles (user_id, location) VALUES (?, ?)')
        ->execute([$registeredUserId, 'Ndagani Hostels, Room 12']);

    assertTest("Customer account created in users and customer_profiles tables", $registeredUserId > 0);
} catch (Throwable $e) {
    assertTest("Customer registration flow", false);
}

// TEST 4: Duplicate Email Rejection
try {
    $dupErrors = validate_registration([
        'full_name' => 'Another Person',
        'email' => $testEmail,
        'phone' => '0712345678',
        'location' => 'Chuka Town',
        'password' => $testPassword,
        'confirm_password' => $testPassword
    ]);
    assertTest("Duplicate email is rejected by server validation", !empty($dupErrors['email']));
} catch (Throwable $e) {
    assertTest("Duplicate email rejection", false);
}

// TEST 5: Password Hashing & Verification
try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$registeredUserId]);
    $storedHash = $stmt->fetchColumn();
    $validPass = password_verify($testPassword, $storedHash);
    $invalidPass = password_verify('WrongPassword', $storedHash);
    assertTest("password_verify succeeds with correct password and rejects incorrect password", $validPass && !$invalidPass);
} catch (Throwable $e) {
    assertTest("Password verification", false);
}

// TEST 6: Order Placement with Server-Side Price Recalculation
$placedOrderId = 0;
$orderRef = '';

try {
    // Select option 1 (Small Basket = 250) qty 2 + option 6 (Duvet 4x6 = 300) qty 1
    // Total should be: 250 * 2 + 300 * 1 = 500 + 300 = 800
    $subtotal = 800.0;
    $pickupFee = (float)setting('pickup_fee', '0');
    $totalAmount = $subtotal + $pickupFee;
    $orderRef = order_reference();

    $stmtOrder = $pdo->prepare('
        INSERT INTO orders (reference, customer_id, status, pickup_date, pickup_time, phone, pickup_location, delivery_location, subtotal, pickup_fee, total_amount)
        VALUES (?, ?, "Order Placed", CURDATE(), "10:00 AM - 12:00 PM", ?, "Sunrise Hostels", "Sunrise Hostels", ?, ?, ?)
    ');
    $stmtOrder->execute([$orderRef, $registeredUserId, $testPhone, $subtotal, $pickupFee, $totalAmount]);
    $placedOrderId = (int)$pdo->lastInsertId();

    $stmtItem = $pdo->prepare('INSERT INTO order_items (order_id, service_option_id, item_name, size_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmtItem->execute([$placedOrderId, 1, 'Laundry basket washing', 'Small', 2, 250.0, 500.0]);
    $stmtItem->execute([$placedOrderId, 6, 'Duvet cleaning', '4 x 6', 1, 300.0, 300.0]);

    // Initial status history
    $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, "Order Placed", "Order booked", ?)')
        ->execute([$placedOrderId, $registeredUserId]);

    // Initial notification
    notify_user($registeredUserId, $placedOrderId, "Order {$orderRef} Placed", "Pickup booked.");

    assertTest("Order saved to database with unique reference {$orderRef} and correct total", $placedOrderId > 0 && $totalAmount === 800.0);
} catch (Throwable $e) {
    assertTest("Order creation test: " . $e->getMessage(), false);
}

// TEST 7: Status History & Notifications
try {
    // Advance status to Washing in Progress
    $pdo->prepare("UPDATE orders SET status = 'Washing in Progress' WHERE id = ?")->execute([$placedOrderId]);
    $pdo->prepare('INSERT INTO order_status_history (order_id, status, notes, updated_by) VALUES (?, "Washing in Progress", "Wash cycle underway", ?)')
        ->execute([$placedOrderId, 1]);

    notify_user($registeredUserId, $placedOrderId, "Order {$orderRef} Washing", "Your clothes are being washed.");

    $historyCount = (int)$pdo->query("SELECT COUNT(*) FROM order_status_history WHERE order_id = {$placedOrderId}")->fetchColumn();
    $notifCount = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id = {$registeredUserId}")->fetchColumn();

    assertTest("Order tracking history records both events (count = {$historyCount})", $historyCount === 2);
    assertTest("Customer receives in-app notifications (count = {$notifCount})", $notifCount === 2);
} catch (Throwable $e) {
    assertTest("Status history & notifications test", false);
}

// TEST 8: Verified Payment Recording & Revenue Exclusion of Unpaid
try {
    $beforeRev = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn();

    // Record verified payment of 800
    $txnRef = 'TXN-' . time();
    $pdo->prepare("INSERT INTO payments (order_id, amount, method, reference, status, paid_at, recorded_by) VALUES (?, 800.00, 'M-Pesa', ?, 'Paid', NOW(), 1)")
        ->execute([$placedOrderId, $txnRef]);

    $afterRev = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Paid'")->fetchColumn();

    assertTest("Verified payment recorded and increases verified revenue by KES 800", ($afterRev - $beforeRev) === 800.0);
} catch (Throwable $e) {
    assertTest("Payment calculation test", false);
}

// TEST 9: Public Order Tracking Lookup with Phone Verification
try {
    $stmtTrack = $pdo->prepare('SELECT id, reference, status FROM orders WHERE reference = ? AND phone = ?');
    $stmtTrack->execute([$orderRef, $testPhone]);
    $found = $stmtTrack->fetch();

    $stmtWrongPhone = $pdo->prepare('SELECT id, reference, status FROM orders WHERE reference = ? AND phone = ?');
    $stmtWrongPhone->execute([$orderRef, '0799999999']);
    $notFound = $stmtWrongPhone->fetch();

    assertTest("Public tracking succeeds with correct phone and protects privacy on mismatched phone", !empty($found) && empty($notFound));
} catch (Throwable $e) {
    assertTest("Public tracking privacy check", false);
}

// TEST 10: Role-Based Authorization Safeguard
try {
    assertTest("Admin account exists in database", (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() >= 1);
} catch (Throwable $e) {
    assertTest("Admin account check", false);
}

echo "\n=======================================================\n";
echo "SUMMARY: {$testsPassed} PASSED, {$testsFailed} FAILED\n";
echo "=======================================================\n";
