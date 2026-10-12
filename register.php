<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

if ($user = current_user()) {
    redirect($user['role'] === 'customer' ? 'customer/dashboard.php' : 'admin/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    require_once __DIR__ . '/includes/validation.php';
    $errors = validate_registration($_POST);

    if (empty($errors)) {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = sanitize_phone($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $addressDetails = trim($_POST['address_details'] ?? '');
        $password = $_POST['password'] ?? '';

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$name, $email, $phone, $hash, 'customer']);
            $userId = (int)$pdo->lastInsertId();

            $stmtProfile = $pdo->prepare('INSERT INTO customer_profiles (user_id, location, address_details) VALUES (?, ?, ?)');
            $stmtProfile->execute([$userId, $location, $addressDetails]);

            $pdo->commit();

            audit_log($userId, 'User registration', 'users', $userId, 'Customer account created');

            // Authenticate and fetch full user record
            $userRecord = [
                'id' => $userId,
                'full_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'customer',
                'is_active' => 1
            ];
            login_user($userRecord);

            flash('success', 'Account created successfully! Welcome to Stainless Laundry Services.');
            redirect('customer/dashboard.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors['general'] = 'Failed to create your account due to a server error. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
require __DIR__ . '/includes/header.php';
?>

<main class="form-page">
    <div class="form-card form-card-wide">
        <span class="section-tag">JOIN STAINLESS LAUNDRY</span>
        <h1 class="mb-2">Create Customer Account</h1>
        <p class="text-muted mb-4">
            Register to book quick doorstep pickups, track your laundry progress live, and review order receipts.
        </p>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= APP_URL ?>/register.php">
            <?= csrf_field() ?>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Grace Wanjiku">
                    <?php if (!empty($errors['full_name'])): ?><div class="form-error-msg"><?= e($errors['full_name']) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="grace@example.com">
                    <?php if (!empty($errors['email'])): ?><div class="form-error-msg"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="phone">Kenyan Phone / WhatsApp Number *</label>
                    <input type="text" id="phone" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>" placeholder="0101476174 or 07XXXXXXXX">
                    <span class="form-hint">Format: 10 digits starting with 01 or 07</span>
                    <?php if (!empty($errors['phone'])): ?><div class="form-error-msg"><?= e($errors['phone']) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="location">Location / Estate / Hostel *</label>
                    <input type="text" id="location" name="location" required value="<?= e($_POST['location'] ?? '') ?>" placeholder="e.g. Ndagani Stage, Sunrise Hostels">
                    <?php if (!empty($errors['location'])): ?><div class="form-error-msg"><?= e($errors['location']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="address_details">Specific Room / Apartment / Gate Details (Optional)</label>
                <input type="text" id="address_details" name="address_details" value="<?= e($_POST['address_details'] ?? '') ?>" placeholder="e.g. Block B, Room 14, 2nd Floor">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label for="password">Password (Minimum 8 Characters) *</label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="Create a strong password">
                    <?php if (!empty($errors['password'])): ?><div class="form-error-msg"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-type password">
                    <?php if (!empty($errors['confirm_password'])): ?><div class="form-error-msg"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">Register &amp; Book Laundry</button>
        </form>

        <div class="text-center mt-4" style="border-top: 1px solid var(--border-light); padding-top: 20px; font-size: 0.92rem;">
            Already have an account? <a href="<?= APP_URL ?>/login.php" class="fw-bold">Log In Here</a>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>