<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$rawToken = trim($_GET['token'] ?? '');
$tokenHash = hash('sha256', $rawToken);
$error = null;
$validReset = null;

if (empty($rawToken)) {
    $error = 'Missing password reset token.';
} else {
    try {
        $stmt = db()->prepare('
            SELECT pr.*, u.full_name, u.email 
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
            LIMIT 1
        ');
        $stmt->execute([$tokenHash]);
        $validReset = $stmt->fetch();

        if (!$validReset) {
            $error = 'This password reset link is invalid or has expired. Please request a new one.';
        }
    } catch (Throwable) {
        $error = 'Unable to verify reset token.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validReset) {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $userId = (int)$validReset['user_id'];

            // Update user password
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, $userId]);

            // Mark this token used
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int)$validReset['id']]);

            // Invalidate any other tokens
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);

            $pdo->commit();

            audit_log($userId, 'Password reset completed', 'users', $userId, 'User reset password successfully');
            flash('success', 'Your password has been reset successfully! Please log in with your new password.');
            redirect('login.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            $error = 'Failed to update password. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password';
require __DIR__ . '/includes/header.php';
?>

<main class="form-page">
    <div class="form-card">
        <span class="section-tag">SECURITY UPDATE</span>
        <h1 class="mb-2">Choose New Password</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
            <p class="text-center mt-3"><a href="<?= APP_URL ?>/forgot-password.php" class="btn btn-secondary btn-sm">Request New Reset Link</a></p>
        <?php elseif ($validReset): ?>
            <p class="text-muted mb-4">
                Setting new password for <strong><?= e($validReset['full_name']) ?></strong> (<?= e($validReset['email']) ?>).
            </p>

            <form method="post" action="<?= APP_URL ?>/reset-password.php?token=<?= urlencode($rawToken) ?>">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="password">New Password (Minimum 8 Characters) *</label>
                    <input type="password" id="password" name="password" required minlength="8" placeholder="Enter new password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-type new password">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">Save &amp; Update Password</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
