<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_role(['customer']);

$user = current_user();
$userId = (int)$user['id'];
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['full_name'] ?? '');
        $phone = sanitize_phone($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $addressDetails = trim($_POST['address_details'] ?? '');

        if (empty($name)) {
            $errors['full_name'] = 'Full name cannot be empty.';
        }
        if (!is_valid_kenyan_phone($phone)) {
            $errors['phone'] = 'Please enter a valid Kenyan phone number (e.g. 0101476174 or 07XXXXXXXX).';
        }
        if (empty($location)) {
            $errors['location'] = 'Location cannot be empty.';
        }

        if (empty($errors)) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $stmtU = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
                $stmtU->execute([$name, $phone, $userId]);

                $stmtP = $pdo->prepare('
                    INSERT INTO customer_profiles (user_id, location, address_details) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE location = ?, address_details = ?
                ');
                $stmtP->execute([$userId, $location, $addressDetails, $location, $addressDetails]);

                $pdo->commit();
                audit_log($userId, 'Profile updated', 'customer_profiles', $userId, 'Updated personal contact details');
                flash('success', 'Profile details updated successfully.');
                redirect('customer/profile.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                $errors['general'] = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'New passwords do not match.';
        } else {
            try {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([$newHash, $userId]);

                audit_log($userId, 'Password changed', 'users', $userId, 'User changed password');
                flash('success', 'Your password has been changed successfully.');
                redirect('customer/profile.php');
            } catch (Throwable $e) {
                $errors['general'] = 'Failed to change password. Please try again.';
            }
        }
    }
}

$pageTitle = 'Account Profile';
require __DIR__ . '/../includes/header.php';
?>

<main class="portal-shell">
    <div class="container" style="max-width: 860px;">
        <div class="portal-header">
            <div>
                <span class="portal-title-tag">PREFERENCES</span>
                <h1 style="color: var(--navy); margin-bottom: 4px;">Profile &amp; Settings</h1>
                <p class="text-muted" style="margin: 0;">
                    Manage your contact details, default hostel/residence address, and password.
                </p>
            </div>
            <div>
                <a class="btn btn-secondary btn-sm" href="<?= APP_URL ?>/customer/dashboard.php">&larr; Back to Dashboard</a>
            </div>
        </div>

        <?php require __DIR__ . '/../includes/customer-nav.php'; ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <!-- Contact Information Panel -->
        <div class="panel">
            <h2 class="panel-title mb-3">Personal &amp; Pickup Address</h2>
            <form method="post" action="<?= APP_URL ?>/customer/profile.php">
                <?= csrf_field() ?>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required value="<?= e($user['full_name']) ?>">
                        <?php if (!empty($errors['full_name'])): ?><div class="form-error-msg"><?= e($errors['full_name']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address (Immutable)</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled style="background-color: var(--bg-light); cursor: not-allowed;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="phone">Phone / WhatsApp Number *</label>
                        <input type="text" id="phone" name="phone" required value="<?= e($user['phone']) ?>">
                        <?php if (!empty($errors['phone'])): ?><div class="form-error-msg"><?= e($errors['phone']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="location">Default Estate / Hostel Location *</label>
                        <input type="text" id="location" name="location" required value="<?= e($user['location'] ?? '') ?>" placeholder="e.g. Ndagani Stage, Sunrise Hostels">
                        <?php if (!empty($errors['location'])): ?><div class="form-error-msg"><?= e($errors['location']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address_details">Specific Room / Gate / Apartment Details</label>
                    <input type="text" id="address_details" name="address_details" value="<?= e($user['address_details'] ?? '') ?>" placeholder="e.g. Block C, Room 10">
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">Save Profile Changes</button>
            </form>
        </div>

        <!-- Change Password Panel -->
        <div class="panel">
            <h2 class="panel-title mb-3">Security &amp; Password</h2>
            <form method="post" action="<?= APP_URL ?>/customer/profile.php">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required placeholder="Enter your current password">
                    <?php if (!empty($errors['current_password'])): ?><div class="form-error-msg"><?= e($errors['current_password']) ?></div><?php endif; ?>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="new_password">New Password (Min 8 Characters) *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Enter new password">
                        <?php if (!empty($errors['new_password'])): ?><div class="form-error-msg"><?= e($errors['new_password']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-type new password">
                        <?php if (!empty($errors['confirm_password'])): ?><div class="form-error-msg"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                    </div>
                </div>

                <button type="submit" name="change_password" class="btn btn-secondary">Update Password</button>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
